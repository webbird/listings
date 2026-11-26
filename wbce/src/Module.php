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

    /** The ID behind an IDKEY in $_POST[$field], or 0 when it is unknown, expired or for another purpose. */
    public static function checkId(\SecureForm $form, string $field, string $purpose): int
    {
        if (!is_string($_POST[$field] ?? null)) {
            return 0;
        }
        $value = $form->checkIDKEY($field, '', 'POST');
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
        foreach (['files', 'item_tags', 'tags', 'item_categories', 'categories', 'tokens', 'items', 'fields', 'settings'] as $table) {
            $database->deleteRow('{TP}mod_listings_' . $table, 'section_id', $sectionId);
            if ($database->hasError()) {
                trigger_error('Listings: ' . $database->getError(), E_USER_WARNING);
            }
        }
    }
}
