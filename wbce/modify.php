<?php
/**
 * Listings – modify.php
 * Runs inside the section loop of admin/pages/modify.php; the work happens
 * in Listings\Backend so no variables leak into that shared scope.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

require_once __DIR__ . '/bootstrap.php';

echo Listings\Backend::render($admin, (int) $page_id, (int) $section_id);
