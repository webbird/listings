<?php
/**
 * Listings – log of what happened to the entries of a directory: who
 * (admin, owner, visitor, system) did what and when. It stores no e-mail
 * addresses, no IP addresses and no message texts, only the title of the
 * entry at the time, and forgets entries after KEEP_DAYS.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Log
{
    private const TABLE = '{TP}mod_listings_log';

    public const KEEP_DAYS = 730;

    public const ACTIONS = [
        'created', 'confirmed', 'updated', 'status', 'deleted', 'extended', 'reminded',
        'contact', 'purged', 'imported', 'preset',
    ];

    public const ACTORS = ['admin', 'owner', 'visitor', 'system'];

    /**
     * @param string $details short text, e.g. the title or the new status
     */
    public static function add(int $sectionId, int $itemId, string $action, string $actor, int $userId = 0, string $details = ''): void
    {
        global $database;
        $database->insertRow(self::TABLE, [
            'section_id' => $sectionId,
            'item_id'    => $itemId,
            'action'     => in_array($action, self::ACTIONS, true) ? $action : 'updated',
            'actor'      => in_array($actor, self::ACTORS, true) ? $actor : 'system',
            'user_id'    => $userId,
            'details'    => mb_substr($details, 0, 255),
            'created_at' => time(),
        ]);
        if ($database->hasError()) {
            // the log must never stop the action it records
            trigger_error('Listings log: ' . $database->getError(), E_USER_WARNING);
        }
    }

    /** The logged-in backend user, or 0. */
    public static function adminId(): int
    {
        $admin = $GLOBALS['admin'] ?? null;
        return $admin instanceof \Wbce ? (int) $admin->get_user_id() : 0;
    }

    /**
     * @return list<array<string, mixed>> newest first, with the user's display name
     */
    public static function forSection(int $sectionId, int $limit, int $offset = 0, int $itemId = 0): array
    {
        global $database;
        $where = 'l.`section_id` = ?';
        $params = [$sectionId];
        if ($itemId > 0) {
            $where .= ' AND l.`item_id` = ?';
            $params[] = $itemId;
        }
        $rows = $database->fetchAll(
            'SELECT l.*, u.`display_name` FROM `' . self::TABLE . '` l
               LEFT JOIN `{TP}users` u ON u.`user_id` = l.`user_id` AND l.`user_id` > 0
              WHERE ' . $where . ' ORDER BY l.`created_at` DESC, l.`log_id` DESC LIMIT ? OFFSET ?',
            array_merge($params, [$limit, $offset])
        );
        foreach ($rows as &$row) {
            foreach (['log_id', 'section_id', 'item_id', 'user_id', 'created_at'] as $key) {
                $row[$key] = (int) $row[$key];
            }
            $row['display_name'] = (string) ($row['display_name'] ?? '');
        }
        unset($row);
        return $rows;
    }

    public static function count(int $sectionId): int
    {
        global $database;
        return (int) $database->fetchValue('SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE `section_id` = ?', [$sectionId]);
    }

    /** Forgets what is older than KEEP_DAYS. */
    public static function purge(int $sectionId): void
    {
        global $database;
        $database->query(
            'DELETE FROM `' . self::TABLE . '` WHERE `section_id` = ? AND `created_at` < ?',
            [$sectionId, time() - self::KEEP_DAYS * 86400]
        );
    }
}
