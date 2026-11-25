<?php
/**
 * Listings – uninstall.php
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

foreach (['tokens', 'items', 'fields', 'settings'] as $listingsTable) {
    $database->query('DROP TABLE IF EXISTS `{TP}mod_listings_' . $listingsTable . '`');
    if ($database->hasError()) {
        log_err('Listings: ' . $database->getError());
    }
}
