<?php
/**
 * Listings – backend of a section: entries, fields, layouts, settings.
 * Views are the modify_*.twig templates in twig/; every form posts to save.php.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Backend
{
    public const VIEWS = ['items', 'item', 'fields', 'field', 'categories', 'category', 'layouts', 'settings', 'import', 'presets', 'log'];

    public const ITEMS_PER_PAGE = 50;

    public const LOG_PER_PAGE = 100;

    /** Entries waiting for approval on top, then the last changed. */
    private const ORDER_WAITING_FIRST = "CASE WHEN `status` = 'pending' THEN 0 ELSE 1 END, `updated_at` DESC, `item_id` DESC";

    public static function render(\Admin $admin, int $pageId, int $sectionId): string
    {
        // URL parameters only count for the section they were meant for
        $own = (int) ($_GET['lst_section'] ?? 0) === $sectionId;
        $param = static fn (string $name): string => $own && is_string($_GET[$name] ?? null) ? $_GET[$name] : '';
        $view = in_array($param('lst_view'), self::VIEWS, true) ? $param('lst_view') : 'items';

        // WBCE has no cron: unconfirmed frontend entries are also removed on backend calls
        Items::purgeUnconfirmed($sectionId);
        Log::purge($sectionId);
        Reminders::run($sectionId, Settings::load($sectionId));

        $flash = Module::takeFlash($sectionId);
        $vars = [
            'page_id'    => $pageId,
            'section_id' => $sectionId,
            'view'       => $view,
            // the IDs in the URL as well: a POST above post_max_size arrives without its fields (save.php)
            'action'     => Module::saveUrl() . '?' . http_build_query(['page_id' => $pageId, 'section_id' => $sectionId], '', '&'),
            'errors'     => array_merge(
                is_array($flash['errors'] ?? null) ? $flash['errors'] : [],
                $param('lst_error') === 'post_too_large' ? [L_('MOD_LISTINGS:ERR_POST_SIZE', Files::formatSize(Files::maxPostBytes()))] : []
            ),
            'old'        => is_array($flash['old'] ?? null) ? $flash['old'] : null,
            'now'        => time(),
            // shown at the tab "Entries" in every view
            'waiting'    => Items::count($sectionId, 'pending'),
            // docs/ in the markdown reader of WBCE (core module MarkdownWbce), tabs from docs/md_reader.json
            'help_link'  => class_exists('MdReaderLink')
                ? \MdReaderLink::dir('/modules/' . Module::DIR . '/docs/')->title('Listings')->linkHtml(L_('MOD_LISTINGS:HELP'), 'lst-button')
                : '',
        ];

        $vars['fields'] = Fields::forSection($sectionId);

        $item = null;
        if ($view === 'item' && (int) $param('lst_item') > 0) {
            $item = Items::find($sectionId, (int) $param('lst_item'));
            if ($item === null) {
                $vars['errors'][] = L_('MOD_LISTINGS:ERR_NOT_FOUND');
                $view = 'items';
            }
        }
        $vars['view'] = $view;

        switch ($view) {
            case 'items':
                $status = in_array($param('lst_status'), Items::STATUSES, true) ? $param('lst_status') : null;
                $total = Items::count($sectionId, $status);
                $pages = max(1, (int) ceil($total / self::ITEMS_PER_PAGE));
                $page = max(1, min($pages, (int) $param('lst_page')));
                $vars += [
                    'status' => $status ?? '',
                    'total'  => $total,
                    'page'   => $page,
                    'pages'  => $pages,
                    'items'  => Items::list($sectionId, $status, false, self::ORDER_WAITING_FIRST, self::ITEMS_PER_PAGE, ($page - 1) * self::ITEMS_PER_PAGE),
                    'counts' => ['' => Items::count($sectionId)],
                ];
                foreach (Items::STATUSES as $countStatus) {
                    $vars['counts'][$countStatus] = Items::count($sectionId, $countStatus);
                }
                break;

            case 'item':
                $vars['item'] = $item;
                $vars['fields'] = Files::withLimits($vars['fields']);
                $vars['files'] = [];
                if ($item !== null) {
                    // the admin sees the files also while the entry is not public
                    Files::grant($item['item_id']);
                    $vars['files'] = array_map([Files::class, 'forForm'], Files::forItem($sectionId, $item['item_id']));
                }
                $vars['history'] = $item === null ? [] : Log::forSection($sectionId, 20, 0, $item['item_id']);
                // HTML fields get the editor of the site; its result is cleaned again on saving
                $values = $vars['old']['fields'] ?? $item['data'] ?? [];
                foreach ($vars['fields'] as &$field) {
                    if ($field['type'] === 'html' && class_exists('WysiwygEditor')) {
                        $value = is_string($values[$field['name']] ?? null) ? $values[$field['name']] : '';
                        $field['editor'] = \WysiwygEditor::render('lst_f_' . $field['name'], Html::clean($value), [
                            'name'   => 'fields[' . $field['name'] . ']',
                            'height' => '260',
                            'config' => 'small>default',
                        ]);
                    }
                }
                unset($field);
                $vars['max_post'] = Files::maxPostBytes();
                $vars['max_post_text'] = Files::formatSize(Files::maxPostBytes());
                $vars['statuses'] = Items::STATUSES;
                $vars['form'] = $vars['old'] ?? [
                    'title'   => $item['title'] ?? '',
                    'slug'    => $item['slug'] ?? '',
                    'status'  => $item['status'] ?? 'draft',
                    'owner_email' => $item['owner_email'] ?? '',
                    'expires' => ($item['expires_at'] ?? 0) > 0 ? date('Y-m-d', $item['expires_at']) : '',
                    'fields'  => $item['data'] ?? [],
                ];
                $assigned = $item === null ? ['categories' => [], 'tags' => []] : Taxonomy::forItems($sectionId, [$item['item_id']])[$item['item_id']];
                $vars['form'] += [
                    'categories' => array_column($assigned['categories'], 'cat_id'),
                    'tags'       => implode(', ', array_column($assigned['tags'], 'name')),
                ];
                $vars['categories'] = Taxonomy::categories($sectionId);
                $vars['tag_names'] = array_column(Taxonomy::tags($sectionId), 'name');
                break;

            case 'categories':
                $counts = Taxonomy::categoryCounts($sectionId);
                $vars['categories'] = array_map(static fn ($c) => $c + ['count' => $counts[$c['cat_id']] ?? 0], Taxonomy::categories($sectionId));
                $vars['tags'] = Taxonomy::tags($sectionId);
                $vars['tags_mode'] = Settings::load($sectionId)['tags_mode'];
                break;

            case 'category':
                $cat = (int) $param('lst_cat') > 0 ? Taxonomy::findCategory($sectionId, (int) $param('lst_cat')) : null;
                $tree = Taxonomy::categories($sectionId);
                // not below itself or its own subcategories
                $exclude = $cat === null ? [] : Taxonomy::withDescendants($tree, $cat['cat_id']);
                $vars['category'] = $cat;
                $vars['parents'] = array_values(array_filter($tree, static fn ($c) => !in_array($c['cat_id'], $exclude, true)));
                $vars['form'] = $vars['old'] ?? [
                    'name'        => $cat['name'] ?? '',
                    'slug'        => $cat['slug'] ?? '',
                    'parent_id'   => (string) ($cat['parent_id'] ?? (int) $param('lst_parent')),
                    'description' => $cat['description'] ?? '',
                    'visible'     => (string) ($cat['visible'] ?? 1),
                ];
                break;

            case 'field':
                $fieldId = (int) $param('lst_field');
                $field = $fieldId > 0 ? Fields::find($sectionId, $fieldId) : null;
                $vars['field'] = $field;
                $vars['name_locked'] = $field !== null && Items::count($sectionId) > 0;
                $vars['types'] = FieldTypes::TYPES;
                $vars['visibilities'] = Fields::VISIBILITY;
                $vars['form'] = $vars['old'] ?? [
                    'label'      => $field['label'] ?? '',
                    'name'       => $field['name'] ?? '',
                    'type'       => $field['type'] ?? 'text',
                    'help'       => $field['help'] ?? '',
                    'required'   => (string) ($field['required'] ?? 0),
                    'visibility' => $field['visibility'] ?? 'public',
                    'in_list'    => (string) ($field['in_list'] ?? 0),
                    'editable'   => (string) ($field['editable'] ?? 1),
                    'filterable' => (string) ($field['filterable'] ?? 0),
                    'choices'    => $field === null ? '' : Fields::choicesText($field),
                    'maxlength'  => (string) ($field['options']['maxlength'] ?? ''),
                    'min'        => (string) ($field['options']['min'] ?? ''),
                    'max'        => (string) ($field['options']['max'] ?? ''),
                    'max_files'  => (string) ($field['options']['max_files'] ?? ''),
                    'max_mb'     => (string) ($field['options']['max_mb'] ?? ''),
                    'max_px'     => (string) ($field['options']['max_px'] ?? ''),
                    'extensions' => (string) ($field['options']['extensions'] ?? ''),
                ];
                $vars['images_available'] = Files::imagesAvailable();
                $vars['max_files_limit'] = Files::MAX_FILES_LIMIT;
                $vars['max_px_limit'] = Files::MAX_PX_LIMIT;
                $vars['default_max_files'] = Files::DEFAULT_MAX_FILES;
                $vars['default_max_mb'] = Files::DEFAULT_MAX_MB;
                $vars['default_max_px'] = Files::DEFAULT_MAX_PX;
                $vars['default_extensions'] = Files::DEFAULT_EXTENSIONS;
                $ini = Files::maxBytes(['type' => 'file', 'options' => ['max_mb' => 100000]]);
                $vars['upload_limit'] = Files::formatSize($ini);
                break;

            case 'layouts':
                $settings = Settings::load($sectionId);
                $vars['layouts'] = [];
                $vars['custom'] = [];
                foreach (Layouts::NAMES as $name) {
                    $vars['layouts'][$name] = $vars['old']['layout_' . $name] ?? Layouts::get($settings, $name);
                    $vars['custom'][$name] = trim($settings['layout_' . $name]) !== '';
                    // syntax highlighting from the core module CodeMirror_Config (as in articles), plain textareas without it
                    if (class_exists('CodeEditor')) {
                        \CodeEditor::init('lst-layout-' . $name . '-' . $sectionId, 'htt', ['height' => 'auto', 'toolbar' => false]);
                    }
                }
                $vars['form_intro'] = $vars['old']['form_intro'] ?? $settings['form_intro'];
                $vars['form_intro_max'] = Settings::FORM_INTRO_MAX;
                $language = Mail::language($sectionId);
                $vars['mails'] = [];
                foreach (array_keys(Mail::KINDS) as $kind) {
                    $template = Mail::template($settings, $kind, $language);
                    $sent = $vars['old']['mails_sent'][$kind] ?? null;
                    $vars['mails'][$kind] = [
                        'subject'      => $sent['subject'] ?? $template['subject'],
                        'text'         => $sent['text'] ?? $template['text'],
                        'custom'       => $template['custom'],
                        'placeholders' => Mail::KINDS[$kind]['placeholders'],
                        'required'     => Mail::KINDS[$kind]['required'],
                        'sample'       => Mail::sample($sectionId, $kind),
                    ];
                }
                $vars['field_keys'] = array_values(array_map(
                    static fn ($f) => strtoupper($f['name']),
                    // layouts receive the public fields only
                    array_filter($vars['fields'], static fn ($f) => FieldTypes::stores($f['type']) && $f['visibility'] === 'public')
                ));
                break;

            case 'settings':
                $vars['form'] = $vars['old'] ?? Settings::load($sectionId);
                $vars['sorts'] = array_keys(Settings::SORTS);
                $vars['per_page_max'] = Settings::PER_PAGE_MAX;
                $vars['captcha_available'] = class_exists('Captcha');
                $vars['owner_modes'] = Settings::OWNER_MODES;
                $vars['login_available'] = defined('FRONTEND_LOGIN') && FRONTEND_LOGIN;
                $vars['runtime_max'] = Settings::RUNTIME_MAX;
                $vars['max_per_owner_limit'] = Settings::MAX_PER_OWNER_LIMIT;
                $vars['tag_modes'] = Taxonomy::TAG_MODES;
                $vars['rss_url'] = WB_URL . '/modules/' . Module::DIR . '/rss.php?section_id=' . $sectionId;
                $vars['import_available'] = Import::available();
                break;

            case 'log':
                $total = Log::count($sectionId);
                $pages = max(1, (int) ceil($total / self::LOG_PER_PAGE));
                $page = max(1, min($pages, (int) $param('lst_page')));
                $vars['entries'] = Log::forSection($sectionId, self::LOG_PER_PAGE, ($page - 1) * self::LOG_PER_PAGE);
                $vars['total'] = $total;
                $vars['page'] = $page;
                $vars['pages'] = $pages;
                $vars['keep_days'] = Log::KEEP_DAYS;
                // links only to entries that still exist
                $ids = array_values(array_unique(array_filter(array_column($vars['entries'], 'item_id'))));
                $vars['existing'] = [];
                $vars['titles'] = [];
                foreach ($ids as $id) {
                    $found = Items::find($sectionId, $id);
                    if ($found !== null) {
                        $vars['existing'][] = $id;
                        $vars['titles'][$id] = $found['title'];
                    }
                }
                break;

            case 'presets':
                $vars['presets'] = Presets::builtIn();
                $vars['target_empty'] = Presets::targetEmpty($sectionId);
                $vars['page_title'] = (string) $GLOBALS['database']->fetchValue('SELECT `page_title` FROM `{TP}pages` WHERE `page_id` = ?', [$pageId]);
                break;

            case 'import':
                // the dry run is a GET view: it only reads
                $source = (int) $param('lst_source');
                $options = [
                    'fix_encoding' => $param('lst_source') === '' || $param('lst_fix') === '1',
                    'public_email' => $param('lst_source') === '' || $param('lst_email') === '1',
                ];
                $vars['sources'] = Import::sources();
                $vars['source'] = $source;
                $vars['options'] = $options;
                $vars['target_empty'] = Import::targetEmpty($sectionId);
                $vars['modify_action'] = ADMIN_URL . '/pages/modify.php';
                $vars['report'] = $source > 0 && in_array($source, array_column($vars['sources'], 'section_id'), true)
                    ? Import::plan($source, $options) : null;
                $vars['result'] = is_array($flash['report'] ?? null) ? $flash['report'] : null;
                break;
        }

        unset($vars['old']);
        $twig = Module::twig([
            'url'   => static fn (array $params = []): string => Module::modifyUrl($pageId, $sectionId, $params),
            'idkey' => static fn (string $purpose, int $id): string => Module::idKey($admin, $purpose, $id),
            'idkey_list' => static fn (string $purpose, iterable $ids): string => Module::idKeyList($admin, $purpose, array_values([...$ids])),
        ]);
        return $twig->render('modify_' . $view . '.twig', $vars);
    }
}
