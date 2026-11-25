<?php
/**
 * Listings – delete.php
 * A section is removed: its entries, fields and settings go with it.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

require_once __DIR__ . '/bootstrap.php';

Listings\Module::deleteSection((int) $section_id);
