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

    /**
     * @param array<string, mixed> $filter see where()
     */
    public static function count(int $sectionId, ?string $status = null, bool $visibleOnly = false, array $filter = []): int
    {
        global $database;
        [$where, $params] = self::where($sectionId, $status, $visibleOnly, $filter);
        return (int) $database->fetchValue('SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE ' . $where, $params);
    }

    /**
     * @param string $orderBy one of Settings::SORTS — never request data
     * @param array<string, mixed> $filter see where()
     * @return list<array<string, mixed>>
     */
    public static function list(int $sectionId, ?string $status, bool $visibleOnly, string $orderBy, int $limit, int $offset, array $filter = []): array
    {
        global $database;
        [$where, $params] = self::where($sectionId, $status, $visibleOnly, $filter);
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
            // images and files are checked by Files::check() and live in their own table
            if (!FieldTypes::stores($field['type']) || Files::isUpload($field['type'])) {
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
        $ownerEmail = array_key_exists('owner_email', $values) ? (string) $values['owner_email'] : (string) ($existing['owner_email'] ?? '');
        $ownerUserId = (int) ($values['owner_user_id'] ?? $existing['owner_user_id'] ?? 0);
        if ($values['status'] === 'published' && $publishedAt === 0) {
            $publishedAt = $now;
            if ((int) $values['expires_at'] === 0) {
                $values['expires_at'] = self::firstExpiry($sectionId, $ownerEmail, $ownerUserId, $now);
            }
        }

        if ($existing === null) {
            $database->insertRow(self::TABLE, [
                'section_id'   => $sectionId,
                'page_id'      => $pageId,
                'title'        => $values['title'],
                'slug'         => $values['slug'],
                'status'       => $values['status'],
                'owner_email'  => $ownerEmail,
                'owner_user_id' => $ownerUserId,
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

    /** Changes only the status; the first publication sets published_at and starts the runtime. */
    public static function setStatus(int $sectionId, int $itemId, string $status): bool
    {
        global $database;
        $item = self::find($sectionId, $itemId);
        if ($item === null || !in_array($status, self::STATUSES, true)) {
            return false;
        }
        $now = time();
        $publishedAt = $item['published_at'];
        $expiresAt = $item['expires_at'];
        if ($status === 'published' && $publishedAt === 0) {
            $publishedAt = $now;
            if ($expiresAt === 0) {
                $expiresAt = self::firstExpiry($sectionId, $item['owner_email'], $item['owner_user_id'], $now);
            }
        }
        $database->query(
            'UPDATE `' . self::TABLE . '` SET `status` = ?, `updated_at` = ?, `published_at` = ?, `expires_at` = ?
              WHERE `item_id` = ? AND `section_id` = ?',
            [$status, $now, $publishedAt, $expiresAt, $itemId, $sectionId]
        );
        return !$database->hasError();
    }

    /**
     * Entries of owners run for the configured days from their first
     * publication; the admin's own entries and a runtime of 0 never expire.
     */
    private static function firstExpiry(int $sectionId, string $ownerEmail, int $ownerUserId, int $now): int
    {
        $days = Settings::runtimeDays(Settings::load($sectionId));
        return $days > 0 && ($ownerEmail !== '' || $ownerUserId > 0) ? $now + $days * 86400 : 0;
    }

    /**
     * Whether the owner may extend now: a runtime is set and the entry is
     * expired or within the reminder period.
     */
    public static function extendable(array $item, array $settings): bool
    {
        $days = Settings::runtimeDays($settings);
        if ($days === 0 || $item['expires_at'] === 0 || $item['status'] !== 'published') {
            return false;
        }
        $window = max(0, (int) ($settings['remind_days'] ?? 7)) * 86400;
        return $item['expires_at'] - $window <= time();
    }

    /** One more runtime, counted from today or from the expiry, whichever is later. */
    public static function extend(int $sectionId, array $item, array $settings): bool
    {
        global $database;
        $from = max(time(), $item['expires_at']);
        $database->query(
            'UPDATE `' . self::TABLE . '` SET `expires_at` = ?, `reminded_at` = 0, `updated_at` = ? WHERE `item_id` = ? AND `section_id` = ?',
            [$from + Settings::runtimeDays($settings) * 86400, time(), $item['item_id'], $sectionId]
        );
        return !$database->hasError();
    }

    /**
     * Published entries of owners that expire within $days and whose owner
     * was not reminded yet.
     *
     * @return list<array<string, mixed>>
     */
    public static function dueForReminder(int $sectionId, int $days): array
    {
        global $database;
        $now = time();
        $rows = $database->fetchAll(
            'SELECT * FROM `' . self::TABLE . '`
              WHERE `section_id` = ? AND `status` = ? AND `owner_email` <> ? AND `reminded_at` = 0
                AND `expires_at` > ? AND `expires_at` <= ?
              ORDER BY `expires_at` LIMIT 50',
            [$sectionId, 'published', '', $now, $now + $days * 86400]
        );
        return array_map([self::class, 'decode'], $rows);
    }

    public static function markReminded(int $sectionId, int $itemId): void
    {
        global $database;
        $database->query(
            'UPDATE `' . self::TABLE . '` SET `reminded_at` = ? WHERE `item_id` = ? AND `section_id` = ?',
            [time(), $itemId, $sectionId]
        );
    }

    /**
     * The entries of a frontend account in the section, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public static function ownedByUser(int $sectionId, int $userId): array
    {
        global $database;
        if ($userId <= 0) {
            return [];
        }
        $rows = $database->fetchAll(
            'SELECT * FROM `' . self::TABLE . '` WHERE `section_id` = ? AND `owner_user_id` = ? ORDER BY `created_at` DESC LIMIT 100',
            [$sectionId, $userId]
        );
        return array_map([self::class, 'decode'], $rows);
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

    /**
     * Confirmed entries (not drafts) of an account ($userId) or an owner
     * address, without $exceptId.
     */
    public static function countOwned(int $sectionId, string $email, int $userId, int $exceptId = 0): int
    {
        global $database;
        if ($userId > 0) {
            $where = '`owner_user_id` = ?';
            $who = $userId;
        } elseif ($email !== '') {
            $where = '`owner_email` = ?';
            $who = strtolower($email);
        } else {
            return 0;
        }
        return (int) $database->fetchValue(
            'SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE `section_id` = ? AND ' . $where . ' AND `status` <> ? AND `item_id` <> ?',
            [$sectionId, $who, 'draft', $exceptId]
        );
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
            Log::add($sectionId, (int) $id, 'purged', 'system');
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
        Files::deleteForItem($sectionId, $itemId);
        Taxonomy::deleteForItem($sectionId, $itemId);
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
     * The filter is built by View from checked request data:
     *   cats   list<int>  in one of these categories
     *   tag    int        has this tag
     *   q      string     words in the title or in one of q_fields (public text fields)
     *   fields list<array{name: string, multi: bool, value: string}>  value of a choice field,
     *          the value taken from the field's choices, never from the request directly
     *
     * @param array<string, mixed> $filter
     * @return array{0: string, 1: list<mixed>}
     */
    private static function where(int $sectionId, ?string $status, bool $visibleOnly, array $filter = []): array
    {
        global $database;
        $outer = '`' . self::TABLE . '`.`item_id`';
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
        $cats = array_values(array_map('intval', $filter['cats'] ?? []));
        if ($cats !== []) {
            $where .= ' AND EXISTS (SELECT 1 FROM `{TP}mod_listings_item_categories` ic WHERE ic.`item_id` = ' . $outer
                . ' AND ic.`cat_id` IN (' . implode(',', array_fill(0, count($cats), '?')) . '))';
            array_push($params, ...$cats);
        }
        if ((int) ($filter['tag'] ?? 0) > 0) {
            $where .= ' AND EXISTS (SELECT 1 FROM `{TP}mod_listings_item_tags` it WHERE it.`item_id` = ' . $outer . ' AND it.`tag_id` = ?)';
            $params[] = (int) $filter['tag'];
        }
        foreach ($filter['fields'] ?? [] as $f) {
            if (preg_match('/^[a-z][a-z0-9_]*$/', $f['name']) !== 1) {
                continue;
            }
            if ($f['multi']) {
                // the value is one of the field's choices; jsonArrayContains() puts it into the SQL
                $where .= ' AND ' . $database->jsonArrayContains('data', '$.' . $f['name'], (string) $f['value']);
            } else {
                $where .= ' AND ' . $database->jsonGet('data', '$.' . $f['name']) . ' = ?';
                $params[] = (string) $f['value'];
            }
        }
        $q = trim((string) ($filter['q'] ?? ''));
        if ($q !== '') {
            foreach (preg_split('/\s+/u', mb_substr($q, 0, 100)) ?: [] as $word) {
                $like = '%' . addcslashes($word, '%_\\') . '%';
                $parts = ['`title` LIKE ?'];
                $params[] = $like;
                foreach ($filter['q_fields'] ?? [] as $name) {
                    if (preg_match('/^[a-z][a-z0-9_]*$/', $name) === 1) {
                        $parts[] = $database->jsonGet('data', '$.' . $name) . ' LIKE ?';
                        $params[] = $like;
                    }
                }
                $where .= ' AND (' . implode(' OR ', $parts) . ')';
            }
        }
        return [$where, $params];
    }

    private static function decode(array $row): array
    {
        $data = json_decode((string) ($row['data'] ?? ''), true);
        $row['data'] = is_array($data) ? $data : [];
        foreach (['item_id', 'section_id', 'page_id', 'created_at', 'updated_at', 'published_at', 'expires_at', 'owner_user_id', 'reminded_at'] as $key) {
            $row[$key] = (int) ($row[$key] ?? 0);
        }
        return $row;
    }
}
