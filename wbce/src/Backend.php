<?php
/**
 * Listings – backend of a section: entries, fields, layouts, settings.
 * Views are PHP templates in backend/; every form posts to save.php.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Backend
{
    public const VIEWS = ['items', 'item', 'fields', 'field', 'layouts', 'settings'];

    public const ITEMS_PER_PAGE = 50;

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

        $flash = Module::takeFlash($sectionId);
        $vars = [
            'admin'      => $admin,
            'page_id'    => $pageId,
            'section_id' => $sectionId,
            'view'       => $view,
            'action'     => Module::saveUrl(),
            'errors'     => is_array($flash['errors'] ?? null) ? $flash['errors'] : [],
            'old'        => is_array($flash['old'] ?? null) ? $flash['old'] : null,
            'url'        => static fn (array $params = []) => Module::modifyUrl($pageId, $sectionId, $params),
            'hidden'     => '<input type="hidden" name="page_id" value="' . $pageId . '">'
                          . '<input type="hidden" name="section_id" value="' . $sectionId . '">'
                          . $admin->getFTAN(),
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
                    'status' => $status,
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
                $vars['form'] = $vars['old'] ?? [
                    'title'   => $item['title'] ?? '',
                    'slug'    => $item['slug'] ?? '',
                    'status'  => $item['status'] ?? 'draft',
                    'owner_email' => $item['owner_email'] ?? '',
                    'expires' => ($item['expires_at'] ?? 0) > 0 ? date('Y-m-d', $item['expires_at']) : '',
                    'fields'  => $item['data'] ?? [],
                ];
                break;

            case 'field':
                $fieldId = (int) $param('lst_field');
                $field = $fieldId > 0 ? Fields::find($sectionId, $fieldId) : null;
                $vars['field'] = $field;
                $vars['name_locked'] = $field !== null && Items::count($sectionId) > 0;
                $vars['form'] = $vars['old'] ?? [
                    'label'      => $field['label'] ?? '',
                    'name'       => $field['name'] ?? '',
                    'type'       => $field['type'] ?? 'text',
                    'help'       => $field['help'] ?? '',
                    'required'   => (string) ($field['required'] ?? 0),
                    'visibility' => $field['visibility'] ?? 'public',
                    'in_list'    => (string) ($field['in_list'] ?? 0),
                    'editable'   => (string) ($field['editable'] ?? 1),
                    'choices'    => $field === null ? '' : Fields::choicesText($field),
                    'maxlength'  => (string) ($field['options']['maxlength'] ?? ''),
                    'min'        => (string) ($field['options']['min'] ?? ''),
                    'max'        => (string) ($field['options']['max'] ?? ''),
                ];
                break;

            case 'layouts':
                $settings = Settings::load($sectionId);
                $vars['layouts'] = [];
                $vars['custom'] = [];
                foreach (Layouts::NAMES as $name) {
                    $vars['layouts'][$name] = $vars['old']['layout_' . $name] ?? Layouts::get($settings, $name);
                    $vars['custom'][$name] = trim($settings['layout_' . $name]) !== '';
                }
                break;

            case 'settings':
                $vars['form'] = $vars['old'] ?? Settings::load($sectionId);
                break;
        }

        return self::template('nav', $vars) . self::template($view, $vars);
    }

    /**
     * Renders backend/<name>.php with $v as its only variable.
     *
     * @param array<string, mixed> $v
     */
    private static function template(string $name, array $v): string
    {
        ob_start();
        include dirname(__DIR__) . '/backend/' . $name . '.php';
        return (string) ob_get_clean();
    }
}
