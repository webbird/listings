<?php
/**
 * Listings – file.php
 * Delivers an image or file of an entry: ?f=<key>, &s=t for the thumbnail.
 * Files::serve() checks that the visitor may see it; the folder itself is
 * closed to direct access.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

$key = is_string($_GET['f'] ?? null) ? $_GET['f'] : '';
if (preg_match('/^[a-f0-9]{32}$/', $key) !== 1) {
    http_response_code(404);
    exit;
}

// SCRIPT_FILENAME keeps the junction path during development; relative paths and __DIR__ resolve it
require dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php';
require_once __DIR__ . '/bootstrap.php';

Listings\Files::serve($key, ($_GET['s'] ?? '') === 't');
