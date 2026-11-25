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
    ];

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

    public static function orderBy(array $settings): string
    {
        return self::SORTS[$settings['sort'] ?? ''] ?? self::SORTS['newest'];
    }

    public static function perPage(array $settings): int
    {
        return max(1, min(self::PER_PAGE_MAX, (int) ($settings['per_page'] ?? 20)));
    }
}
