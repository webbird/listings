<?php
/**
 * Listings – entries of a directory. Field values live as JSON in `data`;
 * title, status and dates are columns because lists sort and filter by them.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Items
{
    private const TABLE = '{TP}mod_listings_items';

    /** draft = admin draft or not yet confirmed by its owner; expired follows with reminders. */
    public const STATUSES = ['draft', 'pending', 'published', 'rejected'];

    /** Entries added in the frontend and not confirmed within this time are deleted. */
    public const UNCONFIRMED_TTL = 172800;

    public static function count(int $sectionId, ?string $status = null, bool $visibleOnly = false): int
    {
        global $database;
        [$where, $params] = self::where($sectionId, $status, $visibleOnly);
        return (int) $database->fetchValue('SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE ' . $where, $params);
    }

    /**
     * @param string $orderBy one of Settings::SORTS — never request data
     * @return list<array<string, mixed>>
     */
    public static function list(int $sectionId, ?string $status, bool $visibleOnly, string $orderBy, int $limit, int $offset): array
    {
        global $database;
        [$where, $params] = self::where($sectionId, $status, $visibleOnly);
        $rows = $database->fetchAll(
            'SELECT * FROM `' . self::TABLE . '` WHERE ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?',
            array_merge($params, [$limit, $offset])
        );
        return array_map([self::class, 'decode'], $rows);
    }

    public static function find(int $sectionId, int $itemId): ?array
    {
        global $database;
        $row = $database->fetchRow(
            'SELECT * FROM `' . self::TABLE . '` WHERE `item_id` = ? AND `section_id` = ?',
            [$itemId, $sectionId]
        );
        return $row === null ? null : self::decode($row);
    }

    /** A published, not expired entry for the frontend. */
    public static function findVisible(int $sectionId, string $slug): ?array
    {
        global $database;
        [$where, $params] = self::where($sectionId, null, true);
        $row = $database->fetchRow(
            'SELECT * FROM `' . self::TABLE . '` WHERE ' . $where . ' AND `slug` = ?',
            array_merge($params, [$slug])
        );
        return $row === null ? null : self::decode($row);
    }

    /**
     * Checks the submitted entry against the field set.
     *
     * @param list<array<string, mixed>> $fields
     * @param array<string, mixed> $input title, slug, status, expires, fields[]
     * @return array{0: array<string, mixed>, 1: list<string>} [values for save(), errors]
     */
    public static function check(int $sectionId, array $fields, array $input, ?array $existing): array
    {
        $errors = [];
        $str = static fn (string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';

        $title = preg_replace('/\s+/u', ' ', $str('title')) ?? '';
        if ($title === '') {
            $errors[] = L_('MOD_LISTINGS:ERR_TITLE');
        } elseif (mb_strlen($title) > 255) {
            $errors[] = L_('MOD_LISTINGS:ERR_TOO_LONG', L_('MOD_LISTINGS:TITLE'), 255);
        }

        $status = in_array($str('status'), self::STATUSES, true) ? $str('status') : 'draft';

        $expires = 0;
        if ($str('expires') !== '') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $str('expires'));
            if ($date === false || $date->format('Y-m-d') !== $str('expires')) {
                $errors[] = L_('MOD_LISTINGS:ERR_DATE', L_('MOD_LISTINGS:EXPIRES'));
            } else {
                // valid through the whole day
                $expires = $date->setTime(23, 59, 59)->getTimestamp();
            }
        }

        // values of fields that were deleted stay, they are just not shown
        $data = $existing['data'] ?? [];
        $submitted = is_array($input['fields'] ?? null) ? $input['fields'] : [];
        foreach ($fields as $field) {
            if (!FieldTypes::stores($field['type'])) {
                continue;
            }
            [$value, $error] = FieldTypes::validate($field, $submitted[$field['name']] ?? null);
            if ($error !== null) {
                $errors[] = $error;
            }
            if ($value === null) {
                unset($data[$field['name']]);
            } else {
                $data[$field['name']] = $value;
            }
        }

        $slugInput = $str('slug');
        $slug = self::uniqueSlug($sectionId, $slugInput !== '' ? $slugInput : $title, $existing === null ? 0 : (int) $existing['item_id']);

        $values = [
            'title'      => $title,
            'slug'       => $slug,
            'status'     => $status,
            'expires_at' => $expires,
            'data'       => $data,
        ];
        if (array_key_exists('owner_email', $input)) {
            $email = strtolower($str('owner_email'));
            if ($email !== '' && (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 255)) {
                $errors[] = L_('MOD_LISTINGS:ERR_EMAIL', L_('MOD_LISTINGS:OWNER_EMAIL'));
            }
            $values['owner_email'] = $email;
        }
        return [$values, $errors];
    }

    /**
     * Inserts ($itemId null) or updates; returns the ID, or null on a database error.
     *
     * @param array<string, mixed> $values from check()
     */
    public static function save(int $sectionId, int $pageId, ?array $existing, array $values, int $userId): ?int
    {
        global $database;
        $now = time();
        $json = json_encode($values['data'], JSON_UNESCAPED_UNICODE);
        $publishedAt = (int) ($existing['published_at'] ?? 0);
        if ($values['status'] === 'published' && $publishedAt === 0) {
            $publishedAt = $now;
        }
        $ownerEmail = array_key_exists('owner_email', $values) ? (string) $values['owner_email'] : (string) ($existing['owner_email'] ?? '');

        if ($existing === null) {
            $database->insertRow(self::TABLE, [
                'section_id'   => $sectionId,
                'page_id'      => $pageId,
                'title'        => $values['title'],
                'slug'         => $values['slug'],
                'status'       => $values['status'],
                'owner_email'  => $ownerEmail,
                'data'         => $json,
                'created_at'   => $now,
                'updated_at'   => $now,
                'published_at' => $publishedAt,
                'expires_at'   => $values['expires_at'],
                'modified_by'  => $userId,
            ]);
            return $database->hasError() ? null : (int) $database->lastInsertId();
        }

        $database->query(
            'UPDATE `' . self::TABLE . '` SET `title` = ?, `slug` = ?, `status` = ?, `owner_email` = ?, `data` = ?, `updated_at` = ?,
                    `published_at` = ?, `expires_at` = ?, `modified_by` = ?
              WHERE `item_id` = ? AND `section_id` = ?',
            [
                $values['title'], $values['slug'], $values['status'], $ownerEmail, $json, $now,
                $publishedAt, $values['expires_at'], $userId, (int) $existing['item_id'], $sectionId,
            ]
        );
        return $database->hasError() ? null : (int) $existing['item_id'];
    }

    /** Changes only the status; the first publication sets published_at. */
    public static function setStatus(int $sectionId, int $itemId, string $status): bool
    {
        global $database;
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $now = time();
        $database->query(
            'UPDATE `' . self::TABLE . '`
                SET `status` = ?, `updated_at` = ?,
                    `published_at` = CASE WHEN ? = 1 AND `published_at` = 0 THEN ? ELSE `published_at` END
              WHERE `item_id` = ? AND `section_id` = ?',
            [$status, $now, $status === 'published' ? 1 : 0, $now, $itemId, $sectionId]
        );
        return !$database->hasError();
    }

    /**
     * Confirmed entries of an owner address in the section — never
     * unconfirmed ones, whose texts a stranger may have typed.
     *
     * @return list<array<string, mixed>>
     */
    public static function ownedBy(int $sectionId, string $email): array
    {
        global $database;
        $rows = $database->fetchAll(
            'SELECT * FROM `' . self::TABLE . '`
              WHERE `section_id` = ? AND `owner_email` = ? AND `status` <> ?
              ORDER BY `created_at` DESC LIMIT 20',
            [$sectionId, strtolower($email), 'draft']
        );
        return array_map([self::class, 'decode'], $rows);
    }

    /** Deletes frontend entries never confirmed; admin drafts have no owner address. */
    public static function purgeUnconfirmed(int $sectionId): void
    {
        global $database;
        $ids = array_column($database->fetchAll(
            'SELECT `item_id` FROM `' . self::TABLE . '`
              WHERE `section_id` = ? AND `status` = ? AND `owner_email` <> ? AND `owner_user_id` = 0 AND `published_at` = 0 AND `created_at` < ?',
            [$sectionId, 'draft', '', time() - self::UNCONFIRMED_TTL]
        ), 'item_id');
        foreach ($ids as $id) {
            self::delete($sectionId, (int) $id);
        }
    }

    public static function delete(int $sectionId, int $itemId): bool
    {
        global $database;
        $database->query(
            'DELETE FROM `' . self::TABLE . '` WHERE `item_id` = ? AND `section_id` = ?',
            [$itemId, $sectionId]
        );
        if ($database->hasError()) {
            return false;
        }
        Tokens::deleteForItem($itemId);
        return true;
    }

    /** URL-safe, unique within the section; "-2", "-3" … on a clash. */
    public static function uniqueSlug(int $sectionId, string $source, int $exceptId): string
    {
        global $database;
        $base = strtolower(page_filename($source));
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', $base), '-');
        $base = substr($base !== '' ? $base : 'entry', 0, 180);

        $slug = $base;
        for ($n = 2; ; $n++) {
            $taken = $database->fetchValue(
                'SELECT `item_id` FROM `' . self::TABLE . '` WHERE `section_id` = ? AND `slug` = ? AND `item_id` <> ?',
                [$sectionId, $slug, $exceptId]
            );
            if ($taken === '') {
                return $slug;
            }
            $slug = $base . '-' . $n;
        }
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private static function where(int $sectionId, ?string $status, bool $visibleOnly): array
    {
        $where = '`section_id` = ?';
        $params = [$sectionId];
        if ($visibleOnly) {
            $status = 'published';
            $where .= ' AND (`expires_at` = 0 OR `expires_at` > ?)';
            $params[] = time();
        }
        if ($status !== null) {
            $where .= ' AND `status` = ?';
            $params[] = $status;
        }
        return [$where, $params];
    }

    private static function decode(array $row): array
    {
        $data = json_decode((string) ($row['data'] ?? ''), true);
        $row['data'] = is_array($data) ? $data : [];
        foreach (['item_id', 'section_id', 'page_id', 'created_at', 'updated_at', 'published_at', 'expires_at'] as $key) {
            $row[$key] = (int) $row[$key];
        }
        return $row;
    }
}
