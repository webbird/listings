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
    public const PARAM_FILTER = 'lst_f';       // lst_f[<section>][c|t|q|<field>]

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
            // a request beyond post_max_size arrives without fields; the URL still names the section
            'upload_action' => WB_URL . '/modules/' . Module::DIR . '/submit.php?section_id=' . $sectionId,
            'max_post'      => Files::maxPostBytes(),
            'max_post_text' => Files::formatSize(Files::maxPostBytes()),
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
        return self::template('form', $vars + self::taxonomyVars($sectionId, $settings) + [
            'mode'     => 'new',
            'token'    => '',
            'item'     => null,
            'fields'   => Files::withLimits(Fields::forOwner(Fields::forSection($sectionId))),
            'files'    => [],
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
        $sectionId = $vars['section_id'];
        $tax = Taxonomy::forItems($sectionId, [$item['item_id']])[$item['item_id']];
        // the owner sees the own images also while the entry waits or is not public
        Files::grant($item['item_id']);
        return self::template('form', $vars + $auth + self::taxonomyVars($sectionId, $settings) + [
            'mode'       => 'edit',
            'item'       => $item,
            'fields'     => Files::withLimits(Fields::forOwner(Fields::forSection($sectionId))),
            'files'      => array_map([Files::class, 'forForm'], Files::forItem($sectionId, $item['item_id'])),
            'values'     => $old ?? [
                'title'      => $item['title'],
                'fields'     => $item['data'],
                'categories' => array_column($tax['categories'], 'cat_id'),
                'tag_ids'    => array_column($tax['tags'], 'tag_id'),
                'tags'       => implode(', ', array_column($tax['tags'], 'name')),
            ],
            'captcha'    => '',
            'extendable' => Items::extendable($item, $settings),
            'expired'    => $item['expires_at'] > 0 && $item['expires_at'] <= time(),
        ]);
    }

    /** What the owner's form offers: visible categories, and the tags as list (fixed) or text (open). */
    private static function taxonomyVars(int $sectionId, array $settings): array
    {
        $mode = $settings['tags_mode'] ?? 'off';
        return [
            'categories' => Taxonomy::categories($sectionId, true),
            'tags_mode'  => $mode,
            'all_tags'   => $mode === 'fixed' ? Taxonomy::tags($sectionId) : [],
        ];
    }

    private static function publicView(int $sectionId, int $pageId, array $settings, string $baseUrl, bool $frontend, array $flash): string
    {
        $fields = array_values(array_filter(
            Fields::forSection($sectionId),
            static fn ($f) => $f['visibility'] === 'public' && FieldTypes::stores($f['type'])
        ));
        $parser = Layouts::parser();
        $tagsOn = ($settings['tags_mode'] ?? 'off') !== 'off';

        $slug = is_string($_GET[self::PARAM_ITEM] ?? null) ? trim($_GET[self::PARAM_ITEM]) : '';
        if ($slug !== '') {
            $item = Items::findVisible($sectionId, $slug);
            if ($item !== null) {
                \I::insertTitle($item['title'] . ' - ' . WEBSITE_TITLE);
                // the contact form is HTML rendered here; the editable layout only places it
                $parser->setRawKeys(['CONTACT']);
                $tax = Taxonomy::forItems($sectionId, [$item['item_id']])[$item['item_id']];
                $files = Files::forItem($sectionId, $item['item_id']);
                return $parser->parse(Layouts::get($settings, 'detail'), [
                    'ITEM'       => self::itemData($item, $fields, $baseUrl, false, $tax, $tagsOn, $sectionId, $files),
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

        $tree = Taxonomy::categories($sectionId, true);
        $tags = $tagsOn ? Taxonomy::tags($sectionId) : [];
        $filter = self::filter($sectionId, $fields, $tree, $tags);

        $perPage = Settings::perPage($settings);
        $total = Items::count($sectionId, null, true, $filter['items']);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = self::currentPage($sectionId, $pages);
        $items = Items::list($sectionId, null, true, Settings::orderBy($settings), $perPage, ($page - 1) * $perPage, $filter['items']);
        $taxonomy = Taxonomy::forItems($sectionId, array_column($items, 'item_id'));
        $files = Files::forItems($sectionId, array_column($items, 'item_id'));

        if (Settings::on($settings, 'rss')) {
            $link = ['rel' => 'alternate', 'type' => 'application/rss+xml', 'title' => WEBSITE_TITLE, 'href' => self::rssUrl($sectionId)];
            // insertLink() is documented for 1.7 but missing in some builds; insertHtmlCode()
            // only reaches the body there, insertMeta() takes a whole tag for the head
            if (method_exists('AssetQueue', 'insertLink')) {
                \I::insertLink($link);
            } else {
                $attributes = '';
                foreach ($link as $name => $value) {
                    $attributes .= ' ' . $name . '="' . h($value) . '"';
                }
                \I::insertMeta('<link' . $attributes . '>', 'add', 'head_middle');
            }
        }
        // the filter form is HTML rendered here; the editable layout only places it
        $parser->setRawKeys(['FILTER']);
        return $parser->parse(Layouts::get($settings, 'list'), [
            'ITEMS'      => array_map(static fn ($i) => self::itemData($i, $fields, $baseUrl, true, $taxonomy[$i['item_id']], $tagsOn, $sectionId, $files[$i['item_id']] ?? []), $items),
            'COUNT'      => $total,
            'PAGE'       => $page,
            'PAGES'      => $pages,
            'PREV_URL'   => $page > 1 ? self::pageUrl($baseUrl, $sectionId, $page - 1, $filter['query']) : '',
            'NEXT_URL'   => $page < $pages ? self::pageUrl($baseUrl, $sectionId, $page + 1, $filter['query']) : '',
            'NOT_FOUND'  => $slug !== '' ? 1 : 0,
            'FILTER'     => Settings::on($settings, 'filter_form') ? self::filterForm($sectionId, $baseUrl, $fields, $tree, $tags, $filter) : '',
            'FILTERED'   => $filter['query'] !== [] ? 1 : 0,
            'CATEGORY'   => $filter['category']['name'] ?? '',
            'CATEGORY_DESCRIPTION' => $filter['category']['description'] ?? '',
            'TAG'        => $filter['tag']['name'] ?? '',
            'RESET_URL'  => $baseUrl,
            'RSS_URL'    => Settings::on($settings, 'rss') ? self::rssUrl($sectionId) : '',
            'NEW_URL'    => $frontend ? self::formUrl($baseUrl, $sectionId, 'new') : '',
            // with accounts the same link leads to "my entries"
            'LINK_URL'   => $frontend ? self::formUrl($baseUrl, $sectionId, Settings::accountMode($settings) ? 'mine' : 'link') : '',
            'LIST_URL'   => $baseUrl,
            'SECTION_ID' => $sectionId,
        ]);
    }

    public static function rssUrl(int $sectionId): string
    {
        return WB_URL . '/modules/' . Module::DIR . '/rss.php?section_id=' . $sectionId;
    }

    /**
     * The list filter from lst_f[<section>][…]: c (category slug), t (tag
     * slug), q (words), and one key per filterable field. Every value is
     * checked against what exists, so only known values reach the query.
     *
     * @param list<array<string, mixed>> $fields public fields
     * @param list<array<string, mixed>> $tree visible categories
     * @param list<array<string, mixed>> $tags
     * @return array{items: array<string, mixed>, query: array<string, string>, values: array<string, string>, category: ?array, tag: ?array}
     */
    private static function filter(int $sectionId, array $fields, array $tree, array $tags): array
    {
        $raw = $_GET[self::PARAM_FILTER][$sectionId] ?? [];
        $raw = is_array($raw) ? array_filter($raw, 'is_string') : [];
        $result = ['items' => [], 'query' => [], 'values' => [], 'category' => null, 'tag' => null];

        $slug = trim($raw['c'] ?? '');
        foreach ($slug === '' ? [] : $tree as $cat) {
            if ($cat['slug'] === $slug) {
                $result['items']['cats'] = Taxonomy::withDescendants($tree, $cat['cat_id']);
                $result['category'] = $cat;
                $result['query']['c'] = $slug;
                break;
            }
        }
        $slug = trim($raw['t'] ?? '');
        foreach ($slug === '' ? [] : $tags as $tag) {
            if ($tag['slug'] === $slug) {
                $result['items']['tag'] = $tag['tag_id'];
                $result['tag'] = $tag;
                $result['query']['t'] = $slug;
                break;
            }
        }
        foreach ($fields as $field) {
            $value = trim($raw[$field['name']] ?? '');
            if ($value === '' || !$field['filterable'] || !in_array($value, array_column($field['options']['choices'] ?? [], 'value'), true)) {
                continue;
            }
            $result['items']['fields'][] = ['name' => $field['name'], 'multi' => $field['type'] === 'checkboxes', 'value' => $value];
            $result['query'][$field['name']] = $value;
        }
        $q = mb_substr(trim(preg_replace('/\s+/u', ' ', $raw['q'] ?? '') ?? ''), 0, 100);
        if ($q !== '') {
            $result['items']['q'] = $q;
            // the words are looked for in the title and in public text fields only
            $result['items']['q_fields'] = array_column(array_filter(
                $fields,
                static fn ($f) => in_array($f['type'], ['text', 'textarea', 'select', 'radio', 'url'], true)
            ), 'name');
            $result['query']['q'] = $q;
        }
        $result['values'] = $result['query'];
        return $result;
    }

    private static function filterForm(int $sectionId, string $baseUrl, array $fields, array $tree, array $tags, array $filter): string
    {
        // the page itself may carry a query (?lang=…); the form sends it along
        $parts = explode('?', $baseUrl, 2);
        $keep = [];
        parse_str($parts[1] ?? '', $keep);
        return self::template('filter', [
            'section_id' => $sectionId,
            'action'     => $parts[0],
            'keep'       => array_filter($keep, 'is_string'),
            'name'       => self::PARAM_FILTER . '[' . $sectionId . ']',
            'categories' => $tree,
            'tags'       => $tags,
            'fields'     => array_values(array_filter($fields, static fn ($f) => $f['filterable'])),
            'values'     => $filter['values'],
            'reset_url'  => $filter['query'] !== [] ? $baseUrl : '',
        ]);
    }

    /** The list filtered by one key, for category and tag links. */
    private static function filterUrl(string $baseUrl, int $sectionId, string $key, string $value): string
    {
        return self::withQuery($baseUrl, [self::PARAM_FILTER => [$sectionId => [$key => $value]]]) . '#lst-' . $sectionId;
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
     * @param array{categories: list<array>, tags: list<array>}|null $tax from Taxonomy::forItems()
     * @param array<string, list<array<string, mixed>>> $files from Files::forItem(), by field name
     */
    public static function itemData(array $item, array $fields, string $baseUrl, bool $listOnly, ?array $tax = null, bool $tagsOn = false, int $sectionId = 0, array $files = []): array
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
            // the first image: in the list of a field marked "show in list", in the detail of any public image field
            'IMAGE'     => '',
            'THUMB'     => '',
            'IMAGE_NAME' => '',
        ];
        foreach ($fields as $field) {
            if (Files::isUpload($field['type'])) {
                // a loop per field: URL, THUMB (images), NAME, SIZE, DOWNLOADS (files), WIDTH, HEIGHT
                $list = array_map(static fn ($f) => [
                    'URL'       => Files::url($f),
                    'THUMB'     => Files::url($f, true),
                    'NAME'      => $f['filename'],
                    'SIZE'      => Files::formatSize($f['size']),
                    'DOWNLOADS' => $f['downloads'],
                    'WIDTH'     => $f['width'],
                    'HEIGHT'    => $f['height'],
                ], $files[$field['name']] ?? []);
                $key = strtoupper($field['name']);
                $data[$key] = $list;
                if ($list !== [] && Files::isImage($field['type']) && $data['IMAGE'] === '' && (!$listOnly || $field['in_list'])) {
                    $data['IMAGE'] = $list[0]['URL'];
                    $data['THUMB'] = $list[0]['THUMB'];
                    $data['IMAGE_NAME'] = $list[0]['NAME'];
                }
                // the list shows the first image only; the detail every field with its files
                if ($list !== [] && !$listOnly) {
                    $data['FIELDS'][] = [
                        'NAME'  => $field['name'],
                        'LABEL' => $field['label'],
                        'TYPE'  => $field['type'],
                        'VALUE' => (string) count($list),
                        'FILES' => $list,
                    ];
                }
                continue;
            }
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
                    'FILES' => [],
                ];
            }
        }
        // categories and tags as links to the filtered list; hidden categories are left out
        $data['CATEGORIES'] = [];
        $data['TAGS'] = [];
        foreach ($tax['categories'] ?? [] as $cat) {
            if ($cat['visible']) {
                $data['CATEGORIES'][] = ['NAME' => $cat['name'], 'URL' => self::filterUrl($baseUrl, $sectionId, 'c', $cat['slug'])];
            }
        }
        foreach ($tagsOn ? $tax['tags'] ?? [] : [] as $tag) {
            $data['TAGS'][] = ['NAME' => $tag['name'], 'URL' => self::filterUrl($baseUrl, $sectionId, 't', $tag['slug'])];
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

    /** @param array<string, string> $filter the active filter, kept while paging */
    private static function pageUrl(string $baseUrl, int $sectionId, int $page, array $filter = []): string
    {
        $query = ($page > 1 ? [self::PARAM_PAGE => [$sectionId => $page]] : []) + ($filter !== [] ? [self::PARAM_FILTER => [$sectionId => $filter]] : []);
        return $query === [] ? $baseUrl : self::withQuery($baseUrl, $query);
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
