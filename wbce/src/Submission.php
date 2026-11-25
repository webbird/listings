<?php
/**
 * Listings – forms of owners in the frontend: add, confirm, ask for edit
 * links, edit, delete. Every request ends in a redirect (POST → redirect → GET).
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Submission
{
    /** People need more than this to fill in a form; bots send at once. */
    private const MIN_SECONDS = 3;

    private int $sectionId;
    private int $pageId;
    private string $pageUrl;
    private array $settings;

    public static function handle(\Frontend $wb): never
    {
        global $database;
        $sectionId = is_string($_POST['section_id'] ?? null) ? (int) $_POST['section_id'] : 0;
        $section = $database->fetchRow(
            'SELECT `page_id`, `module` FROM `{TP}sections` WHERE `section_id` = ?',
            [$sectionId]
        );
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $section === null || $section['module'] !== Module::DIR) {
            header('Location: ' . WB_URL . '/');
            exit;
        }
        $self = new self($sectionId, (int) $section['page_id']);
        if (!Settings::on($self->settings, 'frontend')) {
            $self->redirect($self->pageUrl);
        }
        if (!$wb->checkFTAN()) {
            $self->fail([L_('MOD_LISTINGS:ERR_EXPIRED_FORM')], $self->pageUrl);
        }
        match ($self->post('lst_action')) {
            'create'  => $self->create(),
            'confirm' => $self->confirm(),
            'request' => $self->request(),
            'update'  => $self->update(),
            'delete'  => $self->delete(),
            default   => $self->redirect($self->pageUrl),
        };
    }

    private function __construct(int $sectionId, int $pageId)
    {
        $this->sectionId = $sectionId;
        $this->pageId = $pageId;
        $this->pageUrl = page_link($pageId);
        $this->settings = Settings::load($sectionId);
    }

    private function create(): never
    {
        $formUrl = View::formUrl($this->pageUrl, $this->sectionId, 'new');
        $old = [
            'title'       => $this->post('title'),
            'owner_email' => $this->post('owner_email'),
            'privacy'     => $this->post('privacy'),
            'fields'      => is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [],
        ];
        $this->checkHuman($formUrl, $old);

        $fields = Fields::forOwner(Fields::forSection($this->sectionId));
        [$values, $errors] = Items::check($this->sectionId, $fields, [
            'title'       => $old['title'],
            'owner_email' => $old['owner_email'],
            'fields'      => $old['fields'],
        ], null);
        if ($values['owner_email'] === '') {
            $errors[] = L_('MOD_LISTINGS:ERR_REQUIRED', L_('MOD_LISTINGS:OWNER_EMAIL'));
        }
        if ($old['privacy'] !== '1') {
            $errors[] = L_('MOD_LISTINGS:ERR_PRIVACY');
        }
        if ($errors === [] && Tokens::recentCount($this->sectionId, $values['owner_email']) >= Tokens::MAX_PER_HOUR) {
            $errors[] = L_('MOD_LISTINGS:ERR_TOO_MANY');
        }
        if ($errors !== []) {
            $this->fail($errors, $formUrl, $old);
        }

        $values['status'] = 'draft';
        $itemId = Items::save($this->sectionId, $this->pageId, null, $values, 0);
        $token = $itemId === null ? null : Tokens::create($this->sectionId, $itemId, Tokens::CONFIRM, $values['owner_email']);
        if ($token === null) {
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $formUrl, $old);
        }
        unset($_SESSION['mod_listings_form_time'][$this->sectionId]);
        $email = $values['owner_email'];
        $sectionId = $this->sectionId;
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
        [$token, $item] = $this->editToken();
        $editUrl = View::tokenUrl($this->pageUrl, View::PARAM_EDIT, $this->post('token')) . '#lst-' . $this->sectionId;
        $old = ['title' => $this->post('title'), 'fields' => is_array($_POST['fields'] ?? null) ? $_POST['fields'] : []];

        // the owner changes title and own fields; address, expiry and slug stay
        [$values, $errors] = Items::check($this->sectionId, Fields::forOwner(Fields::forSection($this->sectionId)), [
            'title'   => $old['title'],
            'slug'    => $item['slug'],
            'expires' => $item['expires_at'] > 0 ? date('Y-m-d', $item['expires_at']) : '',
            'fields'  => $old['fields'],
        ], $item);
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
        if (Items::save($this->sectionId, $this->pageId, $item, $values, 0) === null) {
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $editUrl, $old);
        }
        $nowWaiting = $status === 'pending' && $item['status'] !== 'pending';
        $sectionId = $this->sectionId;
        $saved = ['owner_email' => $item['owner_email'], 'item_id' => $item['item_id']] + $values;
        $this->succeed(
            L_($status === 'pending' ? 'MOD_LISTINGS:MSG_SAVED_WAITING' : 'MOD_LISTINGS:MSG_SAVED'),
            $editUrl,
            $nowWaiting ? static fn () => Mail::notifyAdmin($sectionId, $saved, true) : null
        );
    }

    private function delete(): never
    {
        [, $item] = $this->editToken();
        if (!Items::delete($this->sectionId, $item['item_id'])) {
            $this->fail([L_('MOD_LISTINGS:ERR_DATABASE')], $this->pageUrl);
        }
        $this->succeed(L_('MOD_LISTINGS:MSG_DELETED'), $this->pageUrl);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    private function editToken(): array
    {
        $token = Tokens::find($this->post('token'), Tokens::EDIT);
        $item = $token !== null && $token['section_id'] === $this->sectionId ? Items::find($this->sectionId, $token['item_id']) : null;
        if ($item === null) {
            $this->fail([L_('MOD_LISTINGS:LINK_INVALID')], $this->pageUrl);
        }
        return [$token, $item];
    }

    /** Honeypot, minimum time, captcha. */
    private function checkHuman(string $formUrl, array $old): void
    {
        $shown = $_SESSION['mod_listings_form_time'][$this->sectionId] ?? null;
        if ($this->post('lst_hp') !== '' || !is_int($shown) || time() - $shown < self::MIN_SECONDS) {
            $this->fail([L_('MOD_LISTINGS:ERR_REJECTED')], $formUrl, $old);
        }
        if (Settings::on($this->settings, 'captcha') && class_exists('Captcha')
            && !\Captcha::verify($this->post('captcha'), 'lst' . $this->sectionId)) {
            $this->fail([L_('MOD_LISTINGS:ERR_CAPTCHA')], $formUrl, $old);
        }
    }

    private function fail(array $errors, string $url, ?array $old = null): never
    {
        Module::flash($this->sectionId, ['errors' => $errors, 'old' => $old], 'fe');
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
