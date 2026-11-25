<?php
/**
 * Listings – view.php
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

require_once __DIR__ . '/bootstrap.php';

echo Listings\View::render((int) $section_id, (int) $page_id);
