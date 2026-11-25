<?php
/**
 * Listings – install.php
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

$database->importSql(__DIR__ . '/install_struct.sql');
if ($database->hasError()) {
    log_err('Listings: ' . $database->getError());
}
