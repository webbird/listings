<?php
/**
 * Listings – import of a wbProfiles 2.x directory into an empty Listings
 * section. plan() is the dry run: it reads everything and reports what
 * would be created and what is left behind; run() does the same and writes.
 * The wbProfiles tables are only read, never changed.
 *
 * Fields come from $FORMS['regform'] of the template's inc.forms.php when
 * the wbProfiles module is still installed, otherwise from the field names
 * stored with the entries (labels then come from the names).
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Import
{
    private const P = '{TP}mod_profiles_';

    /** Files put into the folder during run(), removed again when the import is rolled back. */
    private static array $written = [];

    /** Columns of the items table that become fields of their own. */
    private const COLUMN_FIELDS = [
        'item_url'      => ['name' => 'website', 'type' => 'url', 'label' => 'Website'],
        'item_logo_url' => ['name' => 'logo_url', 'type' => 'url', 'label' => 'Logo (URL)'],
    ];

    /** Form names that are not fields: handled elsewhere or without data. */
    private const SKIP = ['item_id', 'do', 'page_id', 'item_name', 'item_email', 'item_elink', 'item_category',
        'additional_item_categories', 'item_password', 'item_password2', 'captcha'];

    public static function available(): bool
    {
        global $database;
        return $database->tableExists(self::P . 'items');
    }

    /**
     * wbProfiles directories that have data, with their page if the section still exists.
     *
     * @return list<array{section_id: int, items: int, title: string}>
     */
    public static function sources(): array
    {
        global $database;
        if (!self::available()) {
            return [];
        }
        $result = [];
        foreach ($database->fetchAll(
            'SELECT i.`section_id`, COUNT(*) AS `n`, MAX(p.`page_title`) AS `page_title`, MAX(s.`module`) AS `module`
               FROM `' . self::P . 'items` i
               LEFT JOIN `{TP}sections` s ON s.`section_id` = i.`section_id`
               LEFT JOIN `{TP}pages` p ON p.`page_id` = s.`page_id`
              GROUP BY i.`section_id` ORDER BY i.`section_id`'
        ) as $row) {
            // the section ID may belong to another module by now (data left behind after uninstalling)
            $own = ($row['module'] ?? '') === 'wbProfiles';
            $result[] = [
                'section_id' => (int) $row['section_id'],
                'items'      => (int) $row['n'],
                'title'      => $own ? (string) $row['page_title'] : '',
            ];
        }
        return $result;
    }

    /** The dry run. */
    public static function plan(int $source, array $options): array
    {
        return self::process($source, null, $options);
    }

    /** Imports into $target, which must be empty; returns the same report as plan(). */
    public static function run(int $source, int $target, int $pageId, array $options, int $userId): array
    {
        if (!self::targetEmpty($target)) {
            return ['errors' => [L_('MOD_LISTINGS:ERR_IMPORT_NOT_EMPTY')]] + self::emptyReport();
        }
        $report = [];
        self::$written = [];
        try {
            // all or nothing: a failure rolls back everything written so far
            $GLOBALS['database']->transaction(function () use ($source, $target, $pageId, $options, $userId, &$report) {
                $report = self::process($source, ['section_id' => $target, 'page_id' => $pageId, 'user_id' => $userId], $options);
                if ($report['errors'] !== []) {
                    throw new \RuntimeException('rolled back');
                }
                self::takeSettings($source, $target);
            });
            self::$written = [];
            return $report;
        } catch (\Throwable $e) {
            // the rollback does not reach the disk: the images and files written so far go as well
            Files::discard(['new' => self::$written]);
            self::$written = [];
            if (($report['errors'] ?? []) !== []) {
                return $report;
            }
            return ['errors' => [L_('MOD_LISTINGS:ERR_IMPORT_FAILED', $e->getMessage())]] + self::emptyReport();
        }
    }

    /**
     * The settings with a counterpart: approval, tags. Entries by visitors
     * stay off until the admin switches them on — wbProfiles allowed them
     * without e-mail confirmation.
     */
    private static function takeSettings(int $source, int $target): void
    {
        global $database;
        $old = [];
        foreach ($database->fetchAll('SELECT `attribute`, `value` FROM `' . self::P . 'settings` WHERE `section_id` = ?', [$source]) as $row) {
            $old[$row['attribute']] = (string) $row['value'];
        }
        $values = [];
        if (isset($old['admin_approve'])) {
            $values['approval'] = $old['admin_approve'] === 'yes' ? '1' : '0';
        }
        $hasTags = (int) $database->fetchValue('SELECT COUNT(*) FROM `{TP}mod_listings_tags` WHERE `section_id` = ?', [$target]) > 0;
        $values['tags_mode'] = ($old['use_tags'] ?? '') === 'yes' || $hasTags ? 'fixed' : 'off';
        $error = Settings::save($target, $values);
        if ($error !== null) {
            throw new \RuntimeException($error);
        }
    }

    /** Nothing in the section yet: no fields, entries or categories. */
    public static function targetEmpty(int $sectionId): bool
    {
        return Fields::forSection($sectionId) === [] && Items::count($sectionId) === 0 && Taxonomy::categories($sectionId) === [];
    }

    /**
     * Reads the source and, with $target, writes. The report:
     *   fields (name, label, type, source, values), counts, notes (what was
     *   changed on the way), skipped (what is not taken over), errors
     *
     * @param array{section_id: int, page_id: int, user_id: int}|null $target
     */
    private static function process(int $source, ?array $target, array $options): array
    {
        global $database;
        $report = self::emptyReport();
        $fix = !empty($options['fix_encoding']);
        $text = static function (?string $value) use ($fix, &$report): string {
            $value = (string) $value;
            if ($fix) {
                $repaired = self::repairEncoding($value);
                if ($repaired !== $value) {
                    $report['counts']['repaired']++;
                    return $repaired;
                }
            } elseif (self::repairEncoding($value) !== $value) {
                $report['counts']['broken']++;
            }
            return $value;
        };

        $items = $database->fetchAll('SELECT * FROM `' . self::P . 'items` WHERE `section_id` = ? ORDER BY `item_id`', [$source]);
        if ($items === []) {
            $report['errors'][] = L_('MOD_LISTINGS:ERR_IMPORT_NO_SOURCE');
            return $report;
        }
        $settings = [];
        foreach ($database->fetchAll('SELECT `attribute`, `value` FROM `' . self::P . 'settings` WHERE `section_id` = ?', [$source]) as $row) {
            $settings[$row['attribute']] = (string) $row['value'];
        }
        $itemIds = array_map(static fn ($r) => (int) $r['item_id'], $items);
        $in = implode(',', array_fill(0, count($itemIds), '?'));
        $attrs = [];
        foreach ($database->fetchAll('SELECT `item_id`, `fieldname`, `content` FROM `' . self::P . 'itemattr` WHERE `item_id` IN (' . $in . ')', $itemIds) as $row) {
            $attrs[(int) $row['item_id']][$row['fieldname']][] = (string) $row['content'];
        }

        // ---- fields ----
        $form = self::formDefinition($settings);
        $report['form_source'] = $form === null ? 'data' : 'forms';
        $fields = self::fieldsFrom($form, $attrs, $items, $text, !empty($options['public_email']));

        // ---- pictures and downloads: one gallery and one file field, if there are any ----
        $sourceFiles = self::sourceFiles($source, $items, $settings, $in, $itemIds);
        foreach ($sourceFiles as $kind => $byItem) {
            if ($byItem === []) {
                continue;
            }
            $found = array_merge(...array_values($byItem));
            $exts = array_unique(array_map(static fn ($f) => strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)), $found));
            $most = max(array_map('count', $byItem));
            $fields[] = [
                'name'       => self::fieldName($kind === 'pictures' ? 'pictures' : 'files', array_column($fields, 'name')),
                'label'      => L_($kind === 'pictures' ? 'MOD_LISTINGS:IMPORT_FIELD_PICTURES' : 'MOD_LISTINGS:IMPORT_FIELD_FILES'),
                'type'       => $kind === 'pictures' ? 'gallery' : 'file',
                'source'     => 'item_' . $kind,
                'visibility' => 'public',
                'in_list'    => $kind === 'pictures' ? 1 : 0,
                'options'    => $kind === 'pictures'
                    ? ['max_files' => max(Files::DEFAULT_MAX_FILES['gallery'], $most)]
                    : ['max_files' => max(Files::DEFAULT_MAX_FILES['file'], $most), 'extensions' => implode(', ', Files::parseExtensions(implode(',', $exts))) ?: Files::DEFAULT_EXTENSIONS],
                'values'     => count($byItem),
                'read'       => static fn () => null,
                'files'      => $byItem,
            ];
        }
        foreach ($fields as $f) {
            $report['fields'][] = ['name' => $f['name'], 'label' => $f['label'], 'type' => $f['type'], 'source' => $f['source'], 'values' => $f['values']];
        }

        // ---- categories ----
        $cats = $database->fetchAll('SELECT * FROM `' . self::P . 'categories` WHERE `section_id` = ? ORDER BY `cat_parent`, `cat_id`', [$source]);
        $report['counts']['categories'] = count($cats);
        $catMap = [];
        if ($target !== null) {
            // parents first: a category can only point to one that exists already
            $pending = $cats;
            for ($guard = 0; $pending !== [] && $guard < 50; $guard++) {
                foreach ($pending as $i => $cat) {
                    $parent = (int) $cat['cat_parent'];
                    if ($parent !== 0 && !isset($catMap[$parent]) && in_array($parent, array_map('intval', array_column($cats, 'cat_id')), true)) {
                        continue;
                    }
                    $name = $text($cat['cat_name']);
                    $database->insertRow('{TP}mod_listings_categories', [
                        'section_id'  => $target['section_id'],
                        'parent_id'   => $catMap[$parent] ?? 0,
                        'position'    => (int) ($cat['cat_position'] ?? $cat['cat_id']),
                        'name'        => mb_substr($name, 0, 255),
                        'slug'        => self::uniqueCatSlug($target['section_id'], $name),
                        'description' => $text($cat['cat_info'] ?? ''),
                        'visible'     => strtoupper((string) $cat['cat_visible']) === 'N' ? 0 : 1,
                    ]);
                    if ($database->hasError()) {
                        $report['errors'][] = $database->getError();
                        return $report;
                    }
                    $catMap[(int) $cat['cat_id']] = (int) $database->lastInsertId();
                    unset($pending[$i]);
                }
            }
        }
        $knownCats = array_map('intval', array_column($cats, 'cat_id'));
        $missingCats = [];

        // ---- tags ----
        $tags = $database->tableExists(self::P . 'tags')
            ? $database->fetchAll('SELECT * FROM `' . self::P . 'tags` WHERE `section_id` = ?', [$source]) : [];
        $tagLinks = [];
        if ($tags !== [] && $database->tableExists(self::P . 'tags_items')) {
            foreach ($database->fetchAll('SELECT `tag_id`, `item_id` FROM `' . self::P . 'tags_items` WHERE `item_id` IN (' . $in . ')', $itemIds) as $row) {
                $tagLinks[(int) $row['item_id']][] = (int) $row['tag_id'];
            }
        }
        $report['counts']['tags'] = count($tags);
        $tagMap = [];
        if ($target !== null) {
            foreach ($tags as $tag) {
                $ids = Taxonomy::tagIds($target['section_id'], [mb_substr(trim($text($tag['tag_name'])), 0, 100)], true);
                if ($ids !== []) {
                    $tagMap[(int) $tag['tag_id']] = $ids[0];
                }
            }
        }

        // ---- field definitions ----
        if ($target !== null) {
            foreach ($fields as $position => $f) {
                $database->insertRow('{TP}mod_listings_fields', [
                    'section_id' => $target['section_id'],
                    'position'   => $position + 1,
                    'name'       => $f['name'],
                    'type'       => $f['type'],
                    'label'      => mb_substr($f['label'], 0, 255),
                    'help'       => '',
                    'required'   => 0,
                    'visibility' => $f['visibility'],
                    'in_list'    => $f['in_list'],
                    'editable'   => $f['type'] === 'heading' ? 0 : 1,
                    'filterable' => 0,
                    'options'    => json_encode($f['options'], JSON_UNESCAPED_UNICODE),
                ]);
                if ($database->hasError()) {
                    $report['errors'][] = $database->getError();
                    return $report;
                }
            }
        }

        // ---- entries ----
        $now = time();
        $usedSlugs = [];
        foreach ($items as $item) {
            $id = (int) $item['item_id'];
            $status = 'published';
            if (strtolower((string) $item['item_locked']) === 'y' || (string) $item['item_key'] !== '') {
                // locked (waiting for the admin) and unconfirmed both wait for approval here
                $status = 'pending';
                $report['counts']['pending']++;
            }
            $expired = strtoupper((string) ($item['item_expired'] ?? 'N')) === 'Y';
            if ($expired) {
                $report['counts']['expired']++;
            }

            $data = [];
            foreach ($fields as $f) {
                $value = $f['read']($item, $attrs[$id] ?? []);
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }
                if ($f['type'] === 'textarea' && is_string($value) && $value !== strip_tags($value)) {
                    // Listings has no HTML field yet: the text stays, the markup goes
                    $value = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br />', '</p>'], "\n", $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $report['counts']['html']++;
                }
                $data[$f['name']] = $value;
            }

            $catIds = [];
            foreach (array_merge([(int) $item['cat_id']], array_map('intval', $attrs[$id]['additional_item_categories'] ?? [])) as $catId) {
                if ($catId === 0) {
                    continue;
                }
                if (!in_array($catId, $knownCats, true)) {
                    $missingCats[$catId] = true;
                    continue;
                }
                $catIds[] = $catId;
            }

            $title = mb_substr(preg_replace('/\s+/u', ' ', trim($text($item['item_name']))) ?? '', 0, 255);
            if ($title === '') {
                $title = '#' . $id;
            }
            $email = strtolower(trim((string) $item['item_email']));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $report['notes'][] = L_('MOD_LISTINGS:IMPORT_BAD_EMAIL', $title);
                $email = '';
            }
            $created = (int) $item['item_created'];
            $updated = (int) ($item['item_lastedit'] ?: $created);

            if ($target !== null) {
                $slug = Items::uniqueSlug($target['section_id'], $title, 0);
                $database->insertRow('{TP}mod_listings_items', [
                    'section_id'   => $target['section_id'],
                    'page_id'      => $target['page_id'],
                    'title'        => $title,
                    'slug'         => $slug,
                    'status'       => $status,
                    'owner_email'  => $email,
                    'data'         => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'created_at'   => $created > 0 ? $created : $now,
                    'updated_at'   => $updated > 0 ? $updated : $now,
                    'published_at' => $status === 'published' ? ($created > 0 ? $created : $now) : 0,
                    // expired in wbProfiles: hidden here as well, the owner or admin can extend
                    'expires_at'   => $expired ? $now - 1 : 0,
                    'modified_by'  => $target['user_id'],
                ]);
                if ($database->hasError()) {
                    $report['errors'][] = $database->getError();
                    return $report;
                }
                $newId = (int) $database->lastInsertId();
                foreach (array_unique($catIds) as $catId) {
                    $database->insertRow('{TP}mod_listings_item_categories', ['section_id' => $target['section_id'], 'item_id' => $newId, 'cat_id' => $catMap[$catId]]);
                }
                foreach (array_unique($tagLinks[$id] ?? []) as $tagId) {
                    if (isset($tagMap[$tagId])) {
                        $database->insertRow('{TP}mod_listings_item_tags', ['section_id' => $target['section_id'], 'item_id' => $newId, 'tag_id' => $tagMap[$tagId]]);
                    }
                }
                if ($database->hasError()) {
                    $report['errors'][] = $database->getError();
                    return $report;
                }
            }

            // images are encoded again like uploads; a file that cannot be read is noted, not fatal
            $plan = ['new' => [], 'remove' => []];
            foreach ($fields as $f) {
                foreach ($f['files'][$id] ?? [] as $src) {
                    if ($src['path'] === null) {
                        $report['counts']['files_missing']++;
                        continue;
                    }
                    if ($target === null) {
                        $report['counts']['files']++;
                        continue;
                    }
                    [$file, $error] = Files::fromPath($target['section_id'], $f + ['required' => 0], $src['path'], $src['name']);
                    if ($file === null) {
                        $report['notes'][] = $title . ': ' . $error;
                        continue;
                    }
                    self::$written[] = $file;
                    $plan['new'][] = $file;
                    $report['counts']['files']++;
                }
            }
            if ($target !== null && $plan['new'] !== [] && !Files::store($target['section_id'], $newId, $plan)) {
                $report['errors'][] = $database->getError();
                return $report;
            }
            $report['counts']['items']++;
        }
        if ($missingCats !== []) {
            $report['notes'][] = L_('MOD_LISTINGS:IMPORT_MISSING_CATEGORIES', implode(', ', array_keys($missingCats)));
        }

        // ---- what stays behind ----
        if ($report['counts']['files_missing'] > 0) {
            $report['skipped'][] = L_('MOD_LISTINGS:IMPORT_FILES_MISSING', $report['counts']['files_missing'], MEDIA_DIRECTORY . '/' . self::mediaFolder($settings, $source));
        }
        $passwords = count(array_filter($items, static fn ($r) => (string) $r['item_password'] !== ''));
        if ($passwords > 0) {
            $report['skipped'][] = L_('MOD_LISTINGS:IMPORT_SKIP_PASSWORDS', $passwords);
        }
        if (count(array_filter($items, static fn ($r) => (string) ($r['item_header_pic'] ?? '') !== '')) > 0) {
            $report['skipped'][] = L_('MOD_LISTINGS:IMPORT_SKIP_HEADER');
        }
        $report['skipped'][] = L_('MOD_LISTINGS:IMPORT_SKIP_TEMPLATES');
        return $report;
    }

    /**
     * The active pictures (without the thumbnails wbProfiles made) and
     * downloads of the entries, with the file on disk or null when missing.
     *
     * @return array{pictures: array<int, list<array{name: string, path: ?string}>>, files: array<int, list<array{name: string, path: ?string}>>}
     */
    private static function sourceFiles(int $source, array $items, array $settings, string $in, array $itemIds): array
    {
        global $database;
        $result = ['pictures' => [], 'files' => []];
        $catOf = [];
        foreach ($items as $item) {
            $catOf[(int) $item['item_id']] = (int) $item['cat_id'];
        }
        $folder = self::mediaFolder($settings, $source);
        foreach (['pictures' => 'item_pictures', 'files' => 'item_files'] as $kind => $table) {
            if (!$database->tableExists(self::P . $table)) {
                continue;
            }
            $rows = $database->fetchAll(
                'SELECT * FROM `' . self::P . $table . '` WHERE `item_id` IN (' . $in . ') ORDER BY `item_id`, `file_id`',
                $itemIds
            );
            foreach ($rows as $row) {
                if (strtoupper((string) ($row['active'] ?? 'Y')) === 'N' || ($kind === 'pictures' && ($row['thumbfor'] ?? null) !== null)) {
                    continue;
                }
                $name = basename(str_replace('\\', '/', (string) $row['file']));
                $sub = $kind === 'pictures' ? 'profile_pics' : 'profile_files';
                $path = null;
                // with "separate subfolders" wbProfiles stored per category, otherwise directly in the folder
                foreach ([$folder . '/' . $sub . '/cat_' . ($catOf[(int) $row['item_id']] ?? 0) . '/' . $name, $folder . '/' . $name, $folder . '/' . $sub . '/' . $name] as $relative) {
                    $found = $name !== '' ? wbceSafeRelativePath(WB_PATH . MEDIA_DIRECTORY, $relative) : null;
                    if ($found !== null && is_file($found)) {
                        $path = $found;
                        break;
                    }
                }
                $result[$kind][(int) $row['item_id']][] = ['name' => $name, 'path' => $path];
            }
        }
        return $result;
    }

    /** The upload folder of a wbProfiles directory, relative to the media folder. */
    private static function mediaFolder(array $settings, int $source): string
    {
        $folder = trim(str_replace('\\', '/', (string) ($settings['upload_files_folder'] ?? '')), '/');
        return $folder !== '' ? $folder : 'wbProfiles/profile_files_for_section_' . $source;
    }

    private static function emptyReport(): array
    {
        return [
            'form_source' => '',
            'fields'  => [],
            'counts'  => ['items' => 0, 'pending' => 0, 'expired' => 0, 'categories' => 0, 'tags' => 0, 'html' => 0, 'repaired' => 0, 'broken' => 0, 'files' => 0, 'files_missing' => 0],
            'notes'   => [],
            'skipped' => [],
            'errors'  => [],
        ];
    }

    /**
     * $FORMS['regform'] of the directory's template, when the wbProfiles
     * module is still on the server: a custom folder first, then the
     * template, then the module's default.
     *
     * @return list<array<string, mixed>>|null
     */
    private static function formDefinition(array $settings): ?array
    {
        $base = WB_PATH . '/modules/wbProfiles';
        if (!is_dir($base)) {
            return null;
        }
        $candidates = [];
        if (($settings['custom_folder'] ?? '') !== '') {
            $candidates[] = wbceSafeRelativePath($base, $settings['custom_folder'] . '/inc.forms.php');
        }
        if (($settings['template'] ?? '') !== '') {
            $candidates[] = wbceSafeRelativePath($base, 'templates/' . $settings['template'] . '/inc.forms.php');
        }
        $candidates[] = wbceSafeRelativePath($base, 'inc/inc.forms.php');
        foreach (array_filter($candidates) as $file) {
            $forms = (static function (string $file): mixed {
                $FORMS = null;
                try {
                    include $file;
                } catch (\Throwable $e) {
                    return null;
                }
                return $FORMS;
            })($file);
            if (is_array($forms['regform'] ?? null)) {
                return $forms['regform'];
            }
        }
        return null;
    }

    /**
     * The field set: from the form definition where there is one, plus every
     * stored attribute the form does not know (so no value is lost), plus
     * the URL columns and, on request, the public e-mail address.
     * Each field carries a reader: fn(array $item, array $attrs) => value
     *
     * @return list<array<string, mixed>>
     */
    private static function fieldsFrom(?array $form, array $attrs, array $items, callable $text, bool $publicEmail): array
    {
        $fields = [];
        $names = [];
        $add = static function (array $field) use (&$fields, &$names): void {
            $name = self::fieldName($field['name'], $names);
            $names[] = $name;
            $fields[] = $field + ['name' => $name, 'visibility' => 'public', 'in_list' => 0, 'options' => [], 'values' => 0];
            $fields[count($fields) - 1]['name'] = $name;
        };
        $countValues = static function (callable $read) use ($items, $attrs): int {
            $n = 0;
            foreach ($items as $item) {
                $v = $read($item, $attrs[(int) $item['item_id']] ?? []);
                $n += ($v !== null && $v !== '' && $v !== []) ? 1 : 0;
            }
            return $n;
        };

        $known = [];
        foreach ($form ?? [] as $def) {
            $type = (string) ($def['type'] ?? '');
            $source = (string) ($def['name'] ?? '');
            if ($type === 'legend') {
                $label = trim((string) ($def['text'] ?? ''));
                if ($label !== '') {
                    $add(['name' => 'heading_' . $label, 'label' => $label, 'type' => 'heading', 'source' => '', 'read' => static fn () => null]);
                }
                continue;
            }
            if ($source === '' || in_array($source, self::SKIP, true) || in_array($type, ['hidden', 'password', 'submit', 'file', 'infotext'], true)) {
                continue;
            }
            $known[] = $source;
            $choices = [];
            foreach ((array) ($def['options'] ?? []) as $value => $label) {
                $choices[] = ['value' => (string) $value, 'label' => $text((string) $label)];
            }
            $allow = is_string($def['allow'] ?? null) ? $def['allow'] : '';
            $multi = !empty($def['multiple']);
            $newType = match (true) {
                $type === 'textarea'                         => 'textarea',
                $type === 'number' || $allow === 'number' || $allow === 'integer' => 'number',
                $type === 'select'                           => $choices === [] ? 'text' : 'select',
                $type === 'radio'                            => $choices === [] ? 'text' : 'radio',
                $type === 'checkbox' && $multi               => $choices === [] ? 'text' : 'checkboxes',
                $type === 'checkbox'                         => 'boolean',
                $allow === 'email'                           => 'email',
                $allow === 'url'                             => 'url',
                default                                      => 'text',
            };
            // the URL columns of the items table keep their own names and are read from the columns
            $column = self::COLUMN_FIELDS[$source] ?? null;
            $read = $column !== null ? self::columnReader($source) : self::reader($source, $newType, $text);
            $label = trim($text((string) ($def['label'] ?? '')));
            if ($label === '' || preg_match('/^[a-z0-9_]+$/', $label) === 1) {
                // a translation key of the old template ("item_details_1_heading"): the name reads better
                $label = $column['label'] ?? self::labelFromName($source);
            }
            $add([
                'name' => $column['name'] ?? $source, 'label' => $label, 'type' => $column['type'] ?? $newType, 'source' => $source,
                'options' => $choices === [] || !FieldTypes::isChoice($newType) ? [] : ['choices' => $choices],
                'read' => $read, 'values' => $countValues($read),
            ]);
        }

        // attributes the form does not describe (or no form at all): from the data itself
        $stored = [];
        $multiValued = [];
        foreach ($attrs as $byName) {
            foreach ($byName as $name => $values) {
                $stored[$name] = array_merge($stored[$name] ?? [], $values);
                // several rows for one entry: a multiple choice
                $multiValued[$name] = ($multiValued[$name] ?? false) || count($values) > 1;
            }
        }
        foreach ($stored as $source => $values) {
            if (in_array($source, $known, true) || in_array($source, self::SKIP, true)) {
                continue;
            }
            $long = array_filter($values, static fn ($v) => str_contains($v, "\n") || mb_strlen($v) > 255 || $v !== strip_tags($v));
            $type = !empty($multiValued[$source]) ? 'checkboxes' : ($long !== [] ? 'textarea' : 'text');
            $options = [];
            if ($type === 'checkboxes') {
                $distinct = array_values(array_unique(array_map(static fn ($v) => mb_substr($text($v), 0, 100), $values)));
                $options = ['choices' => array_map(static fn ($v) => ['value' => $v, 'label' => $v], $distinct)];
            }
            $read = self::reader($source, $type, $text);
            $add([
                'name' => $source, 'label' => self::labelFromName($source), 'type' => $type, 'source' => $source,
                'options' => $options, 'read' => $read, 'values' => $countValues($read),
            ]);
        }

        // the URL columns of the items table, unless the form had them already
        foreach (self::COLUMN_FIELDS as $column => $def) {
            if (in_array($column, $known, true)) {
                continue;
            }
            $read = self::columnReader($column);
            $n = $countValues($read);
            if ($n > 0) {
                $add($def + ['source' => $column, 'read' => $read, 'values' => $n]);
            }
        }
        // wbProfiles showed the owner's address when item_elink was 'y'
        if ($publicEmail) {
            $read = static fn (array $item) => strtolower((string) ($item['item_elink'] ?? '')) === 'y' && filter_var($item['item_email'], FILTER_VALIDATE_EMAIL) !== false
                ? strtolower((string) $item['item_email']) : null;
            $add(['name' => 'email', 'label' => 'E-Mail', 'type' => 'email', 'source' => 'item_email + item_elink', 'read' => $read, 'values' => $countValues($read)]);
        }
        return $fields;
    }

    private static function columnReader(string $column): callable
    {
        return static fn (array $item) => trim((string) ($item[$column] ?? '')) === '' ? null : trim((string) $item[$column]);
    }

    /** fn(array $item, array $attrs) => the value to store for one field */
    private static function reader(string $source, string $type, callable $text): callable
    {
        return static function (array $item, array $attrs) use ($source, $type, $text) {
            $values = array_map($text, $attrs[$source] ?? []);
            if ($values === []) {
                return null;
            }
            return match ($type) {
                'checkboxes' => array_values(array_unique(array_filter(array_map(static fn ($v) => mb_substr(trim($v), 0, 100), $values), static fn ($v) => $v !== ''))),
                'boolean'    => in_array(strtolower(trim($values[0])), ['1', 'y', 'yes', 'on', 'true'], true),
                'number'     => is_numeric(str_replace(',', '.', trim($values[0]))) ? str_replace(',', '.', trim($values[0])) + 0 : null,
                default      => trim(str_replace(["\r\n", "\r"], "\n", $values[0])),
            };
        };
    }

    /** additional_item_street → street; valid, not reserved and unique within the import. */
    private static function fieldName(string $source, array $taken): string
    {
        $name = strtolower(preg_replace('/^(additional_item_|item_)/', '', $source) ?? $source);
        $name = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower(page_filename($name))), '_');
        if ($name === '' || !ctype_alpha($name[0]) || in_array($name, Fields::reservedNames(), true)) {
            $name = 'f_' . $name;
        }
        $name = substr($name, 0, 36);
        $unique = $name;
        for ($n = 2; in_array($unique, $taken, true); $n++) {
            $unique = $name . '_' . $n;
        }
        return $unique;
    }

    private static function labelFromName(string $source): string
    {
        $label = str_replace('_', ' ', preg_replace('/^(additional_item_|item_)/', '', $source) ?? $source);
        return ucfirst(trim($label));
    }

    private static function uniqueCatSlug(int $sectionId, string $name): string
    {
        global $database;
        $base = Taxonomy::slugify($name);
        $slug = $base;
        for ($n = 2; $database->fetchValue('SELECT `cat_id` FROM `{TP}mod_listings_categories` WHERE `section_id` = ? AND `slug` = ?', [$sectionId, $slug]) !== ''; $n++) {
            $slug = $base . '-' . $n;
        }
        return $slug;
    }

    /**
     * Text that was UTF-8 already and got encoded a second time as if it
     * were Windows-1252 ("SÃ¼ddorf" for "Süddorf", "StraÃŸe" for "Straße");
     * anything else stays as it is. Only a lossless round trip counts, so
     * no character can get lost.
     */
    public static function repairEncoding(string $value): string
    {
        if (preg_match('/[\xC2\xC3\xC5\xCB\xE2][\x80-\xBF]/', $value) !== 1) {
            return $value;
        }
        $decoded = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
        if (!mb_check_encoding($decoded, 'UTF-8') || preg_match('/[\x80-\xFF]/', $decoded) !== 1
            || mb_convert_encoding($decoded, 'UTF-8', 'Windows-1252') !== $value) {
            return $value;
        }
        return $decoded;
    }
}
