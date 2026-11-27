<?php
/**
 * Listings – shared helpers: URLs, flash data, section cleanup.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Module
{
    public const DIR = 'listings';

    private const FLASH = 'mod_listings_flash';

    /**
     * Backend page of the section; with $params the view inside the section.
     *
     * @param array<string, int|string> $params
     */
    public static function modifyUrl(int $pageId, int $sectionId, array $params = []): string
    {
        $query = ['page_id' => $pageId];
        if ($params !== []) {
            $query += ['lst_section' => $sectionId] + $params;
        }
        $anchor = (defined('SEC_ANCHOR') && SEC_ANCHOR !== '' ? SEC_ANCHOR : 'sec_anchor_') . $sectionId;
        // explicit separator: some modules set arg_separator.output to '&amp;' for the whole request
        return ADMIN_URL . '/pages/modify.php?' . http_build_query($query, '', '&') . '#' . $anchor;
    }

    /** A string of MOD_LISTINGS, placeholders filled. */
    public static function t(string $key, string|int|float ...$args): string
    {
        $text = \Lang::get('MOD_LISTINGS', $key, $key);
        return $args === [] ? $text : vsprintf($text, $args);
    }

    /**
     * Twig for the templates in twig/. WBCE's getTwig() turns autoescaping
     * off; here every value is escaped unless a template says |raw.
     *
     * @param array<string, callable> $functions extra functions for this request
     */
    public static function twig(array $functions = []): \Twig\Environment
    {
        $twig = getTwig(dirname(__DIR__) . '/twig/');
        $twig->getExtension(\Twig\Extension\EscaperExtension::class)->setDefaultStrategy('html');
        $twig->addFunction(new \Twig\TwigFunction('t', self::t(...)));
        $twig->addFunction(new \Twig\TwigFunction('format_time', self::formatTime(...)));
        $twig->addFunction(new \Twig\TwigFunction('maxlength', FieldTypes::maxLength(...)));
        // the value of an HTML field as the editors may show it: reduced to the allowed tags
        $twig->addFunction(new \Twig\TwigFunction('clean_html', static fn (mixed $v): string => is_string($v) ? Html::clean($v) : ''));
        foreach ($functions as $name => $function) {
            $twig->addFunction(new \Twig\TwigFunction($name, $function));
        }
        return $twig;
    }

    /**
     * URL of a page from its stored link, without a $wb object: search
     * indexing, feeds and link tokens may run without one.
     */
    public static function pageUrl(int $pageId): string
    {
        global $database;
        $link = (string) $database->fetchValue('SELECT `link` FROM `{TP}pages` WHERE `page_id` = ?', [$pageId]);
        return $link === '' ? '' : WB_URL . PAGES_DIRECTORY . $link . PAGE_EXTENSION;
    }

    /**
     * Plain text of an entry for search and feeds: title, public field
     * values, categories and tags. Hidden fields never appear.
     *
     * @param list<array<string, mixed>> $publicFields
     * @param array{categories: list<array>, tags: list<array>} $tax
     * @return list<array{label: string, value: string}>
     */
    public static function publicValues(array $item, array $publicFields, array $tax, bool $tagsOn): array
    {
        $lines = [];
        foreach ($publicFields as $field) {
            $value = FieldTypes::display($field, $item['data'][$field['name']] ?? null);
            if ($value !== '') {
                $lines[] = ['label' => $field['label'], 'value' => $value];
            }
        }
        $cats = array_column(array_filter($tax['categories'], static fn ($c) => $c['visible']), 'name');
        if ($cats !== []) {
            $lines[] = ['label' => self::t('CATEGORIES'), 'value' => implode(', ', $cats)];
        }
        if ($tagsOn && $tax['tags'] !== []) {
            $lines[] = ['label' => self::t('TAGS'), 'value' => implode(', ', array_column($tax['tags'], 'name'))];
        }
        return $lines;
    }

    /** @return list<array<string, mixed>> the fields visitors see */
    public static function publicFields(int $sectionId): array
    {
        return array_values(array_filter(
            Fields::forSection($sectionId),
            static fn ($f) => $f['visibility'] === 'public' && FieldTypes::stores($f['type'])
        ));
    }

    public static function saveUrl(): string
    {
        return WB_URL . '/modules/' . self::DIR . '/save.php';
    }

    /**
     * Data for the next request of this section (errors, form input).
     *
     * @param array<string, mixed> $data
     */
    public static function flash(int $sectionId, array $data, string $scope = 'be'): void
    {
        $_SESSION[self::FLASH][$scope][$sectionId] = $data;
    }

    /**
     * @param 'be'|'fe' $scope backend or frontend, so the two never take each other's messages
     * @return array<string, mixed>
     */
    public static function takeFlash(int $sectionId, string $scope = 'be'): array
    {
        $data = $_SESSION[self::FLASH][$scope][$sectionId] ?? [];
        unset($_SESSION[self::FLASH][$scope][$sectionId]);
        return is_array($data) ? $data : [];
    }

    /**
     * IDKEY for an ID. The purpose travels inside the stored value, so a key
     * issued for a field is refused where an entry is expected — also on
     * installations whose getIDKEY() has no purpose parameter yet.
     */
    public static function idKey(\SecureForm $form, string $purpose, int $id): string
    {
        return $form->getIDKEY('listings_' . $purpose . ':' . $id);
    }

    /**
     * The page row of a listings section a visitor may see: the section is
     * inside its publication period and the page is visible to them. Null otherwise.
     */
    public static function visiblePage(int $sectionId): ?array
    {
        global $database;
        $section = $database->fetchRow(
            'SELECT `page_id`, `publ_start`, `publ_end` FROM `{TP}sections` WHERE `section_id` = ? AND `module` = ?',
            [$sectionId, self::DIR]
        );
        $now = time();
        if ($section === null
            || ((int) $section['publ_start'] !== 0 && $now < (int) $section['publ_start'])
            || ((int) $section['publ_end'] !== 0 && $now > (int) $section['publ_end'])) {
            return null;
        }
        $page = $database->fetchRow('SELECT * FROM `{TP}pages` WHERE `page_id` = ?', [(int) $section['page_id']]);
        $wb = $GLOBALS['wb'] ?? new \Frontend();
        return $page !== null && $wb->page_is_visible($page) ? $page : null;
    }

    /**
     * A request by a script (fetch, XHR, htmx). Uses BackendAjax where the
     * core has it; some 1.7 builds do not yet.
     */
    public static function isAjax(): bool
    {
        if (class_exists('BackendAjax')) {
            return \BackendAjax::isAjax();
        }
        $with = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return in_array($with, ['fetch', 'xmlhttprequest'], true)
            || isset($_SERVER['HTTP_HX_REQUEST'])
            || str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
    }

    /** A script request with an expired form or key: 403 and JSON instead of an error page. */
    public static function denyAjaxToken(): void
    {
        if (class_exists('BackendAjax')) {
            \BackendAjax::denyIfAjax(\BackendAjax::TOKEN_EXPIRED);
            return;
        }
        if (!self::isAjax()) {
            return;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'reason' => 'token_expired', 'message' => L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * One key for all IDs a page lists (bulk actions, sorting): the form
     * then names positions in this list, never IDs.
     *
     * @param list<int> $ids
     */
    public static function idKeyList(\SecureForm $form, string $purpose, array $ids): string
    {
        return $form->getIDKEY('listings_' . $purpose . ':' . implode(',', array_map('intval', $ids)));
    }

    /**
     * The IDs behind an idKeyList(), by position; [] when unknown or expired.
     * $peek keeps the key valid for more requests of the same page.
     *
     * @return list<int>
     */
    public static function checkIdList(\SecureForm $form, string $field, string $purpose, bool $peek = false): array
    {
        if (!is_string($_POST[$field] ?? null)) {
            return [];
        }
        $value = $form->checkIDKEY($field, '', 'POST', $peek);
        $prefix = 'listings_' . $purpose . ':';
        if (!is_string($value) || !str_starts_with($value, $prefix)) {
            return [];
        }
        $list = substr($value, strlen($prefix));
        return $list === '' ? [] : array_map('intval', explode(',', $list));
    }

    /**
     * The ID behind an IDKEY in $_POST[$field], or 0 when it is unknown, expired or for another purpose.
     * $peek (for fetch requests) keeps the key and those of the same page valid.
     */
    public static function checkId(\SecureForm $form, string $field, string $purpose, bool $peek = false): int
    {
        if (!is_string($_POST[$field] ?? null)) {
            return 0;
        }
        $value = $form->checkIDKEY($field, '', 'POST', $peek);
        $prefix = 'listings_' . $purpose . ':';
        return is_string($value) && str_starts_with($value, $prefix) ? (int) substr($value, strlen($prefix)) : 0;
    }

    /** Date and time of a timestamp in the backend, '' for 0. */
    public static function formatTime(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return '';
        }
        if (class_exists('DateTimeFormat')) {
            return \DateTimeFormat::format($timestamp, 'datetime');
        }
        return date(DATE_FORMAT . ', ' . TIME_FORMAT, $timestamp + (int) TIMEZONE);
    }

    public static function deleteSection(int $sectionId): void
    {
        global $database;
        Files::deleteSection($sectionId);
        foreach (['log', 'files', 'item_tags', 'tags', 'item_categories', 'categories', 'tokens', 'items', 'fields', 'settings'] as $table) {
            $database->deleteRow('{TP}mod_listings_' . $table, 'section_id', $sectionId);
            if ($database->hasError()) {
                trigger_error('Listings: ' . $database->getError(), E_USER_WARNING);
            }
        }
    }
}
