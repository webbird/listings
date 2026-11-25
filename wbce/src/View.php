<?php
/**
 * Listings – frontend: the list of entries, one entry (?item=<slug>), and
 * with frontend entries switched on the owner's forms and mailed links.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class View
{
    /** Request parameters; the page number is per section, so two directories on a page page separately. */
    public const PARAM_ITEM = 'item';
    public const PARAM_PAGE = 'lst_p';
    public const PARAM_FORM = 'lst_form';      // new | link, with PARAM_SECTION
    public const PARAM_SECTION = 'lst_s';
    public const PARAM_CONFIRM = 'lst_confirm';
    public const PARAM_EDIT = 'lst_edit';

    public static function render(int $sectionId, int $pageId): string
    {
        // WBCE has no cron: unconfirmed entries and old links are removed on page views
        Items::purgeUnconfirmed($sectionId);
        Tokens::purge();

        $settings = Settings::load($sectionId);
        $baseUrl = page_link($pageId);
        $frontend = Settings::on($settings, 'frontend');
        $flash = $frontend ? Module::takeFlash($sectionId, 'fe') : [];

        if ($frontend) {
            $html = self::ownerView($sectionId, $settings, $baseUrl, $flash);
            if ($html !== null) {
                return $html;
            }
        }

        // a message for the list: success, or the error of a request that came back here
        $message = '';
        if (isset($flash['success'])) {
            $message = self::template('message', ['text' => $flash['success'], 'type' => 'success']);
        } elseif (is_array($flash['errors'] ?? null) && $flash['errors'] !== []) {
            $message = self::template('message', ['text' => implode(' ', $flash['errors']), 'type' => 'error']);
        }
        return $message . self::publicView($sectionId, $pageId, $settings, $baseUrl, $frontend);
    }

    /**
     * Forms and mailed links of owners; null when the request is not about them.
     */
    private static function ownerView(int $sectionId, array $settings, string $baseUrl, array $flash): ?string
    {
        $get = static fn (string $name): string => is_string($_GET[$name] ?? null) ? $_GET[$name] : '';
        $errors = is_array($flash['errors'] ?? null) ? $flash['errors'] : [];
        $old = is_array($flash['old'] ?? null) ? $flash['old'] : null;
        $vars = [
            'section_id' => $sectionId,
            'action'     => WB_URL . '/modules/' . Module::DIR . '/submit.php',
            'list_url'   => $baseUrl,
            'link_url'   => self::formUrl($baseUrl, $sectionId, 'link'),
            'errors'     => $errors,
            'success'    => (string) ($flash['success'] ?? ''),
            'hidden'     => '<input type="hidden" name="section_id" value="' . $sectionId . '">' . self::ftan(),
        ];

        // links from mails: they name their section through the token
        if ($get(self::PARAM_CONFIRM) !== '') {
            $token = Tokens::find($get(self::PARAM_CONFIRM), Tokens::CONFIRM);
            if ($token !== null && $token['section_id'] !== $sectionId) {
                return null;
            }
            $item = $token === null ? null : Items::find($sectionId, $token['item_id']);
            if ($item === null || $item['status'] !== 'draft') {
                return self::template('message', ['text' => L_('MOD_LISTINGS:LINK_INVALID'), 'type' => 'error', 'list_url' => $baseUrl, 'link_url' => $vars['link_url']]);
            }
            return self::template('confirm', $vars + ['token' => $get(self::PARAM_CONFIRM)]);
        }

        if ($get(self::PARAM_EDIT) !== '') {
            $token = Tokens::find($get(self::PARAM_EDIT), Tokens::EDIT);
            if ($token !== null && $token['section_id'] !== $sectionId) {
                return null;
            }
            $item = $token === null ? null : Items::find($sectionId, $token['item_id']);
            if ($item === null) {
                return self::template('message', ['text' => L_('MOD_LISTINGS:LINK_INVALID'), 'type' => 'error', 'list_url' => $baseUrl, 'link_url' => $vars['link_url']]);
            }
            return self::template('form', $vars + [
                'mode'   => 'edit',
                'token'  => $get(self::PARAM_EDIT),
                'item'   => $item,
                'fields' => Fields::forOwner(Fields::forSection($sectionId)),
                'values' => $old ?? ['title' => $item['title'], 'fields' => $item['data']],
                'captcha' => '',
            ]);
        }

        if ((int) $get(self::PARAM_SECTION) !== $sectionId) {
            return null;
        }
        $form = $get(self::PARAM_FORM);
        if ($form !== 'new' && $form !== 'link') {
            return null;
        }
        // bots send at once; Submission refuses forms sent within seconds of being shown
        $_SESSION['mod_listings_form_time'][$sectionId] = time();
        $vars['captcha'] = Settings::on($settings, 'captcha') ? self::captcha($sectionId) : '';
        if ($form === 'link') {
            return self::template('link', $vars + ['values' => $old ?? []]);
        }
        return self::template('form', $vars + [
            'mode'   => 'new',
            'token'  => '',
            'item'   => null,
            'fields' => Fields::forOwner(Fields::forSection($sectionId)),
            'values' => $old ?? [],
            'approval' => Settings::on($settings, 'approval'),
        ]);
    }

    private static function publicView(int $sectionId, int $pageId, array $settings, string $baseUrl, bool $frontend): string
    {
        $fields = array_values(array_filter(
            Fields::forSection($sectionId),
            static fn ($f) => $f['visibility'] === 'public' && FieldTypes::stores($f['type'])
        ));
        $parser = Layouts::parser();

        $slug = is_string($_GET[self::PARAM_ITEM] ?? null) ? trim($_GET[self::PARAM_ITEM]) : '';
        if ($slug !== '') {
            $item = Items::findVisible($sectionId, $slug);
            if ($item !== null) {
                \I::insertTitle($item['title'] . ' - ' . WEBSITE_TITLE);
                return $parser->parse(Layouts::get($settings, 'detail'), [
                    'ITEM'       => self::itemData($item, $fields, $baseUrl, false),
                    'LIST_URL'   => $baseUrl,
                    'SECTION_ID' => $sectionId,
                ]);
            }
            // an unknown or hidden entry in this section; another section on the page may own it
            if (!self::slugBelongsElsewhere($sectionId, $pageId, $slug)) {
                http_response_code(404);
            }
        }

        $perPage = Settings::perPage($settings);
        $total = Items::count($sectionId, null, true);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = self::currentPage($sectionId, $pages);
        $items = Items::list($sectionId, null, true, Settings::orderBy($settings), $perPage, ($page - 1) * $perPage);

        return $parser->parse(Layouts::get($settings, 'list'), [
            'ITEMS'      => array_map(static fn ($i) => self::itemData($i, $fields, $baseUrl, true), $items),
            'COUNT'      => $total,
            'PAGE'       => $page,
            'PAGES'      => $pages,
            'PREV_URL'   => $page > 1 ? self::pageUrl($baseUrl, $sectionId, $page - 1) : '',
            'NEXT_URL'   => $page < $pages ? self::pageUrl($baseUrl, $sectionId, $page + 1) : '',
            'NOT_FOUND'  => $slug !== '' ? 1 : 0,
            'NEW_URL'    => $frontend ? self::formUrl($baseUrl, $sectionId, 'new') : '',
            'LINK_URL'   => $frontend ? self::formUrl($baseUrl, $sectionId, 'link') : '',
            'LIST_URL'   => $baseUrl,
            'SECTION_ID' => $sectionId,
        ]);
    }

    /**
     * Layout data of one entry: the fixed keys, every public field by its
     * name in upper case, and FIELDS for a loop over label and value.
     *
     * @param list<array<string, mixed>> $fields public fields
     */
    public static function itemData(array $item, array $fields, string $baseUrl, bool $listOnly): array
    {
        $data = [
            'ID'        => $item['item_id'],
            'TITLE'     => $item['title'],
            'SLUG'      => $item['slug'],
            'URL'       => self::itemUrl($baseUrl, $item['slug']),
            'CREATED'   => $item['created_at'],
            'UPDATED'   => $item['updated_at'],
            'PUBLISHED' => $item['published_at'],
            'EXPIRES'   => $item['expires_at'],
            'FIELDS'    => [],
            'RAW'       => [],
        ];
        foreach ($fields as $field) {
            $raw = $item['data'][$field['name']] ?? null;
            $display = FieldTypes::display($field, $raw);
            $key = strtoupper($field['name']);
            $data[$key] = $display;
            $data['RAW'][$key] = $raw;
            if ($display !== '' && (!$listOnly || $field['in_list'])) {
                $data['FIELDS'][] = [
                    'NAME'  => $field['name'],
                    'LABEL' => $field['label'],
                    'TYPE'  => $field['type'],
                    'VALUE' => $display,
                ];
            }
        }
        return $data;
    }

    public static function itemUrl(string $baseUrl, string $slug): string
    {
        return self::withQuery($baseUrl, [self::PARAM_ITEM => $slug]);
    }

    public static function tokenUrl(string $baseUrl, string $param, string $token): string
    {
        return self::withQuery($baseUrl, [$param => $token]);
    }

    public static function formUrl(string $baseUrl, int $sectionId, string $form): string
    {
        return self::withQuery($baseUrl, [self::PARAM_FORM => $form, self::PARAM_SECTION => $sectionId]) . '#lst-' . $sectionId;
    }

    private static function pageUrl(string $baseUrl, int $sectionId, int $page): string
    {
        return $page <= 1 ? $baseUrl : self::withQuery($baseUrl, [self::PARAM_PAGE => [$sectionId => $page]]);
    }

    /** @param array<string, mixed> $query */
    private static function withQuery(string $baseUrl, array $query): string
    {
        return $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private static function currentPage(int $sectionId, int $pages): int
    {
        $param = $_GET[self::PARAM_PAGE] ?? null;
        $page = is_array($param) ? (int) ($param[$sectionId] ?? 1) : 1;
        return max(1, min($pages, $page));
    }

    private static function slugBelongsElsewhere(int $sectionId, int $pageId, string $slug): bool
    {
        global $database;
        return $database->fetchValue(
            'SELECT `item_id` FROM `{TP}mod_listings_items`
              WHERE `page_id` = ? AND `section_id` <> ? AND `slug` = ? AND `status` = ?',
            [$pageId, $sectionId, $slug, 'published']
        ) !== '';
    }

    private static function ftan(): string
    {
        $wb = $GLOBALS['wb'] ?? null;
        return $wb instanceof \SecureForm ? $wb->getFTAN() : '';
    }

    /** The captcha of captcha_control (ALTCHA; without HTTPS a small sum). */
    private static function captcha(int $sectionId): string
    {
        if (!function_exists('call_captcha') || !class_exists('Captcha')) {
            return '';
        }
        ob_start();
        call_captcha('all', '', 'lst' . $sectionId);
        return (string) ob_get_clean();
    }

    /**
     * Renders frontend/<name>.php with $v as its only variable.
     *
     * @param array<string, mixed> $v
     */
    private static function template(string $name, array $v): string
    {
        ob_start();
        include dirname(__DIR__) . '/frontend/' . $name . '.php';
        return (string) ob_get_clean();
    }
}
