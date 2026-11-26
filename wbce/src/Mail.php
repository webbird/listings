<?php
/**
 * Listings – mails to owners and to the admin, plain text, in the language
 * of the page. Each kind of mail is a template with named placeholders
 * ({TITLE}, {LINK}, …); the defaults come from the language files, a
 * directory may replace them (setting "mails"). A confirmation mail goes to
 * an address anybody can type, so it offers nothing the visitor typed: no
 * title, no field values.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Mail
{
    /**
     * The kinds of mail: their placeholders (the first ones must stay in
     * the text: without them the mail is useless) and how the default is
     * built from the language strings (key => placeholders for its %s).
     */
    public const KINDS = [
        'confirm'       => ['required' => ['{LINK}'], 'placeholders' => ['{LINK}', '{HOURS}', '{PAGE}', '{SITE}']],
        'edit'          => ['required' => ['{LINKS}'], 'placeholders' => ['{LINKS}', '{MINUTES}', '{PAGE}', '{SITE}']],
        'published'     => ['required' => [], 'placeholders' => ['{TITLE}', '{LINK}', '{PAGE}', '{SITE}']],
        'rejected'      => ['required' => [], 'placeholders' => ['{TITLE}', '{PAGE}', '{SITE}']],
        'reminder'      => ['required' => ['{LINK}'], 'placeholders' => ['{LINK}', '{TITLE}', '{DATE}', '{PAGE}', '{SITE}']],
        'contact'       => ['required' => ['{MESSAGE}'], 'placeholders' => ['{MESSAGE}', '{NAME}', '{EMAIL}', '{TITLE}', '{PAGE}', '{SITE}']],
        'admin_waiting' => ['required' => [], 'placeholders' => ['{TITLE}', '{OWNER_EMAIL}', '{LINK}', '{PAGE}', '{SITE}']],
        'admin_new'     => ['required' => [], 'placeholders' => ['{TITLE}', '{OWNER_EMAIL}', '{LINK}', '{PAGE}', '{SITE}']],
    ];

    public const SUBJECT_MAX = 200;
    public const TEXT_MAX = 5000;

    /** Link to confirm a new entry. */
    public static function confirm(int $sectionId, string $email, string $token): bool
    {
        $page = self::page($sectionId);
        if ($page === null) {
            return false;
        }
        return self::sendKind($sectionId, $page, 'confirm', $email, [
            '{LINK}'  => View::tokenUrl($page['url'], View::PARAM_CONFIRM, $token),
            '{HOURS}' => (string) (int) (Tokens::TTL[Tokens::CONFIRM] / 3600),
        ]);
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
        $list = [];
        foreach ($links as $link) {
            $list[] = $link['title'] . "\n" . View::tokenUrl($page['url'], View::PARAM_EDIT, $link['token']);
        }
        return self::sendKind($sectionId, $page, 'edit', $email, [
            '{LINKS}'   => implode("\n\n", $list),
            '{MINUTES}' => (string) (int) (Tokens::TTL[Tokens::EDIT] / 60),
        ]);
    }

    /** To the owner: the entry was published or rejected. */
    public static function status(int $sectionId, array $item, string $status): bool
    {
        $page = self::page($sectionId);
        if ($page === null || $item['owner_email'] === '' || !in_array($status, ['published', 'rejected'], true)) {
            return false;
        }
        return self::sendKind($sectionId, $page, $status, $item['owner_email'], [
            '{TITLE}' => $item['title'],
            '{LINK}'  => View::itemUrl($page['url'], $item['slug']),
        ]);
    }

    /**
     * To the owner: the entry expires soon. $token is an extend link; null
     * (owner with an account) links to the owner's list instead.
     */
    public static function reminder(int $sectionId, array $item, ?string $token): bool
    {
        $page = self::page($sectionId);
        if ($page === null || $item['owner_email'] === '') {
            return false;
        }
        return self::sendKind($sectionId, $page, 'reminder', $item['owner_email'], [
            '{TITLE}' => $item['title'],
            '{DATE}'  => date('d.m.Y', $item['expires_at']),
            '{LINK}'  => $token === null
                ? View::formUrl($page['url'], $sectionId, 'mine')
                : View::tokenUrl($page['url'], View::PARAM_EXTEND, $token),
        ]);
    }

    /**
     * A visitor writes to the owner. The owner's address stays hidden; the
     * answer goes to the visitor through Reply-To.
     */
    public static function contact(int $sectionId, array $item, string $name, string $email, string $message): bool
    {
        $page = self::page($sectionId);
        if ($page === null || $item['owner_email'] === '') {
            return false;
        }
        return self::sendKind($sectionId, $page, 'contact', $item['owner_email'], [
            '{TITLE}'   => $item['title'],
            '{NAME}'    => $name,
            '{EMAIL}'   => $email,
            '{MESSAGE}' => $message,
        ], $email);
    }

    /** To notify_email: a confirmed entry is waiting, or (without approval) went online. */
    public static function notifyAdmin(int $sectionId, array $item, bool $waiting): bool
    {
        $to = Settings::load($sectionId)['notify_email'];
        $page = self::page($sectionId);
        if ($to === '' || $page === null) {
            return false;
        }
        return self::sendKind($sectionId, $page, $waiting ? 'admin_waiting' : 'admin_new', $to, [
            '{TITLE}'       => $item['title'],
            '{OWNER_EMAIL}' => $item['owner_email'],
            '{LINK}'        => Module::modifyUrl($page['page_id'], $sectionId, ['lst_view' => 'item', 'lst_item' => $item['item_id']]),
        ], $item['owner_email']);
    }

    /**
     * The template of a kind for a directory: its own text, or the default
     * in $language.
     *
     * @return array{subject: string, text: string, custom: bool}
     */
    public static function template(array $settings, string $kind, string $language): array
    {
        $custom = self::customTemplates($settings)[$kind] ?? null;
        if ($custom !== null) {
            return $custom + ['custom' => true];
        }
        return self::defaultTemplate($kind, $language) + ['custom' => false];
    }

    /**
     * The default template from the language strings, with the placeholders in place of their %s.
     *
     * @return array{subject: string, text: string}
     */
    public static function defaultTemplate(string $kind, string $language): array
    {
        $t = self::texts($language);
        $footer = $t('MAIL_FOOTER', '{SITE}');
        $ignore = $t('MAIL_IGNORE');
        return match ($kind) {
            'confirm' => [
                'subject' => $t('MAIL_CONFIRM_SUBJECT', '{SITE}'),
                'text'    => $t('MAIL_CONFIRM_TEXT', '{PAGE}', '{SITE}', '{LINK}', '{HOURS}') . "\n\n" . $ignore . "\n\n" . $footer,
            ],
            'edit' => [
                'subject' => $t('MAIL_EDIT_SUBJECT', '{SITE}'),
                'text'    => $t('MAIL_EDIT_TEXT', '{PAGE}', '{MINUTES}') . "\n\n{LINKS}\n\n" . $ignore . "\n\n" . $footer,
            ],
            'published' => [
                'subject' => $t('MAIL_PUBLISHED_SUBJECT', '{TITLE}'),
                'text'    => $t('MAIL_PUBLISHED_TEXT', '{TITLE}', '{PAGE}') . "\n\n{LINK}\n\n" . $footer,
            ],
            'rejected' => [
                'subject' => $t('MAIL_REJECTED_SUBJECT', '{TITLE}'),
                'text'    => $t('MAIL_REJECTED_TEXT', '{TITLE}', '{PAGE}') . "\n\n" . $footer,
            ],
            'reminder' => [
                'subject' => $t('MAIL_REMINDER_SUBJECT', '{TITLE}', '{DATE}'),
                'text'    => $t('MAIL_REMINDER_TEXT', '{TITLE}', '{PAGE}', '{DATE}') . "\n\n{LINK}\n\n" . $footer,
            ],
            'contact' => [
                'subject' => $t('MAIL_CONTACT_SUBJECT', '{TITLE}'),
                'text'    => $t('MAIL_CONTACT_TEXT', '{TITLE}', '{PAGE}', '{SITE}') . "\n\n"
                    . $t('CONTACT_NAME') . ": {NAME}\n" . $t('CONTACT_EMAIL') . ": {EMAIL}\n\n{MESSAGE}\n\n" . $footer,
            ],
            'admin_waiting', 'admin_new' => [
                'subject' => $t('MAIL_' . strtoupper($kind) . '_SUBJECT', '{TITLE}'),
                'text'    => $t('MAIL_' . strtoupper($kind) . '_TEXT', '{PAGE}') . "\n\n{TITLE}\n" . $t('OWNER_EMAIL') . ": {OWNER_EMAIL}\n\n{LINK}",
            ],
            default => ['subject' => '', 'text' => ''],
        };
    }

    /**
     * Checks templates from the layouts form; one equal to the default is
     * not stored, so later changes of the default reach the directory.
     *
     * @param array<string, mixed> $input mail_<kind>_subject, mail_<kind>_text, mail_<kind>_reset
     * @return array{0: string, 1: list<string>, 2: array<string, array{subject: string, text: string}>} [JSON for the setting, errors, the sent values]
     */
    public static function checkTemplates(array $input, string $language): array
    {
        $str = static fn (string $key): string => is_string($input[$key] ?? null) ? str_replace(["\r\n", "\r"], "\n", $input[$key]) : '';
        $stored = [];
        $sent = [];
        $errors = [];
        foreach (array_keys(self::KINDS) as $kind) {
            if ($str('mail_' . $kind . '_reset') === '1') {
                continue;
            }
            $subject = trim((string) preg_replace('/\s+/u', ' ', $str('mail_' . $kind . '_subject')));
            $text = trim($str('mail_' . $kind . '_text'));
            $sent[$kind] = ['subject' => $subject, 'text' => $text];
            $default = self::defaultTemplate($kind, $language);
            if (($subject === trim($default['subject']) && $text === trim($default['text'])) || ($subject === '' && $text === '')) {
                continue;
            }
            $label = L_('MOD_LISTINGS:MAIL_KIND_' . strtoupper($kind));
            if ($subject === '' || $text === '') {
                $errors[] = L_('MOD_LISTINGS:ERR_MAIL_EMPTY', $label);
            } elseif (mb_strlen($subject) > self::SUBJECT_MAX || mb_strlen($text) > self::TEXT_MAX) {
                $errors[] = L_('MOD_LISTINGS:ERR_MAIL_TOO_LONG', $label, self::SUBJECT_MAX, self::TEXT_MAX);
            }
            foreach (self::KINDS[$kind]['required'] as $placeholder) {
                if (!str_contains($text, $placeholder)) {
                    $errors[] = L_('MOD_LISTINGS:ERR_MAIL_PLACEHOLDER', $label, $placeholder);
                }
            }
            $stored[$kind] = ['subject' => $subject, 'text' => $text];
        }
        return [$stored === [] ? '' : (string) json_encode($stored, JSON_UNESCAPED_UNICODE), $errors, $sent];
    }

    /**
     * A filled template; only the placeholders of the kind are replaced.
     *
     * @param array<string, string> $values
     * @return array{subject: string, text: string}
     */
    public static function fill(array $template, string $kind, array $values): array
    {
        $allowed = array_intersect_key($values, array_flip(self::KINDS[$kind]['placeholders'] ?? []));
        // values never bring line breaks into the subject
        $subjectValues = array_map(static fn ($v) => trim((string) preg_replace('/\s+/u', ' ', $v)), $allowed);
        return [
            'subject' => strtr($template['subject'], $subjectValues),
            'text'    => strtr($template['text'], $allowed),
        ];
    }

    /**
     * Sample values of a kind for the preview in the backend.
     *
     * @return array<string, string>
     */
    public static function sample(int $sectionId, string $kind): array
    {
        $page = self::page($sectionId);
        $url = $page['url'] ?? WB_URL;
        $t = self::texts($page['language'] ?? DEFAULT_LANGUAGE);
        $values = [
            '{PAGE}'        => $page['title'] ?? '',
            '{SITE}'        => WEBSITE_TITLE,
            '{TITLE}'       => $t('MAIL_SAMPLE_TITLE'),
            '{LINK}'        => $url . (str_contains($url, '?') ? '&' : '?') . 'lst_…=' . str_repeat('x', 12),
            '{LINKS}'       => $t('MAIL_SAMPLE_TITLE') . "\n" . $url . '?lst_edit=' . str_repeat('x', 12),
            '{HOURS}'       => (string) (int) (Tokens::TTL[Tokens::CONFIRM] / 3600),
            '{MINUTES}'     => (string) (int) (Tokens::TTL[Tokens::EDIT] / 60),
            '{DATE}'        => date('d.m.Y', time() + 7 * 86400),
            '{NAME}'        => 'Alex Muster',
            '{EMAIL}'       => 'alex@example.com',
            '{OWNER_EMAIL}' => 'owner@example.com',
            '{MESSAGE}'     => $t('MAIL_SAMPLE_MESSAGE'),
        ];
        return array_intersect_key($values, array_flip(self::KINDS[$kind]['placeholders'] ?? []));
    }

    /** The language mails of the directory are written in: the page's. */
    public static function language(int $sectionId): string
    {
        return self::page($sectionId)['language'] ?? DEFAULT_LANGUAGE;
    }

    /** @return array<string, array{subject: string, text: string}> */
    private static function customTemplates(array $settings): array
    {
        $data = json_decode((string) ($settings['mails'] ?? ''), true);
        $result = [];
        foreach (is_array($data) ? $data : [] as $kind => $template) {
            if (isset(self::KINDS[$kind]) && is_string($template['subject'] ?? null) && is_string($template['text'] ?? null)) {
                $result[$kind] = ['subject' => $template['subject'], 'text' => $template['text']];
            }
        }
        return $result;
    }

    /** @param array<string, string> $values */
    private static function sendKind(int $sectionId, array $page, string $kind, string $to, array $values, string $replyTo = ''): bool
    {
        $values += ['{PAGE}' => $page['title'], '{SITE}' => WEBSITE_TITLE];
        $mail = self::fill(self::template(Settings::load($sectionId), $kind, $page['language']), $kind, $values);
        return self::send($to, $mail['subject'], $mail['text'], $replyTo);
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
        return static fn (string $key, string|int ...$args): string => $args === [] ? ($strings[$key] ?? $key) : vsprintf($strings[$key] ?? $key, $args);
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
