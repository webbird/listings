<?php
/**
 * Listings – links mailed to owners: confirm a new entry, edit an entry.
 * The token itself only exists in the mail; the table holds its SHA-256 hash.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Tokens
{
    private const TABLE = '{TP}mod_listings_tokens';

    public const CONFIRM = 'confirm';
    public const EDIT = 'edit';
    public const EXTEND = 'extend';

    /** Lifetime in seconds; the reminder link lasts beyond the expiry, so a late reader can still extend. */
    public const TTL = [self::CONFIRM => 86400, self::EDIT => 3600, self::EXTEND => 30 * 86400];

    /** Mails per address and section within an hour; protects strangers' inboxes. */
    public const MAX_PER_HOUR = 5;

    /** A new token; returns it, or null on a database error. */
    public static function create(int $sectionId, int $itemId, string $purpose, string $email): ?string
    {
        global $database;
        $token = bin2hex(random_bytes(32));
        $now = time();
        $database->insertRow(self::TABLE, [
            'section_id' => $sectionId,
            'item_id'    => $itemId,
            'purpose'    => $purpose,
            'token_hash' => hash('sha256', $token),
            'email'      => strtolower($email),
            'created_at' => $now,
            'expires_at' => $now + (self::TTL[$purpose] ?? 3600),
        ]);
        return $database->hasError() ? null : $token;
    }

    /** The unused, unexpired token row, or null. */
    public static function find(string $token, string $purpose): ?array
    {
        global $database;
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return null;
        }
        $row = $database->fetchRow(
            'SELECT * FROM `' . self::TABLE . '`
              WHERE `token_hash` = ? AND `purpose` = ? AND `used_at` = 0 AND `expires_at` > ?',
            [hash('sha256', $token), $purpose, time()]
        );
        if ($row === null) {
            return null;
        }
        foreach (['token_id', 'section_id', 'item_id', 'expires_at'] as $key) {
            $row[$key] = (int) $row[$key];
        }
        return $row;
    }

    public static function markUsed(int $tokenId): bool
    {
        global $database;
        $database->query('UPDATE `' . self::TABLE . '` SET `used_at` = ? WHERE `token_id` = ?', [time(), $tokenId]);
        return !$database->hasError();
    }

    /** How many mails went to this address for this section in the last hour. */
    public static function recentCount(int $sectionId, string $email): int
    {
        global $database;
        return (int) $database->fetchValue(
            'SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE `section_id` = ? AND `email` = ? AND `created_at` > ?',
            [$sectionId, strtolower($email), time() - 3600]
        );
    }

    public static function deleteForItem(int $itemId): void
    {
        global $database;
        $database->deleteRow(self::TABLE, 'item_id', $itemId);
    }

    /** Expired tokens are kept a day for the hourly limit, then removed. */
    public static function purge(): void
    {
        global $database;
        $database->query('DELETE FROM `' . self::TABLE . '` WHERE `expires_at` < ?', [time() - 86400]);
    }
}
