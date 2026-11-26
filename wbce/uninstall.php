<?php
/**
 * Listings – uninstall.php
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

foreach (['files', 'item_tags', 'tags', 'item_categories', 'categories', 'tokens', 'items', 'fields', 'settings'] as $listingsTable) {
    $database->query('DROP TABLE IF EXISTS `{TP}mod_listings_' . $listingsTable . '`');
    if ($database->hasError()) {
        log_err('Listings: ' . $database->getError());
    }
}

// the uploaded images and files of all directories
if (is_dir(WB_PATH . MEDIA_DIRECTORY . '/listings')) {
    rm_full_dir(WB_PATH . MEDIA_DIRECTORY . '/listings');
}
