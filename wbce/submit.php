<?php
/**
 * Listings – submit.php
 * Receives the owner forms of the frontend; checks section, FTAN, honeypot
 * and captcha in Listings\Submission and redirects back.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

// SCRIPT_FILENAME keeps the junction path during development; relative paths and __DIR__ resolve it
require dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php';
require_once __DIR__ . '/bootstrap.php';

// global $wb: page_link() and other core helpers look for it there
$wb = new Frontend();
Listings\Submission::handle($wb);
