<?php
/**
 * Listings – precheck.php
 * Requirements checked by the WBCE installer. 1.7.0 brings the PDO database class;
 * GD is only needed for image fields and checked when an image is uploaded.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

$PRECHECK = [
    'WBCE_VERSION' => ['VERSION' => '1.7.0', 'OPERATOR' => '>='],
    'PHP_VERSION'  => ['VERSION' => '8.1', 'OPERATOR' => '>='],
];
