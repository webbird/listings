<?php
/**
 * Listings – rss.php
 * Feed of the newest visible entries of a section: ?section_id=
 * Delivers only if the section is a listings section with the feed switched
 * on, its page is visible to the visitor and the section is inside its
 * publication period (as articles and news_img). Otherwise 404.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

$sectionId = filter_input(INPUT_GET, 'section_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!is_int($sectionId)) {
    http_response_code(400);
    exit;
}

// SCRIPT_FILENAME keeps the junction path during development; relative paths and __DIR__ resolve it
require dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php';
require_once __DIR__ . '/bootstrap.php';

use Listings\Items;
use Listings\Module;
use Listings\Settings;
use Listings\Taxonomy;
use Listings\View;

$notFound = static function (): never {
    http_response_code(404);
    exit;
};
$settings = Settings::load($sectionId);
Settings::on($settings, 'rss') || $notFound();
$page = Module::visiblePage($sectionId) ?? $notFound();
$pageId = (int) $page['page_id'];

$pageUrl = Module::pageUrl($pageId);
$fields = Module::publicFields($sectionId);
$items = Items::list($sectionId, null, true, Settings::SORTS['published'], 20, 0);
$taxonomy = Taxonomy::forItems($sectionId, array_column($items, 'item_id'));
$tagsOn = $settings['tags_mode'] !== 'off';

$cdata = static fn (string $text): string => '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $text) . ']]>';
$xml = static fn (string $text): string => htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>', "\n";
?>
<rss version="2.0">
    <channel>
        <title><?= $xml((string) $page['page_title']) ?></title>
        <link><?= $xml($pageUrl) ?></link>
        <description><?= $xml((string) ($page['description'] ?: WEBSITE_TITLE)) ?></description>
        <language><?= $xml(strtolower((string) ($page['language'] ?: DEFAULT_LANGUAGE))) ?></language>
<?php foreach ($items as $item):
    $url = $xml(View::itemUrl($pageUrl, $item['slug']));
    $description = '';
    foreach (Module::publicValues($item, $fields, $taxonomy[$item['item_id']], $tagsOn) as $line) {
        $description .= '<p><strong>' . h($line['label']) . ':</strong> ' . nl2br(h($line['value'])) . '</p>';
    }
    $date = $item['published_at'] > 0 ? $item['published_at'] : $item['created_at']; ?>
        <item>
            <title><?= $cdata($item['title']) ?></title>
            <description><?= $cdata($description) ?></description>
            <guid isPermaLink="true"><?= $url ?></guid>
            <link><?= $url ?></link>
            <pubDate><?= $xml(date(DATE_RSS, $date)) ?></pubDate>
        </item>
<?php endforeach; ?>
    </channel>
</rss>
