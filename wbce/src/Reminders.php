<?php
/**
 * Listings – reminds owners before their entry expires. WBCE has no cron,
 * so this runs on page views and backend calls, at most once an hour.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Reminders
{
    private const INTERVAL = 3600;

    public static function run(int $sectionId, array $settings): void
    {
        $remindDays = (int) ($settings['remind_days'] ?? 0);
        if (Settings::runtimeDays($settings) === 0 || $remindDays <= 0 || (int) $settings['reminder_run'] > time() - self::INTERVAL) {
            return;
        }
        // stored first: two requests at the same time would otherwise both send
        Settings::save($sectionId, ['reminder_run' => (string) time()]);

        $account = Settings::accountMode($settings);
        foreach (Items::dueForReminder($sectionId, $remindDays) as $item) {
            if ($account && $item['owner_user_id'] > 0) {
                $url = null;   // Mail::reminder links to "my entries"
            } else {
                $token = Tokens::create($sectionId, $item['item_id'], Tokens::EXTEND, $item['owner_email']);
                if ($token === null) {
                    continue;
                }
                $url = $token;
            }
            // marked either way: a failing mail server must not mean a mail per hour
            Items::markReminded($sectionId, $item['item_id']);
            Mail::reminder($sectionId, $item, $url);
        }
    }
}
