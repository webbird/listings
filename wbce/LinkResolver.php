<?php
/**
 * Listings – LinkResolver.php
 * Entries in the link dialog of the editor, stored as [listings:<item_id>]
 * and turned into the current URL of the detail page on output, so a link
 * survives a new title or a moved page. Only visible entries resolve.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

require_once __DIR__ . '/bootstrap.php';

class ListingsLinkResolver extends LinkResolver
{
    /** Visible entries of one section, by title. */
    public function linkItems(int $sectionId, int $pageId): array
    {
        $items = Listings\Items::list($sectionId, null, true, Listings\Settings::SORTS['title'], 1000, 0);
        return array_map(static fn ($item) => [
            'label' => (string) $item['title'],
            'value' => '[listings:' . $item['item_id'] . ']',
        ], $items);
    }

    /** URL of an entry the visitor may see, else null (the token stays as it is). */
    public function resolve(int $itemId): ?string
    {
        global $database;
        $row = $database->fetchRow(
            'SELECT i.`section_id`, i.`slug`, s.`page_id` FROM `{TP}mod_listings_items` i
               JOIN `{TP}sections` s ON s.`section_id` = i.`section_id`
              WHERE i.`item_id` = ? AND i.`status` = ? AND (i.`expires_at` = 0 OR i.`expires_at` > ?)',
            [$itemId, 'published', time()]
        );
        if ($row === null) {
            return null;
        }
        $pageUrl = Listings\Module::pageUrl((int) $row['page_id']);
        return $pageUrl === '' ? null : Listings\View::itemUrl($pageUrl, (string) $row['slug']);
    }

    /** The entry shown as detail view in this request. */
    public function currentItemId(int $sectionId, int $pageId): ?int
    {
        $slug = is_string($_GET[Listings\View::PARAM_ITEM] ?? null) ? trim($_GET[Listings\View::PARAM_ITEM]) : '';
        $item = $slug === '' ? null : Listings\Items::findVisible($sectionId, $slug);
        return $item === null ? null : $item['item_id'];
    }
}
