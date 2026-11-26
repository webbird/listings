<?php
/**
 * Listings – save.php
 * Handles every backend form of a section and redirects back (POST → redirect → GET).
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

// SCRIPT_FILENAME keeps the junction path during development; relative paths and __DIR__ resolve it
require dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php';

$admin_header = false;
require WB_PATH . '/modules/admin.php';
require_once __DIR__ . '/bootstrap.php';

use Listings\Fields;
use Listings\Files;
use Listings\Import;
use Listings\Items;
use Listings\Layouts;
use Listings\Mail;
use Listings\Module;
use Listings\Settings;
use Listings\Taxonomy;

$sectionId = (int) $section_id;
$pageId = (int) $page_id;

// admin.php checks the page rights; that the section is ours and on that page is checked here
if (!$admin->checkFTAN()
    || $sectionId === 0
    || !isset($section)
    || (int) $section['page_id'] !== $pageId
    || $section['module'] !== Module::DIR
) {
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
        $files = ['new' => [], 'remove' => []];
        if ($errors === []) {
            [$files, $errors] = Files::check($sectionId, $fields, $existing['item_id'] ?? null, $_FILES['uploads'] ?? null, $_POST['files_remove'] ?? null);
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
        // the owner hears of a decision, also when it is taken in the form
        if ($existing !== null && $existing['status'] !== $values['status']) {
            Mail::status($sectionId, ['slug' => $values['slug'], 'title' => $values['title'], 'owner_email' => $values['owner_email'] ?? $existing['owner_email']], $values['status']);
        }
        $done(
            L_('MOD_LISTINGS:SAVED_ITEM', h($values['title'])),
            $post('lst_then') === 'edit' ? ['lst_view' => 'item', 'lst_item' => $savedId] : ['lst_view' => 'items']
        );

    case 'item_status':
        $item = Items::find($sectionId, Module::checkId($admin, 'lst_item', 'item'));
        $status = $post('lst_status');
        if ($item === null || !in_array($status, ['published', 'rejected'], true)) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'items']);
        }
        if (!Items::setStatus($sectionId, $item['item_id'], $status)) {
            $fail([$dbError], ['lst_view' => 'items']);
        }
        $mailed = Mail::status($sectionId, $item, $status);
        $message = L_($status === 'published' ? 'MOD_LISTINGS:MSG_APPROVED' : 'MOD_LISTINGS:MSG_REJECTED', h($item['title']));
        if ($item['owner_email'] !== '' && !$mailed) {
            $message .= ' ' . L_('MOD_LISTINGS:MSG_MAIL_FAILED');
        }
        $done($message, ['lst_view' => 'items']);

    case 'item_delete':
        $item = Items::find($sectionId, Module::checkId($admin, 'lst_item', 'item'));
        if ($item === null) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'items']);
        }
        if (!Items::delete($sectionId, $item['item_id'])) {
            $fail([$dbError], ['lst_view' => 'items']);
        }
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

    case 'layouts_save':
        $values = [];
        foreach (Layouts::NAMES as $name) {
            $values['layout_' . $name] = $post('reset_' . $name) === '1' ? '' : Layouts::normalise($name, $post('layout_' . $name));
        }
        $error = Settings::save($sectionId, $values);
        if ($error !== null) {
            $fail([$dbError], ['lst_view' => 'layouts'], $values);
        }
        $done(L_('MOD_LISTINGS:SAVED_LAYOUTS'), ['lst_view' => 'layouts']);

    case 'settings_save':
        $perPage = $post('per_page');
        $sort = $post('sort');
        $errors = [];
        if (!ctype_digit($perPage) || (int) $perPage < 1 || (int) $perPage > Settings::PER_PAGE_MAX) {
            $errors[] = L_('MOD_LISTINGS:ERR_PER_PAGE', Settings::PER_PAGE_MAX);
        }
        if (!array_key_exists($sort, Settings::SORTS)) {
            $sort = 'newest';
        }
        $notify = strtolower(trim($post('notify_email')));
        if ($notify !== '' && filter_var($notify, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = L_('MOD_LISTINGS:ERR_EMAIL', L_('MOD_LISTINGS:NOTIFY_EMAIL'));
        }
        $runtime = trim($post('runtime_days'));
        $remind = trim($post('remind_days'));
        if (!ctype_digit($runtime) || (int) $runtime > Settings::RUNTIME_MAX) {
            $errors[] = L_('MOD_LISTINGS:ERR_RUNTIME', Settings::RUNTIME_MAX);
        }
        if (!ctype_digit($remind) || (int) $remind > 365) {
            $errors[] = L_('MOD_LISTINGS:ERR_REMIND');
        }
        $values = [
            'per_page'     => $perPage,
            'sort'         => $sort,
            'notify_email' => $notify,
            'owner_mode'   => in_array($post('owner_mode'), Settings::OWNER_MODES, true) ? $post('owner_mode') : 'email',
            'runtime_days' => $runtime,
            'remind_days'  => $remind,
            'tags_mode'    => in_array($post('tags_mode'), Taxonomy::TAG_MODES, true) ? $post('tags_mode') : 'off',
        ];
        foreach (['frontend', 'approval', 'recheck', 'captcha', 'contact', 'filter_form', 'rss'] as $flag) {
            $values[$flag] = $post($flag) === '1' ? '1' : '0';
        }
        if ($errors !== []) {
            $fail($errors, ['lst_view' => 'settings'], $values);
        }
        $values['per_page'] = (string) (int) $perPage;
        $values['runtime_days'] = (string) (int) $runtime;
        $values['remind_days'] = (string) (int) $remind;
        if (Settings::save($sectionId, $values) !== null) {
            $fail([$dbError], ['lst_view' => 'settings']);
        }
        $done(L_('MOD_LISTINGS:SAVED_SETTINGS'), ['lst_view' => 'settings']);

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
        Module::flash($sectionId, ['report' => $report]);
        $done(L_('MOD_LISTINGS:IMPORT_DONE', $report['counts']['items']), ['lst_view' => 'import']);

    case 'tag_rename':
    case 'tag_delete':
        $tag = Taxonomy::findTag($sectionId, Module::checkId($admin, 'lst_tag', 'tag'));
        if ($tag === null) {
            $fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], ['lst_view' => 'categories']);
        }
        if ($post('lst_action') === 'tag_rename') {
            $error = Taxonomy::renameTag($sectionId, $tag, $post('name'));
            if ($error !== null) {
                $fail([$error], ['lst_view' => 'categories']);
            }
            $done(L_('MOD_LISTINGS:SAVED_TAG', h(trim($post('name')))), ['lst_view' => 'categories']);
        }
        if (!Taxonomy::deleteTag($sectionId, $tag)) {
            $fail([$dbError], ['lst_view' => 'categories']);
        }
        $done(L_('MOD_LISTINGS:DELETED_TAG', h($tag['name'])), ['lst_view' => 'categories']);
}

$back([]);
