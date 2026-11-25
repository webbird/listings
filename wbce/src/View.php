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
    public const PARAM_FORM = 'lst_form';      // new | link | mine | edit, with PARAM_SECTION
    public const PARAM_SECTION = 'lst_s';
    public const PARAM_ITEM_ID = 'lst_item';   // with lst_form=edit: an entry of the logged-in owner
    public const PARAM_CONFIRM = 'lst_confirm';
    public const PARAM_EDIT = 'lst_edit';
    public const PARAM_EXTEND = 'lst_extend';

    public static function render(int $sectionId, int $pageId): string
    {
        // WBCE has no cron: unconfirmed entries, old links and reminders are handled on page views
        Items::purgeUnconfirmed($sectionId);
        Tokens::purge();
        Reminders::run($sectionId, Settings::load($sectionId));

        $settings = Settings::load($sectionId);
        $baseUrl = page_link($pageId);
        $frontend = Settings::on($settings, 'frontend');
        // also without entries by visitors: the contact form leaves its messages here
        $flash = Module::takeFlash($sectionId, 'fe');

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
        return $message . self::publicView($sectionId, $pageId, $settings, $baseUrl, $frontend, $flash);
    }

    /**
     * The logged-in frontend user, or null.
     *
     * @return array{id: int, email: string}|null
     */
    public static function user(): ?array
    {
        $wb = $GLOBALS['wb'] ?? null;
        if (!$wb instanceof \Wbce || !$wb->isLoggedIn() || (int) $wb->get_user_id() <= 0) {
            return null;
        }
        return ['id' => (int) $wb->get_user_id(), 'email' => strtolower((string) $wb->get_email())];
    }

    /** Edit form of an entry of the logged-in owner. */
    public static function ownerEditUrl(string $baseUrl, int $sectionId, int $itemId): string
    {
        return self::withQuery($baseUrl, [self::PARAM_FORM => 'edit', self::PARAM_SECTION => $sectionId, self::PARAM_ITEM_ID => $itemId]) . '#lst-' . $sectionId;
    }

    /**
     * Forms and mailed links of owners; null when the request is not about them.
     */
    private static function ownerView(int $sectionId, array $settings, string $baseUrl, array $flash): ?string
    {
        $get = static fn (string $name): string => is_string($_GET[$name] ?? null) ? $_GET[$name] : '';
        $errors = is_array($flash['errors'] ?? null) ? $flash['errors'] : [];
        $old = is_array($flash['old'] ?? null) ? $flash['old'] : null;
        $account = Settings::accountMode($settings);
        $vars = [
            'section_id' => $sectionId,
            'action'     => WB_URL . '/modules/' . Module::DIR . '/submit.php',
            'list_url'   => $baseUrl,
            'link_url'   => self::formUrl($baseUrl, $sectionId, $account ? 'mine' : 'link'),
            'errors'     => $errors,
            'success'    => (string) ($flash['success'] ?? ''),
            'account'    => $account,
        ];
        $invalid = static fn () => self::template('message', ['text' => L_('MOD_LISTINGS:LINK_INVALID'), 'type' => 'error', 'list_url' => $baseUrl, 'link_url' => $vars['link_url']]);

        // links from mails: they name their section through the token
        if ($get(self::PARAM_CONFIRM) !== '') {
            $token = Tokens::find($get(self::PARAM_CONFIRM), Tokens::CONFIRM);
            if ($token !== null && $token['section_id'] !== $sectionId) {
                return null;
            }
            $item = $token === null ? null : Items::find($sectionId, $token['item_id']);
            if ($item === null || $item['status'] !== 'draft') {
                return $invalid();
            }
            return self::template('confirm', $vars + ['token' => $get(self::PARAM_CONFIRM)]);
        }

        if ($get(self::PARAM_EXTEND) !== '') {
            $token = Tokens::find($get(self::PARAM_EXTEND), Tokens::EXTEND);
            if ($token !== null && $token['section_id'] !== $sectionId) {
                return null;
            }
            $item = $token === null ? null : Items::find($sectionId, $token['item_id']);
            if ($item === null || !Items::extendable($item, $settings)) {
                return $invalid();
            }
            return self::template('extend', $vars + [
                'token'   => $get(self::PARAM_EXTEND),
                'item'    => $item,
                'expired' => $item['expires_at'] <= time(),
            ]);
        }

        if ($get(self::PARAM_EDIT) !== '') {
            $token = Tokens::find($get(self::PARAM_EDIT), Tokens::EDIT);
            if ($token !== null && $token['section_id'] !== $sectionId) {
                return null;
            }
            $item = $token === null ? null : Items::find($sectionId, $token['item_id']);
            if ($item === null) {
                return $invalid();
            }
            return self::editForm($vars, $settings, $item, $old, ['token' => $get(self::PARAM_EDIT)]);
        }

        if ((int) $get(self::PARAM_SECTION) !== $sectionId) {
            return null;
        }
        $form = $get(self::PARAM_FORM);
        if (!in_array($form, ['new', 'link', 'mine', 'edit'], true)) {
            return null;
        }
        // with accounts, "my entries" replaces the mailed links, and the other way round
        if ($account && $form === 'link') {
            $form = 'mine';
        } elseif (!$account && in_array($form, ['mine', 'edit'], true)) {
            $form = 'link';
        }

        $user = self::user();
        if ($account && $user === null) {
            return self::template('login', $vars + [
                'login_url'  => defined('LOGIN_URL') ? LOGIN_URL . '?redirect=' . rawurlencode(self::formUrl($baseUrl, $sectionId, $form)) : '',
                'signup_url' => defined('SIGNUP_URL') && defined('FRONTEND_SIGNUP') && FRONTEND_SIGNUP ? SIGNUP_URL : '',
            ]);
        }

        if ($form === 'mine') {
            $items = [];
            foreach (Items::ownedByUser($sectionId, $user['id']) as $item) {
                $items[] = $item + [
                    'edit_url'   => self::ownerEditUrl($baseUrl, $sectionId, $item['item_id']),
                    'extendable' => Items::extendable($item, $settings),
                    'key'        => Module::idKey($GLOBALS['wb'], 'item', $item['item_id']),
                    'expired'    => $item['expires_at'] > 0 && $item['expires_at'] <= time(),
                ];
            }
            return self::template('mine', $vars + ['items' => $items, 'new_url' => self::formUrl($baseUrl, $sectionId, 'new')]);
        }

        if ($form === 'edit') {
            $item = Items::find($sectionId, (int) $get(self::PARAM_ITEM_ID));
            if ($item === null || $item['owner_user_id'] !== $user['id']) {
                return self::template('message', ['text' => L_('MOD_LISTINGS:ERR_NOT_YOURS'), 'type' => 'error', 'list_url' => $baseUrl, 'link_url' => $vars['link_url']]);
            }
            return self::editForm($vars, $settings, $item, $old, ['item_key' => Module::idKey($GLOBALS['wb'], 'item', $item['item_id'])]);
        }

        // bots send at once; Submission refuses forms sent within seconds of being shown
        $_SESSION['mod_listings_form_time'][$sectionId] = time();
        // a logged-in owner has proven to be a person already
        $vars['captcha'] = Settings::on($settings, 'captcha') && $user === null ? self::captcha($sectionId) : '';
        if ($form === 'link') {
            return self::template('link', $vars + ['values' => $old ?? []]);
        }
        return self::template('form', $vars + [
            'mode'     => 'new',
            'token'    => '',
            'item'     => null,
            'fields'   => Fields::forOwner(Fields::forSection($sectionId)),
            'values'   => $old ?? [],
            'approval' => Settings::on($settings, 'approval'),
        ]);
    }

    /**
     * The owner's edit form, reached by a mailed link (token) or with an account (item_key).
     *
     * @param array<string, string> $auth token or item_key
     */
    private static function editForm(array $vars, array $settings, array $item, ?array $old, array $auth): string
    {
        return self::template('form', $vars + $auth + [
            'mode'       => 'edit',
            'item'       => $item,
            'fields'     => Fields::forOwner(Fields::forSection($vars['section_id'])),
            'values'     => $old ?? ['title' => $item['title'], 'fields' => $item['data']],
            'captcha'    => '',
            'extendable' => Items::extendable($item, $settings),
            'expired'    => $item['expires_at'] > 0 && $item['expires_at'] <= time(),
        ]);
    }

    private static function publicView(int $sectionId, int $pageId, array $settings, string $baseUrl, bool $frontend, array $flash): string
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
                // the contact form is HTML rendered here; the editable layout only places it
                $parser->setRawKeys(['CONTACT']);
                return $parser->parse(Layouts::get($settings, 'detail'), [
                    'ITEM'       => self::itemData($item, $fields, $baseUrl, false),
                    'CONTACT'    => self::contactForm($sectionId, $settings, $item, $flash),
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
            // with accounts the same link leads to "my entries"
            'LINK_URL'   => $frontend ? self::formUrl($baseUrl, $sectionId, Settings::accountMode($settings) ? 'mine' : 'link') : '',
            'LIST_URL'   => $baseUrl,
            'SECTION_ID' => $sectionId,
        ]);
    }

    /** The contact form to the owner, or '' when it is off or the entry has no owner address. */
    private static function contactForm(int $sectionId, array $settings, array $item, array $flash): string
    {
        if (!Settings::on($settings, 'contact') || $item['owner_email'] === '' || !isset($GLOBALS['wb'])) {
            return '';
        }
        $_SESSION['mod_listings_form_time'][$sectionId] = time();
        $old = ($flash['form'] ?? '') === 'contact' && is_array($flash['old'] ?? null) ? $flash['old'] : [];
        return self::template('contact', [
            'section_id' => $sectionId,
            'action'     => WB_URL . '/modules/' . Module::DIR . '/submit.php',
            'item_key'   => Module::idKey($GLOBALS['wb'], 'contact', $item['item_id']),
            'values'     => $old,
            'captcha'    => Settings::on($settings, 'captcha') && self::user() === null ? self::captcha($sectionId) : '',
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
     * Renders twig/view_<name>.twig.
     *
     * @param array<string, mixed> $vars
     */
    private static function template(string $name, array $vars): string
    {
        return Module::twig()->render('view_' . $name . '.twig', $vars);
    }
}
