<?php
/**
 * Listings – presets: a field set with settings, categories, tags and
 * layouts as JSON, without entries. The module brings some in presets/;
 * the admin exports a directory as preset and loads one into an empty
 * directory. Everything in a preset goes through the same checks as the
 * backend forms, so a file from elsewhere cannot store what the forms refuse.
 *
 * {
 *   "format": "listings-preset", "version": 1,
 *   "name": "…" | {"de": "…", "en": "…"}, "description": …,
 *   "fields": [{"name", "type", "label", "help", "required", "visibility", "in_list",
 *               "editable", "filterable", "choices": [{"value", "label"}], "options": {…}}],
 *   "settings": {"approval": true, "runtime_days": 30, …},
 *   "categories": [{"name", "description", "visible", "children": […]}],
 *   "tags": ["…"],
 *   "layouts": {"list": "…", "detail": "…"}
 * }
 * Every text may be a string or an object by language (de, en, …).
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Presets
{
    public const FORMAT = 'listings-preset';
    public const VERSION = 1;

    /** A preset file larger than this is refused. */
    public const MAX_BYTES = 1048576;

    /** Not part of a preset: a personal address and internal state. */
    private const SETTINGS_NOT_EXPORTED = ['notify_email', 'reminder_run', 'layout_list', 'layout_detail', 'mails', 'form_intro'];

    private const MAX_FIELDS = 100;
    private const MAX_CATEGORIES = 500;
    private const MAX_DEPTH = 5;

    /**
     * The presets in presets/, sorted by name.
     *
     * @return list<array{key: string, name: string, description: string, fields: int, categories: int}>
     */
    public static function builtIn(): array
    {
        $list = [];
        foreach (glob(self::dir() . '/*.json') ?: [] as $file) {
            $key = basename($file, '.json');
            [$preset] = self::parse((string) file_get_contents($file));
            if ($preset === null || preg_match('/^[a-z0-9_-]+$/', $key) !== 1) {
                continue;
            }
            $list[] = [
                'key'         => $key,
                'name'        => self::text($preset['name'] ?? $key),
                'description' => self::text($preset['description'] ?? ''),
                'fields'      => count(array_filter($preset['fields'] ?? [], static fn ($f) => ($f['type'] ?? '') !== 'heading')),
                'categories'  => self::countCategories($preset['categories'] ?? []),
            ];
        }
        usort($list, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        return $list;
    }

    /** A preset of presets/ by its key, or null. */
    public static function find(string $key): ?array
    {
        if (preg_match('/^[a-z0-9_-]+$/', $key) !== 1 || !is_file(self::dir() . '/' . $key . '.json')) {
            return null;
        }
        return self::parse((string) file_get_contents(self::dir() . '/' . $key . '.json'))[0];
    }

    /**
     * Reads a preset; only the outer form is checked here, apply() checks every value.
     *
     * @return array{0: ?array<string, mixed>, 1: ?string} [preset, error]
     */
    public static function parse(string $json): array
    {
        if (strlen($json) > self::MAX_BYTES) {
            return [null, L_('MOD_LISTINGS:ERR_PRESET_SIZE')];
        }
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;
        try {
            $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [null, L_('MOD_LISTINGS:ERR_PRESET_JSON')];
        }
        if (!is_array($data) || ($data['format'] ?? '') !== self::FORMAT || !is_array($data['fields'] ?? null)) {
            return [null, L_('MOD_LISTINGS:ERR_PRESET_FORMAT')];
        }
        if ((int) ($data['version'] ?? 0) > self::VERSION) {
            return [null, L_('MOD_LISTINGS:ERR_PRESET_VERSION')];
        }
        return [$data, null];
    }

    /**
     * The directory as preset: field set, settings, categories, the admin's
     * tags and edited layouts. No entries, no owner addresses.
     *
     * @return array<string, mixed>
     */
    public static function export(int $sectionId, string $name): array
    {
        $fields = [];
        foreach (Fields::forSection($sectionId) as $f) {
            $field = [
                'name'       => $f['name'],
                'type'       => $f['type'],
                'label'      => $f['label'],
            ];
            if ($f['help'] !== '') {
                $field['help'] = $f['help'];
            }
            if ($f['type'] !== 'heading') {
                $field += [
                    'required'   => (bool) $f['required'],
                    'visibility' => $f['visibility'],
                    'in_list'    => (bool) $f['in_list'],
                    'editable'   => (bool) $f['editable'],
                    'filterable' => (bool) $f['filterable'],
                ];
            }
            $options = $f['options'];
            if (isset($options['choices'])) {
                $field['choices'] = $options['choices'];
                unset($options['choices']);
            }
            if ($options !== []) {
                $field['options'] = $options;
            }
            $fields[] = $field;
        }

        $all = Settings::load($sectionId);
        $settings = [];
        foreach (array_diff_key($all, array_flip(self::SETTINGS_NOT_EXPORTED)) as $key => $value) {
            $settings[$key] = in_array($key, Settings::FLAGS, true) ? $value === '1'
                : (in_array($key, ['per_page', 'runtime_days', 'remind_days', 'max_per_owner'], true) ? (int) $value : $value);
        }

        $tree = Taxonomy::categories($sectionId);
        $build = static function (int $parent) use (&$build, $tree): array {
            $list = [];
            foreach ($tree as $cat) {
                if ($cat['parent_id'] !== $parent) {
                    continue;
                }
                $node = ['name' => $cat['name']];
                if ((string) $cat['description'] !== '') {
                    $node['description'] = $cat['description'];
                }
                if (!$cat['visible']) {
                    $node['visible'] = false;
                }
                $children = $build($cat['cat_id']);
                if ($children !== []) {
                    $node['children'] = $children;
                }
                $list[] = $node;
            }
            return $list;
        };

        $preset = [
            'format'   => self::FORMAT,
            'version'  => self::VERSION,
            'name'     => $name,
            'exported' => date('c'),
            'fields'   => $fields,
            'settings' => $settings,
        ];
        $categories = $build(0);
        if ($categories !== []) {
            $preset['categories'] = $categories;
        }
        if ($all['tags_mode'] === 'fixed') {
            $preset['tags'] = array_column(Taxonomy::tags($sectionId), 'name');
        }
        foreach (Layouts::NAMES as $layout) {
            if (trim($all['layout_' . $layout]) !== '') {
                $preset['layouts'][$layout] = $all['layout_' . $layout];
            }
        }
        // the directory's own texts: above the form, and mail templates that differ from the defaults
        if ($all['form_intro'] !== '') {
            $preset['layouts']['form_intro'] = $all['form_intro'];
        }
        $mails = json_decode($all['mails'], true);
        if (is_array($mails) && $mails !== []) {
            $preset['mails'] = $mails;
        }
        return $preset;
    }

    /** Nothing in the directory yet: a preset never merges into an existing field set. */
    public static function targetEmpty(int $sectionId): bool
    {
        return Import::targetEmpty($sectionId);
    }

    /**
     * Writes the preset into an empty directory: all or nothing.
     *
     * @return list<string> errors; empty when it worked
     */
    public static function apply(int $sectionId, array $preset): array
    {
        if (!self::targetEmpty($sectionId)) {
            return [L_('MOD_LISTINGS:ERR_PRESET_NOT_EMPTY')];
        }
        $errors = [];
        try {
            $GLOBALS['database']->transaction(function () use ($sectionId, $preset, &$errors) {
                $errors = self::write($sectionId, $preset);
                if ($errors !== []) {
                    throw new \RuntimeException('rolled back');
                }
            });
        } catch (\Throwable $e) {
            if ($errors === []) {
                $errors[] = L_('MOD_LISTINGS:ERR_PRESET_FAILED', $e->getMessage());
            }
        }
        return $errors;
    }

    /** @return list<string> */
    private static function write(int $sectionId, array $preset): array
    {
        global $database;
        $errors = [];

        // ---- fields: the same check as the field form ----
        $fields = array_values(array_filter($preset['fields'] ?? [], 'is_array'));
        if (count($fields) > self::MAX_FIELDS) {
            return [L_('MOD_LISTINGS:ERR_PRESET_TOO_MANY', self::MAX_FIELDS)];
        }
        foreach ($fields as $i => $f) {
            $choices = [];
            foreach (is_array($f['choices'] ?? null) ? $f['choices'] : [] as $choice) {
                $value = is_array($choice) ? (string) ($choice['value'] ?? '') : (string) $choice;
                $label = is_array($choice) ? self::text($choice['label'] ?? $value) : $value;
                $choices[] = str_replace(["\r", "\n", '|'], ' ', $value) . '|' . str_replace(["\r", "\n"], ' ', $label);
            }
            $options = is_array($f['options'] ?? null) ? $f['options'] : [];
            $input = [
                'name'       => (string) ($f['name'] ?? ''),
                'type'       => (string) ($f['type'] ?? ''),
                'label'      => self::text($f['label'] ?? ''),
                'help'       => self::text($f['help'] ?? ''),
                'visibility' => (string) ($f['visibility'] ?? 'public'),
                'choices'    => implode("\n", $choices),
            ];
            foreach (['required' => false, 'in_list' => false, 'editable' => true, 'filterable' => false] as $flag => $default) {
                $input[$flag] = ($f[$flag] ?? $default) ? '1' : '';
            }
            foreach (['maxlength', 'min', 'max', 'max_files', 'max_mb', 'max_px', 'extensions'] as $key) {
                $input[$key] = is_scalar($options[$key] ?? null) ? (string) $options[$key] : '';
            }
            [$data, $fieldErrors] = Fields::check($sectionId, $input, null, false);
            if ($fieldErrors !== []) {
                $label = $input['label'] !== '' ? $input['label'] : '#' . ($i + 1);
                foreach ($fieldErrors as $error) {
                    $errors[] = $label . ': ' . $error;
                }
                continue;
            }
            if (Fields::save($sectionId, null, $data) === null) {
                return [$database->getError()];
            }
        }
        if ($errors !== []) {
            return $errors;
        }

        // ---- settings: on top of the defaults, through the settings check ----
        $given = is_array($preset['settings'] ?? null) ? $preset['settings'] : [];
        [$values, $settingErrors] = Settings::check($given + Settings::DEFAULTS);
        if ($settingErrors !== []) {
            return $settingErrors;
        }
        foreach (Layouts::NAMES as $layout) {
            $text = $preset['layouts'][$layout] ?? '';
            $values['layout_' . $layout] = is_string($text) ? Layouts::normalise($layout, $text) : '';
        }
        $intro = $preset['layouts']['form_intro'] ?? '';
        $values['form_intro'] = is_string($intro) ? mb_substr(trim(str_replace(["\r\n", "\r"], "\n", $intro)), 0, Settings::FORM_INTRO_MAX) : '';
        // mail templates through the same check as the layouts form
        $input = [];
        foreach (is_array($preset['mails'] ?? null) ? $preset['mails'] : [] as $kind => $mail) {
            if (is_string($kind) && is_array($mail)) {
                $input['mail_' . $kind . '_subject'] = self::text($mail['subject'] ?? '');
                $input['mail_' . $kind . '_text'] = self::text($mail['text'] ?? '');
            }
        }
        [$values['mails'], $mailErrors] = Mail::checkTemplates($input, Mail::language($sectionId));
        if ($mailErrors !== []) {
            return $mailErrors;
        }
        $error = Settings::save($sectionId, $values);
        if ($error !== null) {
            return [$error];
        }

        // ---- categories, parents first ----
        if (self::countCategories($preset['categories'] ?? []) > self::MAX_CATEGORIES) {
            return [L_('MOD_LISTINGS:ERR_PRESET_TOO_MANY_CATS', self::MAX_CATEGORIES)];
        }
        $add = static function (array $list, int $parent, int $depth) use (&$add, $sectionId, &$errors): void {
            foreach ($list as $node) {
                if (!is_array($node) || $depth > self::MAX_DEPTH) {
                    continue;
                }
                [$data, $catErrors] = Taxonomy::checkCategory($sectionId, [
                    'name'        => self::text($node['name'] ?? ''),
                    'slug'        => is_string($node['slug'] ?? null) ? $node['slug'] : '',
                    'parent_id'   => (string) $parent,
                    'description' => self::text($node['description'] ?? ''),
                    'visible'     => ($node['visible'] ?? true) ? '1' : '0',
                ], null);
                if ($catErrors !== []) {
                    array_push($errors, ...$catErrors);
                    continue;
                }
                $id = Taxonomy::saveCategory($sectionId, null, $data);
                if ($id === null) {
                    $errors[] = $GLOBALS['database']->getError();
                    return;
                }
                $add(is_array($node['children'] ?? null) ? $node['children'] : [], $id, $depth + 1);
            }
        };
        $add(is_array($preset['categories'] ?? null) ? $preset['categories'] : [], 0, 1);
        if ($errors !== []) {
            return $errors;
        }

        // ---- the admin's tags ----
        $names = [];
        foreach (is_array($preset['tags'] ?? null) ? $preset['tags'] : [] as $tag) {
            $name = mb_substr(trim(preg_replace('/\s+/u', ' ', self::text($tag)) ?? ''), 0, 100);
            if ($name !== '') {
                $names[] = $name;
            }
        }
        if ($names !== [] && count(Taxonomy::tagIds($sectionId, array_slice(array_unique($names), 0, 500), true)) === 0) {
            return [$database->getError()];
        }
        return [];
    }

    /**
     * A text of the preset in the backend language: a string, or an
     * object by language code with English and then the first as fallback.
     */
    public static function text(mixed $value): string
    {
        if (is_array($value)) {
            $lang = strtolower(defined('LANGUAGE') ? (string) LANGUAGE : 'en');
            $value = $value[$lang] ?? $value['en'] ?? (reset($value) ?: '');
        }
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /** A safe file name for the download. */
    public static function fileName(string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(page_filename($name))), '-');
        return 'listings-' . ($base !== '' ? $base : 'preset') . '.json';
    }

    private static function countCategories(mixed $list, int $depth = 1): int
    {
        if (!is_array($list) || $depth > self::MAX_DEPTH + 1) {
            return 0;
        }
        $n = 0;
        foreach ($list as $node) {
            if (is_array($node)) {
                $n += 1 + self::countCategories($node['children'] ?? [], $depth + 1);
            }
        }
        return $n;
    }

    private static function dir(): string
    {
        return dirname(__DIR__) . '/presets';
    }
}
