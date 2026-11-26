<?php
/**
 * Listings – forms of owners and visitors in the frontend: add, confirm,
 * ask for edit links, edit, extend, delete, contact the owner. Owners prove
 * who they are by a mailed link or, in account mode, by their WBCE frontend
 * login. Every request ends in a redirect (POST → redirect → GET).
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Submission
{
    /** People need more than this to fill in a form; bots send at once. */
    private const MIN_SECONDS = 3;

    /** Contact messages per visitor session within an hour. */
    private const CONTACT_PER_HOUR = 3;

    private int $sectionId;
    private int $pageId;
    private string $pageUrl;
    private array $settings;
    private \Frontend $wb;
    private ?array $user;

    public static function handle(\Frontend $wb): never
    {
        global $database;
        // beyond post_max_size PHP drops every field; the form names its section in the URL as well
        $tooBig = $_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
        $sectionId = is_string($_POST['section_id'] ?? null) ? (int) $_POST['section_id']
            : ($tooBig && is_string($_GET['section_id'] ?? null) ? (int) $_GET['section_id'] : 0);
        $section = $database->fetchRow(
            'SELECT `page_id`, `module` FROM `{TP}sections` WHERE `section_id` = ?',
            [$sectionId]
        );
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $section === null || $section['module'] !== Module::DIR) {
            header('Location: ' . WB_URL . '/');
            exit;
        }
        $self = new self($wb, $sectionId, (int) $section['page_id']);
        if ($tooBig) {
            // back to the form the visitor came from, if it is on this site
            $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            $self->fail(
                [L_('MOD_LISTINGS:ERR_POST_SIZE', Files::formatSize(Files::maxPostBytes()))],
                str_starts_with($referer, WB_URL . '/') ? $referer : $self->pageUrl
            );
        }
        $action = $self->post('lst_action');
        // the contact form belongs to the public detail page, not to entries by visitors
        if ($action !== 'contact' && !Settings::on($self->settings, 'frontend')) {
            $self->redirect($self->pageUrl);
        }
        if (!$wb->checkFTAN()) {
            $self->fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], $self->pageUrl);
        }
        match ($action) {
            'create'  => $self->create(),
            'confirm' => $self->confirm(),
            'request' => $self->request(),
            'update'  => $self->update(),
            'extend'  => $self->extend(),
            'delete'  => $self->delete(),
            'contact' => $self->contact(),
            default   => $self->redirect($self->pageUrl),
        };
    }

    private function __construct(\Frontend $wb, int $sectionId, int $pageId)
    {
        $this->wb = $wb;
        $this->sectionId = $sectionId;
        $this->pageId = $pageId;
        $this->pageUrl = page_link($pageId);
        $this->settings = Settings::load($sectionId);
        $this->user = View::user();
    }

    private function create(): never
    {
        $formUrl = View::formUrl($this->pageUrl, $this->sectionId, 'new');
        $account = Settings::accountMode($this->settings);
        if ($account && $this->user === null) {
            $this->redirect($formUrl);
        }
        $old = [
            'title'       => $this->post('title'),
            'owner_email' => $this->post('owner_email'),
            'privacy'     => $this->post('privacy'),
            'fields'      => is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [],
        ] + $this->taxonomyInput();
        // a logged-in owner has proven to be a person already
        $this->checkHuman($formUrl, $old, $this->user === null);

        $fields = Fields::forOwner(Fields::forSection($this->sectionId));
        [$values, $errors] = Items::check($this->sectionId, $fields, [
            'title'       => $old['title'],
            'owner_email' => $account ? $this->user['email'] : $old['owner_email'],
            'fields'      => $old['fields'],
        ], null);
        if ($values['owner_email'] === '') {
            $errors[] = L_('MOD_LISTINGS:ERR_REQUIRED', L_('MOD_LISTINGS:OWNER_EMAIL'));
        }
        if ($old['privacy'] !== '1') {
            $errors[] = L_('MOD_LISTINGS:ERR_PRIVACY');
        }
        if (!$account && $errors === [] && Tokens::recentCount($this->sectionId, $values['owner_email']) >= Tokens::MAX_PER_HOUR) {
            $errors[] = L_('MOD_LISTINGS:ERR_TOO_MANY');
        }
        $files = ['new' => [], 'remove' => []];
        if ($errors === []) {
            [$files, $errors] = Files::check($this->sectionId, $fields, null, $_FILES['uploads'] ?? null, null);
        }
        if ($errors !== []) {
            $this->fail($errors, $formUrl, $old);
        }
        unset($_SESSION['mod_listings_form_time'][$this->sectionId]);
        $sectionId = $this->sectionId;

        if ($account) {
            // the account is confirmed already: no mail, straight to approval or online
            $approval = Settings::on($this->settings, 'approval');
            $values['status'] = $approval ? 'pending' : 'published';
            $values['owner_user_id'] = $this->user['id'];
            $itemId = Items::save($this->sectionId, $this->pageId, null, $values, $this->user['id']);
            if ($itemId === null) {
                Files::discard($files);
            }
            if ($itemId === null || !Files::store($this->sectionId, $itemId, $files) || !$this->assign($itemId)) {
                $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $formUrl, $old);
            }
            $saved = ['item_id' => $itemId] + $values;
            $this->succeed(
                L_($approval ? 'MOD_LISTINGS:MSG_ACCOUNT_WAITING' : 'MOD_LISTINGS:MSG_ACCOUNT_PUBLISHED'),
                View::formUrl($this->pageUrl, $this->sectionId, 'mine'),
                static fn () => Mail::notifyAdmin($sectionId, $saved, $approval)
            );
        }

        $values['status'] = 'draft';
        $itemId = Items::save($this->sectionId, $this->pageId, null, $values, 0);
        if ($itemId === null) {
            Files::discard($files);
        }
        $token = $itemId === null || !Files::store($this->sectionId, $itemId, $files) || !$this->assign($itemId) ? null : Tokens::create($this->sectionId, $itemId, Tokens::CONFIRM, $values['owner_email']);
        if ($token === null) {
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $formUrl, $old);
        }
        $email = $values['owner_email'];
        $this->succeed(L_('MOD_LISTINGS:MSG_CHECK_MAIL'), $this->pageUrl, static fn () => Mail::confirm($sectionId, $email, $token));
    }

    private function confirm(): never
    {
        $token = Tokens::find($this->post('token'), Tokens::CONFIRM);
        $item = $token !== null && $token['section_id'] === $this->sectionId ? Items::find($this->sectionId, $token['item_id']) : null;
        if ($item === null || $item['status'] !== 'draft') {
            $this->fail([L_('MOD_LISTINGS:LINK_INVALID')], $this->pageUrl);
        }
        $approval = Settings::on($this->settings, 'approval');
        $status = $approval ? 'pending' : 'published';
        if (!Items::setStatus($this->sectionId, $item['item_id'], $status) || !Tokens::markUsed($token['token_id'])) {
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $this->pageUrl);
        }
        $sectionId = $this->sectionId;
        $this->succeed(
            L_($approval ? 'MOD_LISTINGS:MSG_CONFIRMED_WAITING' : 'MOD_LISTINGS:MSG_CONFIRMED_PUBLISHED'),
            $approval ? $this->pageUrl : View::itemUrl($this->pageUrl, $item['slug']),
            static fn () => Mail::notifyAdmin($sectionId, $item, $approval)
        );
    }

    private function request(): never
    {
        if (Settings::accountMode($this->settings)) {
            $this->redirect(View::formUrl($this->pageUrl, $this->sectionId, 'mine'));
        }
        $formUrl = View::formUrl($this->pageUrl, $this->sectionId, 'link');
        $email = strtolower(trim($this->post('owner_email')));
        $this->checkHuman($formUrl, ['owner_email' => $email]);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->fail([L_('MOD_LISTINGS:ERR_EMAIL', L_('MOD_LISTINGS:OWNER_EMAIL'))], $formUrl, ['owner_email' => $email]);
        }
        unset($_SESSION['mod_listings_form_time'][$this->sectionId]);

        // the same answer whether the address has entries or not: nobody learns who is listed
        $message = L_('MOD_LISTINGS:MSG_LINK_SENT');
        $items = Tokens::recentCount($this->sectionId, $email) < Tokens::MAX_PER_HOUR ? Items::ownedBy($this->sectionId, $email) : [];
        $links = [];
        foreach ($items as $item) {
            $token = Tokens::create($this->sectionId, $item['item_id'], Tokens::EDIT, $email);
            if ($token !== null) {
                $links[] = ['title' => $item['title'], 'token' => $token];
            }
        }
        $sectionId = $this->sectionId;
        $this->succeed($message, $this->pageUrl, $links === [] ? null : static fn () => Mail::editLinks($sectionId, $email, $links));
    }

    private function update(): never
    {
        [$item, $editUrl] = $this->ownedItem();
        $old = ['title' => $this->post('title'), 'fields' => is_array($_POST['fields'] ?? null) ? $_POST['fields'] : []] + $this->taxonomyInput();

        // the owner changes title and own fields; expiry and slug stay
        $fields = Fields::forOwner(Fields::forSection($this->sectionId));
        [$values, $errors] = Items::check($this->sectionId, $fields, [
            'title'   => $old['title'],
            'slug'    => $item['slug'],
            'expires' => $item['expires_at'] > 0 ? date('Y-m-d', $item['expires_at']) : '',
            'fields'  => $old['fields'],
        ], $item);
        $files = ['new' => [], 'remove' => []];
        if ($errors === []) {
            [$files, $errors] = Files::check($this->sectionId, $fields, $item['item_id'], $_FILES['uploads'] ?? null, $_POST['files_remove'] ?? null);
        }
        if ($errors !== []) {
            $this->fail($errors, $editUrl, $old);
        }

        $approval = Settings::on($this->settings, 'approval');
        $status = $item['status'];
        if ($status === 'rejected') {
            $status = $approval ? 'pending' : 'published';
        } elseif ($status === 'published' && $approval && Settings::on($this->settings, 'recheck')) {
            $status = 'pending';
        }
        $values['status'] = $status;
        $values['expires_at'] = $item['expires_at'];
        if ($item['owner_user_id'] > 0 && $this->user !== null) {
            // mails follow the address of the account, also after it changed
            $values['owner_email'] = $this->user['email'];
        }
        if (Items::save($this->sectionId, $this->pageId, $item, $values, (int) ($this->user['id'] ?? 0)) === null) {
            Files::discard($files);
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $editUrl, $old);
        }
        if (!Files::store($this->sectionId, $item['item_id'], $files) || !$this->assign($item['item_id'])) {
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $editUrl, $old);
        }
        $nowWaiting = $status === 'pending' && $item['status'] !== 'pending';
        $sectionId = $this->sectionId;
        $saved = ['owner_email' => $values['owner_email'] ?? $item['owner_email'], 'item_id' => $item['item_id']] + $values;
        $this->succeed(
            L_($status === 'pending' ? 'MOD_LISTINGS:MSG_SAVED_WAITING' : 'MOD_LISTINGS:MSG_SAVED'),
            $editUrl,
            $nowWaiting ? static fn () => Mail::notifyAdmin($sectionId, $saved, true) : null
        );
    }

    private function extend(): never
    {
        [$item, $backUrl] = $this->ownedItem(true);
        if ($item['owner_user_id'] > 0 && $this->post('lst_back') === 'mine') {
            $backUrl = View::formUrl($this->pageUrl, $this->sectionId, 'mine');
        }
        if (!Items::extendable($item, $this->settings)) {
            $this->fail([L_('MOD_LISTINGS:ERR_NOT_EXTENDABLE')], $backUrl);
        }
        if (!Items::extend($this->sectionId, $item, $this->settings)) {
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $backUrl);
        }
        $until = Items::find($this->sectionId, $item['item_id'])['expires_at'] ?? 0;
        $this->succeed(L_('MOD_LISTINGS:MSG_EXTENDED', date('d.m.Y', $until)), $backUrl);
    }

    private function delete(): never
    {
        [$item] = $this->ownedItem();
        if (!Items::delete($this->sectionId, $item['item_id'])) {
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $this->pageUrl);
        }
        $this->succeed(
            L_('MOD_LISTINGS:MSG_DELETED'),
            $item['owner_user_id'] > 0 && $this->user !== null ? View::formUrl($this->pageUrl, $this->sectionId, 'mine') : $this->pageUrl
        );
    }

    private function contact(): never
    {
        $item = Items::find($this->sectionId, Module::checkId($this->wb, 'lst_item', 'contact'));
        $visible = $item !== null && $item['status'] === 'published' && ($item['expires_at'] === 0 || $item['expires_at'] > time());
        if (!$visible || !Settings::on($this->settings, 'contact') || $item['owner_email'] === '') {
            $this->fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], $this->pageUrl);
        }
        $backUrl = View::itemUrl($this->pageUrl, $item['slug']) . '#lst-contact';
        $old = [
            'name'    => mb_substr(preg_replace('/\s+/u', ' ', $this->post('contact_name')) ?? '', 0, 100),
            'email'   => strtolower($this->post('contact_email')),
            'message' => str_replace(["\r\n", "\r"], "\n", $this->post('contact_message')),
            'privacy' => $this->post('privacy'),
        ];
        $this->checkHuman($backUrl, $old, $this->user === null, 'contact');

        $errors = [];
        if ($old['name'] === '') {
            $errors[] = L_('MOD_LISTINGS:ERR_REQUIRED', L_('MOD_LISTINGS:CONTACT_NAME'));
        }
        if (filter_var($old['email'], FILTER_VALIDATE_EMAIL) === false || strlen($old['email']) > 255) {
            $errors[] = L_('MOD_LISTINGS:ERR_EMAIL', L_('MOD_LISTINGS:CONTACT_EMAIL'));
        }
        if (mb_strlen($old['message']) < 10) {
            $errors[] = L_('MOD_LISTINGS:ERR_CONTACT_SHORT');
        } elseif (mb_strlen($old['message']) > 5000) {
            $errors[] = L_('MOD_LISTINGS:ERR_TOO_LONG', L_('MOD_LISTINGS:CONTACT_MESSAGE'), 5000);
        }
        if ($old['privacy'] !== '1') {
            $errors[] = L_('MOD_LISTINGS:ERR_PRIVACY');
        }
        $sent = array_filter((array) ($_SESSION['mod_listings_contact'] ?? []), static fn ($t) => is_int($t) && $t > time() - 3600);
        if ($errors === [] && count($sent) >= self::CONTACT_PER_HOUR) {
            $errors[] = L_('MOD_LISTINGS:ERR_TOO_MANY_CONTACT');
        }
        if ($errors !== []) {
            $this->fail($errors, $backUrl, $old, 'contact');
        }
        $sent[] = time();
        $_SESSION['mod_listings_contact'] = array_values($sent);
        unset($_SESSION['mod_listings_form_time'][$this->sectionId]);

        $sectionId = $this->sectionId;
        $this->succeed(
            L_('MOD_LISTINGS:MSG_CONTACT_SENT'),
            $backUrl,
            static fn () => Mail::contact($sectionId, $item, $old['name'], $old['email'], $old['message'])
        );
    }

    /**
     * The entry the request may change, and the URL to return to: by a
     * mailed link (token) or by the logged-in owner (item key).
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function ownedItem(bool $extend = false): array
    {
        $raw = $this->post('token');
        if ($raw !== '') {
            // the reminder mail carries an extend link; the edit link may extend as well
            $token = ($extend ? Tokens::find($raw, Tokens::EXTEND) : null) ?? Tokens::find($raw, Tokens::EDIT);
            $item = $token !== null && $token['section_id'] === $this->sectionId ? Items::find($this->sectionId, $token['item_id']) : null;
            if ($item === null) {
                $this->fail([L_('MOD_LISTINGS:LINK_INVALID')], $this->pageUrl);
            }
            $url = $token['purpose'] === Tokens::EDIT
                ? View::tokenUrl($this->pageUrl, View::PARAM_EDIT, $raw) . '#lst-' . $this->sectionId
                : View::itemUrl($this->pageUrl, $item['slug']);
            return [$item, $url];
        }
        $item = Items::find($this->sectionId, Module::checkId($this->wb, 'lst_item', 'item'));
        if ($item === null || $this->user === null || $item['owner_user_id'] !== $this->user['id']) {
            $this->fail([L_('MOD_LISTINGS:ERR_NOT_YOURS')], View::formUrl($this->pageUrl, $this->sectionId, 'mine'));
        }
        return [$item, View::ownerEditUrl($this->pageUrl, $this->sectionId, $item['item_id'])];
    }

    /** Categories and tags as sent, for the form after an error. */
    private function taxonomyInput(): array
    {
        return [
            'categories' => is_array($_POST['categories'] ?? null) ? array_filter($_POST['categories'], 'is_string') : [],
            'tag_ids'    => is_array($_POST['tag_ids'] ?? null) ? array_filter($_POST['tag_ids'], 'is_string') : [],
            'tags'       => $this->post('tags'),
        ];
    }

    /** Stores the categories and tags the owner chose (visible categories, tags by the tag mode). */
    private function assign(int $itemId): bool
    {
        [$cats, $tags] = Taxonomy::fromInput($this->sectionId, $_POST, $this->settings['tags_mode'] ?? 'off', false);
        return Taxonomy::assign($this->sectionId, $itemId, $cats, $tags);
    }

    /** Honeypot, minimum time, captcha. */
    private function checkHuman(string $formUrl, array $old, bool $captcha = true, string $form = ''): void
    {
        $shown = $_SESSION['mod_listings_form_time'][$this->sectionId] ?? null;
        if ($this->post('lst_hp') !== '' || !is_int($shown) || time() - $shown < self::MIN_SECONDS) {
            $this->fail([L_('MOD_LISTINGS:ERR_REJECTED')], $formUrl, $old, $form);
        }
        if ($captcha && Settings::on($this->settings, 'captcha') && class_exists('Captcha')
            && !\Captcha::verify($this->post('captcha'), 'lst' . $this->sectionId)) {
            $this->fail([L_('MOD_LISTINGS:ERR_CAPTCHA')], $formUrl, $old, $form);
        }
    }

    private function fail(array $errors, string $url, ?array $old = null, string $form = ''): never
    {
        Module::flash($this->sectionId, ['errors' => $errors, 'old' => $old, 'form' => $form], 'fe');
        $this->redirect($url);
    }

    /**
     * Redirects; $after runs once the visitor has the redirect, so a slow
     * mail server does not keep them waiting.
     */
    private function succeed(string $message, string $url, ?callable $after = null): never
    {
        Module::flash($this->sectionId, ['success' => $message], 'fe');
        header('Location: ' . $url);
        if ($after !== null) {
            // the next page reads this session; an open session would wait for $after
            session_write_close();
            ignore_user_abort(true);
            header('Connection: close');
            header('Content-Length: 0');
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            $after();
        }
        exit;
    }

    private function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    private function post(string $name): string
    {
        return is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : '';
    }
}
