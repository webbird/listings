<?php
/**
 * Listings – search.php
 * Hook of the classic core search (search/search.php calls listings_search()
 * per section): every visible entry is a hit of its own, leading to its
 * detail page. No {TP}search rows. WBCE Search (core module wbce_search)
 * reads search_index.php instead.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

require_once __DIR__ . '/bootstrap.php';

if (!function_exists('listings_search')) {
    function listings_search(array $vars): bool
    {
        $sectionId = (int) $vars['section_id'];
        $settings = Listings\Settings::load($sectionId);
        $fields = Listings\Module::publicFields($sectionId);
        $items = Listings\Items::list($sectionId, null, true, Listings\Settings::SORTS['updated'], 1000, 0);
        $taxonomy = Listings\Taxonomy::forItems($sectionId, array_column($items, 'item_id'));
        $tagsOn = $settings['tags_mode'] !== 'off';

        $found = false;
        foreach ($items as $item) {
            $text = $item['title'] . '.';
            foreach (Listings\Module::publicValues($item, $fields, $taxonomy[$item['item_id']], $tagsOn) as $line) {
                $text .= ' ' . $line['value'] . '.';
            }
            $found = print_excerpt2([
                'page_link'          => $vars['page_link'],
                'page_link_target'   => '&' . Listings\View::PARAM_ITEM . '=' . rawurlencode($item['slug']),
                'page_title'         => $vars['page_title'],
                'page_description'   => $item['title'],
                'page_modified_when' => $item['updated_at'],
                'page_modified_by'   => $vars['page_modified_by'],
                'text'               => $text,
                'max_excerpt_num'    => $vars['default_max_excerpt'],
            ], $vars) || $found;
        }
        return $found;
    }
}
