<?php
/**
 * Listings – upgrade.php
 * The CREATE TABLE statements use IF NOT EXISTS, so running them again is
 * safe and adds new tables; new columns of existing tables follow below.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

$database->importSql(__DIR__ . '/install_struct.sql');
if ($database->hasError()) {
    log_err('Listings: ' . $database->getError());
}

// 0.2: owners may edit a field (or only the admin)
if (!$database->addField('{TP}mod_listings_fields', 'editable', 'TINYINT NOT NULL DEFAULT 1')) {
    log_err('Listings: ' . $database->getError());
}

// 0.3: when the owner was reminded of the expiry
if (!$database->addField('{TP}mod_listings_items', 'reminded_at', 'INT NOT NULL DEFAULT 0')) {
    log_err('Listings: ' . $database->getError());
}
if (!$database->addIndex('{TP}mod_listings_items', 'owner_user', 'section_id, owner_user_id')) {
    log_err('Listings: ' . $database->getError());
}

// 0.4: fields offered as filter in the list (the new tables come from install_struct.sql)
if (!$database->addField('{TP}mod_listings_fields', 'filterable', 'TINYINT NOT NULL DEFAULT 0')) {
    log_err('Listings: ' . $database->getError());
}
