<?php
/**
 * Listings – settings of a directory, stored as name/value rows.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Settings
{
    private const TABLE = '{TP}mod_listings_settings';

    /** Missing rows fall back to these; an empty layout means the file in layouts/. */
    public const DEFAULTS = [
        'per_page'      => '20',
        'sort'          => 'newest',
        'layout_list'   => '',
        'layout_detail' => '',
        // frontend entries (0.2)
        'frontend'      => '0',   // visitors may add entries
        'approval'      => '1',   // a confirmed entry waits for the admin
        'recheck'       => '1',   // a changed published entry waits again
        'captcha'       => '1',
        'notify_email'  => '',    // new waiting entries are reported here
        // 0.3
        'owner_mode'    => 'email',  // email: links by mail; account: WBCE frontend account
        'contact'       => '0',      // contact form to the owner on the detail page
        'runtime_days'  => '0',      // entries of owners expire after this; 0 = never
        'remind_days'   => '7',      // the owner is reminded this many days before
        'reminder_run'  => '0',      // last run of the reminders (no cron in WBCE)
        // 0.4
        'tags_mode'     => 'off',    // off | fixed (the admin's list) | open (owners add tags)
        'filter_form'   => '1',      // search and filter above the list
        'rss'           => '0',      // feed of the latest entries
        // 1.1
        'max_per_owner' => '0',      // confirmed entries per owner address or account; 0 = any number
        'mails'         => '',       // own mail templates as JSON (Mail::checkTemplates()); '' = the defaults
        'form_intro'    => '',       // own text above the form for new entries; '' = the default
    ];

    public const FORM_INTRO_MAX = 2000;

    public const MAX_PER_OWNER_LIMIT = 1000;

    public static function maxPerOwner(array $settings): int
    {
        return max(0, min(self::MAX_PER_OWNER_LIMIT, (int) ($settings['max_per_owner'] ?? 0)));
    }

    public const OWNER_MODES = ['email', 'account'];

    public const RUNTIME_MAX = 3650;

    public static function accountMode(array $settings): bool
    {
        return ($settings['owner_mode'] ?? 'email') === 'account';
    }

    public static function runtimeDays(array $settings): int
    {
        return max(0, min(self::RUNTIME_MAX, (int) ($settings['runtime_days'] ?? 0)));
    }

    public static function on(array $settings, string $name): bool
    {
        return ($settings[$name] ?? '0') === '1';
    }

    /** Sort keys and their ORDER BY; the key is all that leaves the server. */
    public const SORTS = [
        'newest'    => '`created_at` DESC, `item_id` DESC',
        'published' => '`published_at` DESC, `item_id` DESC',
        'updated'   => '`updated_at` DESC, `item_id` DESC',
        'title'     => '`title` ASC, `item_id` ASC',
    ];

    public const PER_PAGE_MAX = 200;

    /**
     * @return array<string, string>
     */
    public static function load(int $sectionId): array
    {
        global $database;
        $settings = self::DEFAULTS;
        $rows = $database->fetchAll(
            'SELECT `name`, `value` FROM `' . self::TABLE . '` WHERE `section_id` = ?',
            [$sectionId]
        );
        foreach ($rows as $row) {
            if (array_key_exists($row['name'], $settings)) {
                $settings[$row['name']] = (string) $row['value'];
            }
        }
        return $settings;
    }

    /**
     * Saves known names only; returns the database error or null.
     *
     * @param array<string, string> $values
     */
    public static function save(int $sectionId, array $values): ?string
    {
        global $database;
        foreach (array_intersect_key($values, self::DEFAULTS) as $name => $value) {
            $database->upsertRow(self::TABLE, 'section_id,name', [
                'section_id' => $sectionId,
                'name'       => $name,
                'value'      => (string) $value,
            ]);
            if ($database->hasError()) {
                return $database->getError();
            }
        }
        return null;
    }

    /** Switches stored as '1' or '0'. */
    public const FLAGS = ['frontend', 'approval', 'recheck', 'captcha', 'contact', 'filter_form', 'rss'];

    /**
     * Checks submitted settings (the form, or a preset on top of the current values).
     *
     * @param array<string, mixed> $input
     * @return array{0: array<string, string>, 1: list<string>} [values for save(), errors]
     */
    public static function check(array $input): array
    {
        $str = static fn (string $key): string => is_scalar($input[$key] ?? null) ? trim((string) $input[$key]) : '';
        $errors = [];
        $perPage = $str('per_page');
        if (!ctype_digit($perPage) || (int) $perPage < 1 || (int) $perPage > self::PER_PAGE_MAX) {
            $errors[] = L_('MOD_LISTINGS:ERR_PER_PAGE', self::PER_PAGE_MAX);
        }
        $notify = strtolower($str('notify_email'));
        if ($notify !== '' && filter_var($notify, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = L_('MOD_LISTINGS:ERR_EMAIL', L_('MOD_LISTINGS:NOTIFY_EMAIL'));
        }
        $runtime = $str('runtime_days');
        $remind = $str('remind_days');
        if (!ctype_digit($runtime) || (int) $runtime > self::RUNTIME_MAX) {
            $errors[] = L_('MOD_LISTINGS:ERR_RUNTIME', self::RUNTIME_MAX);
        }
        if (!ctype_digit($remind) || (int) $remind > 365) {
            $errors[] = L_('MOD_LISTINGS:ERR_REMIND');
        }
        $maxOwner = $str('max_per_owner') === '' ? '0' : $str('max_per_owner');
        if (!ctype_digit($maxOwner) || (int) $maxOwner > self::MAX_PER_OWNER_LIMIT) {
            $errors[] = L_('MOD_LISTINGS:ERR_MAX_PER_OWNER', self::MAX_PER_OWNER_LIMIT);
        }
        $values = [
            'per_page'     => ctype_digit($perPage) ? (string) (int) $perPage : $perPage,
            'sort'         => array_key_exists($str('sort'), self::SORTS) ? $str('sort') : 'newest',
            'notify_email' => $notify,
            'owner_mode'   => in_array($str('owner_mode'), self::OWNER_MODES, true) ? $str('owner_mode') : 'email',
            'runtime_days' => ctype_digit($runtime) ? (string) (int) $runtime : $runtime,
            'remind_days'  => ctype_digit($remind) ? (string) (int) $remind : $remind,
            'max_per_owner' => ctype_digit($maxOwner) ? (string) (int) $maxOwner : $maxOwner,
            'tags_mode'    => in_array($str('tags_mode'), Taxonomy::TAG_MODES, true) ? $str('tags_mode') : 'off',
        ];
        foreach (self::FLAGS as $flag) {
            $values[$flag] = in_array($str($flag), ['1', 'true'], true) ? '1' : '0';
        }
        return [$values, $errors];
    }

    public static function orderBy(array $settings): string
    {
        return self::SORTS[$settings['sort'] ?? ''] ?? self::SORTS['newest'];
    }

    public static function perPage(array $settings): int
    {
        return max(1, min(self::PER_PAGE_MAX, (int) ($settings['per_page'] ?? 20)));
    }
}
