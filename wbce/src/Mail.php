<?php
/**
 * Listings – mails to owners and to the admin, plain text, in the language
 * of the page. A confirmation mail goes to an address anybody can type, so
 * it carries nothing the visitor typed: no title, no field values.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Mail
{
    /** Link to confirm a new entry. */
    public static function confirm(int $sectionId, string $email, string $token): bool
    {
        $page = self::page($sectionId);
        if ($page === null) {
            return false;
        }
        $t = self::texts($page['language']);
        $url = View::tokenUrl($page['url'], View::PARAM_CONFIRM, $token);
        return self::send(
            $email,
            $t('MAIL_CONFIRM_SUBJECT', WEBSITE_TITLE),
            $t('MAIL_CONFIRM_TEXT', $page['title'], WEBSITE_TITLE, $url, (int) (Tokens::TTL[Tokens::CONFIRM] / 3600))
                . "\n\n" . $t('MAIL_IGNORE') . "\n\n" . $t('MAIL_FOOTER', WEBSITE_TITLE)
        );
    }

    /**
     * Edit links for the entries of an address.
     *
     * @param list<array{title: string, token: string}> $links
     */
    public static function editLinks(int $sectionId, string $email, array $links): bool
    {
        $page = self::page($sectionId);
        if ($page === null || $links === []) {
            return false;
        }
        $t = self::texts($page['language']);
        $list = '';
        foreach ($links as $link) {
            $list .= $link['title'] . "\n" . View::tokenUrl($page['url'], View::PARAM_EDIT, $link['token']) . "\n\n";
        }
        return self::send(
            $email,
            $t('MAIL_EDIT_SUBJECT', WEBSITE_TITLE),
            $t('MAIL_EDIT_TEXT', $page['title'], (int) (Tokens::TTL[Tokens::EDIT] / 60)) . "\n\n" . $list
                . $t('MAIL_IGNORE') . "\n\n" . $t('MAIL_FOOTER', WEBSITE_TITLE)
        );
    }

    /** To the owner: the entry was published or rejected. */
    public static function status(int $sectionId, array $item, string $status): bool
    {
        $page = self::page($sectionId);
        if ($page === null || $item['owner_email'] === '' || !in_array($status, ['published', 'rejected'], true)) {
            return false;
        }
        $t = self::texts($page['language']);
        $key = strtoupper($status);
        $text = $t('MAIL_' . $key . '_TEXT', $item['title'], $page['title']);
        if ($status === 'published') {
            $text .= "\n\n" . View::itemUrl($page['url'], $item['slug']);
        }
        return self::send(
            $item['owner_email'],
            $t('MAIL_' . $key . '_SUBJECT', $item['title']),
            $text . "\n\n" . $t('MAIL_FOOTER', WEBSITE_TITLE)
        );
    }

    /** To notify_email: a confirmed entry is waiting, or (without approval) went online. */
    public static function notifyAdmin(int $sectionId, array $item, bool $waiting): bool
    {
        $settings = Settings::load($sectionId);
        $to = $settings['notify_email'];
        $page = self::page($sectionId);
        if ($to === '' || $page === null) {
            return false;
        }
        $t = self::texts($page['language']);
        $key = $waiting ? 'WAITING' : 'NEW';
        $url = Module::modifyUrl($page['page_id'], $sectionId, ['lst_view' => 'item', 'lst_item' => $item['item_id']]);
        return self::send(
            $to,
            $t('MAIL_ADMIN_' . $key . '_SUBJECT', $item['title']),
            $t('MAIL_ADMIN_' . $key . '_TEXT', $page['title']) . "\n\n"
                . $item['title'] . "\n" . $t('OWNER_EMAIL') . ': ' . $item['owner_email'] . "\n\n" . $url,
            $item['owner_email']
        );
    }

    /**
     * @return array{page_id: int, title: string, url: string, language: string}|null
     */
    private static function page(int $sectionId): ?array
    {
        global $database;
        $row = $database->fetchRow(
            'SELECT p.`page_id`, p.`page_title`, p.`language` FROM `{TP}sections` s
               JOIN `{TP}pages` p ON p.`page_id` = s.`page_id` WHERE s.`section_id` = ?',
            [$sectionId]
        );
        if ($row === null) {
            return null;
        }
        return [
            'page_id'  => (int) $row['page_id'],
            'title'    => (string) $row['page_title'],
            'url'      => page_link((int) $row['page_id']),
            'language' => (string) $row['language'],
        ];
    }

    /**
     * Mail strings of a language, independent of the language of the
     * current request (an admin may approve in English for a German page).
     *
     * @return callable(string, string|int ...): string
     */
    private static function texts(string $language): callable
    {
        $strings = self::load('EN');
        $language = strtoupper($language);
        if ($language !== 'EN' && preg_match('/^[A-Z]{2}$/', $language) === 1) {
            $strings = self::load($language) + $strings;
        }
        return static fn (string $key, string|int ...$args): string => vsprintf($strings[$key] ?? $key, $args);
    }

    /** @return array<string, string> */
    private static function load(string $language): array
    {
        $file = dirname(__DIR__) . '/languages/' . $language . '.php';
        if (!is_file($file)) {
            return [];
        }
        $MOD_LISTINGS = [];
        include $file;
        return array_filter($MOD_LISTINGS, 'is_string');
    }

    private static function send(string $to, string $subject, string $body, string $replyTo = ''): bool
    {
        $mail = new \Mailer();
        // WBCE waits 30 s per SMTP connection by default
        $mail->Timeout = 10;
        $mail->isHTML(false);
        $mail->addAddress($to);
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false) {
            $mail->addReplyTo($replyTo);
        }
        $mail->Subject = $subject;
        $mail->Body = $body;
        if ($mail->send()) {
            return true;
        }
        // no address in the log: it is personal data
        error_log('Listings: mail "' . $subject . '" not sent: ' . $mail->ErrorInfo);
        return false;
    }
}
