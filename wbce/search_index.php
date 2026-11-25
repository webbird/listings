<?php
/**
 * Listings – search_index.php
 * Tells WBCE Search (core module wbce_search, WBCE 1.7+) what to index for a
 * listings section: one entry per visible entry, with its detail page. Only
 * public fields, visible categories and tags are indexed — WBCE Search
 * checks only the page's rights, so whatever is indexed can be found by
 * everyone who may see the page. Format as in news_img and articles.
 *
 * Kept up to date like articles: every backend change calls
 * $admin->touchSection(), and WBCE Search re-indexes a section whose page
 * changed. Changes by owners in the frontend and expiries touch no page, so
 * the index follows only with the next backend edit.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

return [
    'callback' => static function (
        Database $database, array $def, array $section,
        int $pid, int $sid, string $module,
        string $page_title, string $description, string $keywords,
        string $visibility, int $searching, int $now
    ): int {
        // Not at file level: WBCE Search may read this file without indexing
        require_once __DIR__ . '/bootstrap.php';

        // WBCE Search has deleted the section's old rows before calling
        $insert = static function (int $itemId, string $itemTitle, string $text, string $detailUrl) use (
            $database, $pid, $sid, $module, $page_title, $description, $keywords, $visibility, $searching, $now
        ): int {
            $database->insertRow('{TP_SEARCH}index', [
                'page_id'     => $pid,
                'section_id'  => $sid,
                'item_id'     => $itemId,
                'module'      => $module,
                'title'       => $page_title,
                'item_title'  => $itemTitle,
                'description' => $description,
                'keywords'    => $keywords,
                'content'     => WbceSearchIndexer::plainText($text),
                'detail_url'  => WbceSearch::urlToToken($detailUrl),
                'thumb_url'   => '',
                'visibility'  => $visibility,
                'searching'   => $searching,
                'indexed_at'  => $now,
            ]);
            return $database->hasError() ? 0 : 1;
        };

        $settings = Listings\Settings::load($sid);
        $fields = Listings\Module::publicFields($sid);
        $pageUrl = Listings\Module::pageUrl($pid);
        $items = Listings\Items::list($sid, null, true, Listings\Settings::SORTS['newest'], 5000, 0);
        $taxonomy = Listings\Taxonomy::forItems($sid, array_column($items, 'item_id'));

        $count = 0;
        foreach ($items as $item) {
            $text = '';
            foreach (Listings\Module::publicValues($item, $fields, $taxonomy[$item['item_id']], $settings['tags_mode'] !== 'off') as $line) {
                $text .= $line['label'] . ': ' . $line['value'] . "\n";
            }
            $count += $insert($item['item_id'], $item['title'], $text, Listings\View::itemUrl($pageUrl, $item['slug']));
        }

        // No visible entry: keep the page itself findable by its title.
        // Returning 0 would store a marker row ("processed, nothing to find").
        if ($count === 0 && $page_title !== '') {
            $count = $insert(0, '', '', '');
        }
        return $count;
    },
];
