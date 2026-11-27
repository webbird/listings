<?php
/**
 * Listings – save.php
 * Handles every backend form of a section and redirects back (POST → redirect → GET).
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

// SCRIPT_FILENAME keeps the junction path during development; relative paths and __DIR__ resolve it
require dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php';

// PHP drops a POST above post_max_size completely, FTAN and IDs included: back to the
// section with the reason instead of a security error. Nothing is written.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    require_once __DIR__ . '/bootstrap.php';
    $to = Listings\Module::modifyUrl((int) ($_GET['page_id'] ?? 0), (int) ($_GET['section_id'] ?? 0), ['lst_error' => 'post_too_large']);
    // with display_errors on, PHP has printed its warning already and a Location header fails
    headers_sent() ? print('<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($to) . '">') : header('Location: ' . $to);
    exit;
}

$admin_header = false;
require WB_PATH . '/modules/admin.php';
require_once __DIR__ . '/bootstrap.php';

use Listings\Fields;
use Listings\Files;
use Listings\Import;
use Listings\Items;
use Listings\Layouts;
use Listings\Log;
use Listings\Mail;
use Listings\Module;
use Listings\Presets;
use Listings\Settings;
use Listings\Taxonomy;
use Listings\View;

$sectionId = (int) $section_id;
$pageId = (int) $page_id;

// admin.php checks the page rights; that the section is ours and on that page is checked here
if (!$admin->checkFTAN()
    || $sectionId === 0
    || !isset($section)
    || (int) $section['page_id'] !== $pageId
    || $section['module'] !== Module::DIR
) {
    Module::denyAjaxToken();
    $admin->print_header();
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'], ADMIN_URL . '/pages/modify.php?page_id=' . $pageId);
}

$post = static fn (string $name): string => is_string($_POST[$name] ?? null) ? $_POST[$name] : '';
$back = static function (array $params) use ($pageId, $sectionId): never {
    header('Location: ' . Module::modifyUrl($pageId, $sectionId, $params));
    exit;
};
$done = static function (string $message, array $params) use ($admin, $sectionId, $back): never {
    $admin->touchSection($sectionId);
    (new Alerts())->sessionToast($message);
    $back($params);
};
$fail = static function (array $errors, array $params, ?array $old = null) use ($sectionId, $back): never {
    Module::flash($sectionId, ['errors' => $errors, 'old' => $old]);
    $back($params);
};
$dbError = L_('MOD_LISTINGS:ERR_DATABASE');
$json = static function (array $data): never {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};
// approve or reject: status, log, mail to the owner; the message for the admin, or null on a database error
$changeStatus = static function (array $item, string $status) use ($sectionId, $admin): ?string {
    if (!Items::setStatus($sectionId, $item['item_id'], $status)) {
        return null;
    }
    Log::add($sectionId, $item['item_id'], 'status', 'admin', (int) $admin->get_user_id(), $status);
    $mailed = Mail::status($sectionId, $item, $status);
    $message = L_($status === 'published' ? 'MOD_LISTINGS:MSG_APPROVED' : 'MOD_LISTINGS:MSG_REJECTED', h($item['title']));
    if ($item['owner_email'] !== '' && !$mailed) {
        $message .= ' ' . L_('MOD_LISTINGS:MSG_MAIL_FAILED');
    }
    return $message;
};

switch ($post('lst_action')) {
    case 'item_save':
        $existing = null;
        if (isset($_POST['lst_item'])) {
            $itemId = Module::checkId($admin, 'lst_item', 'item');
            $existing = $itemId > 0 ? Items::find($sectionId, $itemId) : null;
            if ($existing === null) {
                $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'items']);
            }
        }
        $formParams = ['lst_view' => 'item'] + ($existing === null ? [] : ['lst_item' => $existing['item_id']]);
        $fields = Fields::forSection($sectionId);
        [$values, $errors] = Items::check($sectionId, $fields, $_POST, $existing);
        // uploads are only prepared when the rest is valid: the browser cannot send them again with the old values
        $files = ['new' => [], 'remove' => [], 'order' => []];
        if ($errors === []) {
            [$files, $errors] = Files::check($sectionId, $fields, $existing['item_id'] ?? null, $_FILES['uploads'] ?? null, $_POST['files_remove'] ?? null, $_POST['files_order'] ?? null);
        }
        if ($errors !== []) {
            $fail($errors, $formParams, [
                'title'   => $post('title'),
                'slug'    => $post('slug'),
                'status'  => $post('status'),
                'owner_email' => $post('owner_email'),
                'expires' => $post('expires'),
                'fields'  => is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [],
                'categories' => is_array($_POST['categories'] ?? null) ? $_POST['categories'] : [],
                'tags'    => $post('tags'),
            ]);
        }
        $savedId = Items::save($sectionId, $pageId, $existing, $values, (int) $admin->get_user_id());
        [$cats, $tags] = Taxonomy::fromInput($sectionId, $_POST, Settings::load($sectionId)['tags_mode'], true);
        if ($savedId === null) {
            Files::discard($files);
            $fail([$dbError], $formParams);
        }
        if (!Files::store($sectionId, $savedId, $files) || !Taxonomy::assign($sectionId, $savedId, $cats, $tags)) {
            $fail([$dbError], ['lst_view' => 'item', 'lst_item' => $savedId]);
        }
        Log::add($sectionId, $savedId, $existing === null ? 'created' : 'updated', 'admin', (int) $admin->get_user_id(), $values['title']);
        // the owner hears of a decision, also when it is taken in the form
        if ($existing !== null && $existing['status'] !== $values['status']) {
            Log::add($sectionId, $savedId, 'status', 'admin', (int) $admin->get_user_id(), $values['status']);
            Mail::status($sectionId, ['slug' => $values['slug'], 'title' => $values['title'], 'owner_email' => $values['owner_email'] ?? $existing['owner_email']], $values['status']);
        }
        $done(
            L_('MOD_LISTINGS:SAVED_ITEM', h($values['title'])),
            $post('lst_then') === 'edit' ? ['lst_view' => 'item', 'lst_item' => $savedId] : ['lst_view' => 'items']
        );

    case 'item_status':
        // the buttons in the list send this by fetch; the key stays valid for the other rows (peek)
        $ajax = Module::isAjax();
        $item = Items::find($sectionId, Module::checkId($admin, 'lst_item', 'item', $ajax));
        $status = $post('lst_status');
        if ($item === null || !in_array($status, ['published', 'rejected'], true)) {
            Module::denyAjaxToken();
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'items']);
        }
        $message = $changeStatus($item, $status);
        if ($message === null) {
            if ($ajax) {
                $json(['ok' => false, 'message' => $dbError]);
            }
            $fail([$dbError], ['lst_view' => 'items']);
        }
        if ($ajax) {
            $admin->touchSection($sectionId);
            $json([
                'ok'      => true,
                'message' => $message,
                'status'  => $status,
                'label'   => L_('MOD_LISTINGS:STATUS_' . strtoupper($status)),
                'waiting' => Items::count($sectionId, 'pending'),
            ]);
        }
        $done($message, ['lst_view' => 'items']);

    case 'item_bulk':
        // the page lists its entries in one key; the boxes name positions in that list, never IDs
        $listed = Module::checkIdList($admin, 'lst_items', 'items');
        $picked = is_array($_POST['lst_pick'] ?? null) ? array_map('intval', array_filter($_POST['lst_pick'], 'is_string')) : [];
        $ids = array_values(array_unique(array_intersect_key($listed, array_flip($picked))));
        $bulk = $post('lst_bulk');
        if ($listed === [] || !in_array($bulk, ['published', 'rejected', 'delete'], true)) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'items']);
        }
        if ($ids === []) {
            $fail([L_('MOD_LISTINGS:ERR_NOTHING_PICKED')], ['lst_view' => 'items']);
        }
        $count = 0;
        $mailFailed = false;
        foreach ($ids as $id) {
            $item = Items::find($sectionId, $id);
            if ($item === null) {
                continue;
            }
            if ($bulk === 'delete') {
                if (Items::delete($sectionId, $id)) {
                    Log::add($sectionId, $id, 'deleted', 'admin', (int) $admin->get_user_id(), $item['title']);
                    $count++;
                }
                continue;
            }
            if ($item['status'] === $bulk) {
                continue;
            }
            $message = $changeStatus($item, $bulk);
            if ($message !== null) {
                $count++;
                $mailFailed = $mailFailed || str_contains($message, L_('MOD_LISTINGS:MSG_MAIL_FAILED'));
            }
        }
        $done(
            L_('MOD_LISTINGS:MSG_BULK_' . strtoupper($bulk), $count) . ($mailFailed ? ' ' . L_('MOD_LISTINGS:MSG_MAIL_FAILED') : ''),
            ['lst_view' => 'items']
        );

    case 'item_delete':
        $item = Items::find($sectionId, Module::checkId($admin, 'lst_item', 'item'));
        if ($item === null) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'items']);
        }
        if (!Items::delete($sectionId, $item['item_id'])) {
            $fail([$dbError], ['lst_view' => 'items']);
        }
        Log::add($sectionId, $item['item_id'], 'deleted', 'admin', (int) $admin->get_user_id(), $item['title']);
        $done(L_('MOD_LISTINGS:DELETED_ITEM', h($item['title'])), ['lst_view' => 'items']);

    case 'field_save':
        $existing = null;
        if (isset($_POST['lst_field'])) {
            $existing = Fields::find($sectionId, Module::checkId($admin, 'lst_field', 'field'));
            if ($existing === null) {
                $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'fields']);
            }
        }
        $formParams = ['lst_view' => 'field'] + ($existing === null ? [] : ['lst_field' => $existing['field_id']]);
        $nameLocked = $existing !== null && Items::count($sectionId) > 0;
        [$data, $errors] = Fields::check($sectionId, $_POST, $existing, $nameLocked);
        if ($errors !== []) {
            $old = [];
            foreach (['label', 'name', 'type', 'help', 'required', 'visibility', 'in_list', 'editable', 'filterable', 'choices', 'maxlength', 'min', 'max'] as $key) {
                $old[$key] = $post($key);
            }
            $fail($errors, $formParams, $old);
        }
        if (Fields::save($sectionId, $existing === null ? null : $existing['field_id'], $data) === null) {
            $fail([$dbError], $formParams);
        }
        $done(L_('MOD_LISTINGS:SAVED_FIELD', h($data['label'])), ['lst_view' => 'fields']);

    case 'field_delete':
        $field = Fields::find($sectionId, Module::checkId($admin, 'lst_field', 'field'));
        if ($field === null) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'fields']);
        }
        if (!Fields::delete($sectionId, $field['field_id'])) {
            $fail([$dbError], ['lst_view' => 'fields']);
        }
        $done(L_('MOD_LISTINGS:DELETED_FIELD', h($field['label'])), ['lst_view' => 'fields']);

    case 'field_move':
        $field = Fields::find($sectionId, Module::checkId($admin, 'lst_field', 'field'));
        if ($field === null) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'fields']);
        }
        if (!Fields::move($sectionId, $field['field_id'], (int) $post('lst_dir'))) {
            $fail([$dbError], ['lst_view' => 'fields']);
        }
        $back(['lst_view' => 'fields']);

    case 'field_sort':
        // after dragging, by fetch: lst_order names positions in the list the page was shown with
        $listed = Module::checkIdList($admin, 'lst_fields', 'fields', true);
        $order = array_map('intval', explode(',', $post('lst_order')));
        $sorted = $order;
        sort($sorted);
        if ($listed === [] || $sorted !== range(0, count($listed) - 1)) {
            Module::denyAjaxToken();
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'fields']);
        }
        $ok = Fields::reorder($sectionId, array_map(static fn ($i) => $listed[$i], $order));
        if (Module::isAjax()) {
            if ($ok) {
                $admin->touchSection($sectionId);
            }
            $json(['ok' => $ok, 'message' => $ok ? L_('MOD_LISTINGS:MSG_ORDER_SAVED') : L_('MOD_LISTINGS:ERR_ORDER_STALE')]);
        }
        $ok ? $back(['lst_view' => 'fields']) : $fail([L_('MOD_LISTINGS:ERR_ORDER_STALE')], ['lst_view' => 'fields']);

    case 'layouts_save':
        $values = [];
        foreach (Layouts::NAMES as $name) {
            $values['layout_' . $name] = $post('reset_' . $name) === '1' ? '' : Layouts::normalise($name, $post('layout_' . $name));
        }
        $values['form_intro'] = mb_substr(trim(str_replace(["\r\n", "\r"], "\n", $post('form_intro'))), 0, Settings::FORM_INTRO_MAX);

        // the preview buttons send the same form to a new window: render, store nothing
        $preview = $post('lst_preview');
        if (in_array($preview, ['list', 'detail', 'form'], true)) {
            $settings = $values + Settings::load($sectionId);
            foreach (Layouts::NAMES as $name) {
                // a reset layout previews as the default
                $settings['layout_' . $name] = $values['layout_' . $name];
            }
            $html = View::preview($sectionId, $pageId, $preview, $settings);
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Robots-Tag: noindex');
            echo '<!DOCTYPE html><html lang="', h(strtolower(LANGUAGE)), '"><head><meta charset="utf-8">',
                '<meta name="viewport" content="width=device-width, initial-scale=1"><title>', h(L_('MOD_LISTINGS:PREVIEW')), '</title>',
                '<link rel="stylesheet" href="', h(WB_URL . '/modules/' . Module::DIR . '/assets/frontend.css'), '">',
                '<style>body{font-family:system-ui,sans-serif;max-width:60rem;margin:0 auto;padding:1rem 1.5rem;line-height:1.5}',
                '.lst-preview-note{margin:0 0 1.5rem;padding:.5rem .75rem;background:#fff4d6;border-left:3px solid #c80}</style></head><body>',
                '<p class="lst-preview-note">', h(L_('MOD_LISTINGS:PREVIEW_NOTE')), '</p>', $html, '</body></html>';
            exit;
        }

        [$mails, $errors, $sent] = Mail::checkTemplates($_POST, Mail::language($sectionId));
        if ($errors !== []) {
            $fail($errors, ['lst_view' => 'layouts'], $values + ['mails_sent' => $sent]);
        }
        $values['mails'] = $mails;
        $error = Settings::save($sectionId, $values);
        if ($error !== null) {
            $fail([$dbError], ['lst_view' => 'layouts'], $values);
        }
        $done(L_('MOD_LISTINGS:SAVED_LAYOUTS'), ['lst_view' => 'layouts']);

    case 'settings_save':
        [$values, $errors] = Settings::check($_POST);
        if ($errors !== []) {
            $fail($errors, ['lst_view' => 'settings'], $values);
        }
        if (Settings::save($sectionId, $values) !== null) {
            $fail([$dbError], ['lst_view' => 'settings']);
        }
        $done(L_('MOD_LISTINGS:SAVED_SETTINGS'), ['lst_view' => 'settings']);

    case 'preset_export':
        // a download, not a redirect: field set, settings, categories, tags, edited layouts — no entries
        $name = trim(preg_replace('/\s+/u', ' ', $post('preset_name')) ?? '');
        if ($name === '') {
            $name = (string) $database->fetchValue('SELECT `page_title` FROM `{TP}pages` WHERE `page_id` = ?', [$pageId]);
        }
        $json = json_encode(Presets::export($sectionId, mb_substr($name, 0, 100)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . Presets::fileName($name) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo $json;
        exit;

    case 'preset_apply':
        $preset = null;
        $error = null;
        $key = $post('preset_key');
        if ($key !== '') {
            $preset = Presets::find($key);
            $error = $preset === null ? L_('MOD_LISTINGS:ERR_PRESET_FORMAT') : null;
        } else {
            $upload = $_FILES['preset_file'] ?? null;
            $ok = is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                && is_uploaded_file((string) $upload['tmp_name']);
            if (!$ok) {
                $error = L_('MOD_LISTINGS:ERR_PRESET_NO_FILE');
            } elseif ((int) $upload['size'] > Presets::MAX_BYTES) {
                $error = L_('MOD_LISTINGS:ERR_PRESET_SIZE');
            } else {
                [$preset, $error] = Presets::parse((string) file_get_contents((string) $upload['tmp_name']));
            }
        }
        if ($preset === null) {
            $fail([$error ?? L_('MOD_LISTINGS:ERR_PRESET_FORMAT')], ['lst_view' => 'presets']);
        }
        $errors = Presets::apply($sectionId, $preset);
        if ($errors !== []) {
            $fail($errors, ['lst_view' => 'presets']);
        }
        Log::add($sectionId, 0, 'preset', 'admin', (int) $admin->get_user_id(), Presets::text($preset['name'] ?? ''));
        $done(L_('MOD_LISTINGS:MSG_PRESET_APPLIED', h(Presets::text($preset['name'] ?? ''))), ['lst_view' => 'fields']);

    case 'category_save':
        $existing = null;
        if (isset($_POST['lst_cat'])) {
            $existing = Taxonomy::findCategory($sectionId, Module::checkId($admin, 'lst_cat', 'category'));
            if ($existing === null) {
                $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'categories']);
            }
        }
        $formParams = ['lst_view' => 'category'] + ($existing === null ? [] : ['lst_cat' => $existing['cat_id']]);
        [$data, $errors] = Taxonomy::checkCategory($sectionId, $_POST, $existing);
        if ($errors !== []) {
            $old = [];
            foreach (['name', 'slug', 'parent_id', 'description', 'visible'] as $key) {
                $old[$key] = $post($key);
            }
            $fail($errors, $formParams, $old);
        }
        if (Taxonomy::saveCategory($sectionId, $existing, $data) === null) {
            $fail([$dbError], $formParams);
        }
        $done(L_('MOD_LISTINGS:SAVED_CATEGORY', h($data['name'])), ['lst_view' => 'categories']);

    case 'category_delete':
    case 'category_move':
        $cat = Taxonomy::findCategory($sectionId, Module::checkId($admin, 'lst_cat', 'category'));
        if ($cat === null) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'categories']);
        }
        if ($post('lst_action') === 'category_move') {
            if (!Taxonomy::moveCategory($sectionId, $cat, (int) $post('lst_dir'))) {
                $fail([$dbError], ['lst_view' => 'categories']);
            }
            $back(['lst_view' => 'categories']);
        }
        if (!Taxonomy::deleteCategory($sectionId, $cat)) {
            $fail([$dbError], ['lst_view' => 'categories']);
        }
        $done(L_('MOD_LISTINGS:DELETED_CATEGORY', h($cat['name'])), ['lst_view' => 'categories']);

    case 'import_run':
        $source = Module::checkId($admin, 'lst_source', 'import');
        if ($source === 0) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'import']);
        }
        $report = Import::run(
            $source,
            $sectionId,
            $pageId,
            ['fix_encoding' => $post('lst_fix') === '1', 'public_email' => $post('lst_email') === '1'],
            (int) $admin->get_user_id()
        );
        if ($report['errors'] !== []) {
            $fail($report['errors'], ['lst_view' => 'import', 'lst_source' => $source]);
        }
        // the report is shown once on the import page; without its closures it fits the session
        Log::add($sectionId, 0, 'imported', 'admin', (int) $admin->get_user_id(), 'wbProfiles #' . $source . ': ' . $report['counts']['items']);
        Module::flash($sectionId, ['report' => $report]);
        $done(L_('MOD_LISTINGS:IMPORT_DONE', $report['counts']['items']), ['lst_view' => 'import']);

    case 'tag_rename':
    case 'tag_delete':
        $tag = Taxonomy::findTag($sectionId, Module::checkId($admin, 'lst_tag', 'tag'));
        if ($tag === null) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'categories']);
        }
        if ($post('lst_action') === 'tag_rename') {
            [$error, $merged] = Taxonomy::renameTag($sectionId, $tag, $post('name'));
            if ($error !== null) {
                $fail([$error], ['lst_view' => 'categories']);
            }
            $done(L_($merged !== null ? 'MOD_LISTINGS:MERGED_TAG' : 'MOD_LISTINGS:SAVED_TAG', h($merged ?? trim($post('name'))), h($tag['name'])), ['lst_view' => 'categories']);
        }
        if (!Taxonomy::deleteTag($sectionId, $tag)) {
            $fail([$dbError], ['lst_view' => 'categories']);
        }
        $done(L_('MOD_LISTINGS:DELETED_TAG', h($tag['name'])), ['lst_view' => 'categories']);
}

$back([]);
