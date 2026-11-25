<?php
/**
 * Listings – bootstrap.php
 * Registers the module classes and loads the language strings.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

WbAuto::AddPsr4('Listings', __DIR__ . '/src');
Lang::loadLanguage(__DIR__);
