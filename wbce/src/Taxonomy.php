<?php
/**
 * Listings – categories (a tree) and tags of a directory, and which entries
 * carry them. An entry may be in several categories and have several tags.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Taxonomy
{
    private const CATS = '{TP}mod_listings_categories';
    private const ITEM_CATS = '{TP}mod_listings_item_categories';
    private const TAGS = '{TP}mod_listings_tags';
    private const ITEM_TAGS = '{TP}mod_listings_item_tags';

    /** Tags: off, a list the admin keeps (fixed), or owners may add new ones (open). */
    public const TAG_MODES = ['off', 'fixed', 'open'];

    public const MAX_TAGS_PER_ITEM = 20;

    // ---- categories ----

    /**
     * The tree in display order, each with `level` (0 = top) and `count`
     * (visible entries in it and below when $countVisible, else all entries).
     *
     * @return list<array<string, mixed>>
     */
    public static function categories(int $sectionId, bool $visibleOnly = false): array
    {
        global $database;
        $rows = $database->fetchAll(
            'SELECT * FROM `' . self::CATS . '` WHERE `section_id` = ? ORDER BY `position`, `name`',
            [$sectionId]
        );
        $byParent = [];
        foreach ($rows as $row) {
            foreach (['cat_id', 'parent_id', 'position', 'visible'] as $key) {
                $row[$key] = (int) $row[$key];
            }
            $byParent[$row['parent_id']][] = $row;
        }
        $tree = [];
        $walk = static function (int $parent, int $level) use (&$walk, &$tree, $byParent, $visibleOnly): void {
            foreach ($byParent[$parent] ?? [] as $cat) {
                // a hidden category hides its subcategories as well
                if ($visibleOnly && !$cat['visible']) {
                    continue;
                }
                // prefix: the level as dashes, for select lists
                $tree[] = $cat + ['level' => $level, 'prefix' => str_repeat('– ', $level)];
                $walk($cat['cat_id'], $level + 1);
            }
        };
        $walk(0, 0);
        return $tree;
    }

    public static function findCategory(int $sectionId, int $catId): ?array
    {
        global $database;
        $row = $database->fetchRow(
            'SELECT * FROM `' . self::CATS . '` WHERE `cat_id` = ? AND `section_id` = ?',
            [$catId, $sectionId]
        );
        if ($row === null) {
            return null;
        }
        foreach (['cat_id', 'parent_id', 'position', 'visible'] as $key) {
            $row[$key] = (int) $row[$key];
        }
        return $row;
    }

    /**
     * The category and all below it.
     *
     * @param list<array<string, mixed>> $tree from categories()
     * @return list<int>
     */
    public static function withDescendants(array $tree, int $catId): array
    {
        $ids = [$catId];
        $children = [];
        foreach ($tree as $cat) {
            $children[$cat['parent_id']][] = $cat['cat_id'];
        }
        for ($i = 0; $i < count($ids); $i++) {
            foreach ($children[$ids[$i]] ?? [] as $child) {
                $ids[] = $child;
            }
        }
        return $ids;
    }

    /**
     * @param array<string, mixed> $input name, slug, parent_id, description, visible
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    public static function checkCategory(int $sectionId, array $input, ?array $existing): array
    {
        $errors = [];
        $str = static fn (string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
        $name = preg_replace('/\s+/u', ' ', $str('name')) ?? '';
        if ($name === '') {
            $errors[] = L_('MOD_LISTINGS:ERR_CATEGORY_NAME');
        }
        $parent = (int) $str('parent_id');
        $tree = self::categories($sectionId);
        $known = array_column($tree, 'cat_id');
        // never below itself or one of its own subcategories
        $forbidden = $existing === null ? [] : self::withDescendants($tree, $existing['cat_id']);
        if ($parent !== 0 && (!in_array($parent, $known, true) || in_array($parent, $forbidden, true))) {
            $errors[] = L_('MOD_LISTINGS:ERR_CATEGORY_PARENT');
            $parent = 0;
        }
        $slugSource = $str('slug') !== '' ? $str('slug') : $name;
        return [[
            'name'        => mb_substr($name, 0, 255),
            'slug'        => self::uniqueSlug(self::CATS, 'cat_id', $sectionId, $slugSource, (int) ($existing['cat_id'] ?? 0)),
            'parent_id'   => $parent,
            'description' => mb_substr(str_replace(["\r\n", "\r"], "\n", $str('description')), 0, 5000),
            'visible'     => $str('visible') === '1' ? 1 : 0,
        ], $errors];
    }

    public static function saveCategory(int $sectionId, ?array $existing, array $data): ?int
    {
        global $database;
        if ($existing === null) {
            $position = (int) $database->fetchValue(
                'SELECT MAX(`position`) FROM `' . self::CATS . '` WHERE `section_id` = ? AND `parent_id` = ?',
                [$sectionId, $data['parent_id']]
            ) + 1;
            $database->insertRow(self::CATS, ['section_id' => $sectionId, 'position' => $position] + $data);
            return $database->hasError() ? null : (int) $database->lastInsertId();
        }
        $database->query(
            'UPDATE `' . self::CATS . '` SET `name` = ?, `slug` = ?, `parent_id` = ?, `description` = ?, `visible` = ?
              WHERE `cat_id` = ? AND `section_id` = ?',
            [$data['name'], $data['slug'], $data['parent_id'], $data['description'], $data['visible'], $existing['cat_id'], $sectionId]
        );
        return $database->hasError() ? null : $existing['cat_id'];
    }

    /** Subcategories move up to the parent; entries only lose this category. */
    public static function deleteCategory(int $sectionId, array $cat): bool
    {
        try {
            $GLOBALS['database']->transaction(function (\Database $db) use ($sectionId, $cat) {
                $db->query(
                    'UPDATE `' . self::CATS . '` SET `parent_id` = ? WHERE `parent_id` = ? AND `section_id` = ?',
                    [$cat['parent_id'], $cat['cat_id'], $sectionId]
                );
                $db->query('DELETE FROM `' . self::ITEM_CATS . '` WHERE `cat_id` = ? AND `section_id` = ?', [$cat['cat_id'], $sectionId]);
                $db->query('DELETE FROM `' . self::CATS . '` WHERE `cat_id` = ? AND `section_id` = ?', [$cat['cat_id'], $sectionId]);
            });
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    /** Swaps the category with its neighbour among its siblings. */
    public static function moveCategory(int $sectionId, array $cat, int $direction): bool
    {
        $siblings = array_values(array_filter(self::categories($sectionId), static fn ($c) => $c['parent_id'] === $cat['parent_id']));
        $ids = array_column($siblings, 'cat_id');
        $index = array_search($cat['cat_id'], $ids, true);
        $other = $index === false ? false : $index + ($direction < 0 ? -1 : 1);
        if ($index === false || $other < 0 || $other >= count($ids)) {
            return true;
        }
        [$ids[$index], $ids[$other]] = [$ids[$other], $ids[$index]];
        try {
            $GLOBALS['database']->transaction(function (\Database $db) use ($ids, $sectionId) {
                foreach ($ids as $position => $id) {
                    $db->query(
                        'UPDATE `' . self::CATS . '` SET `position` = ? WHERE `cat_id` = ? AND `section_id` = ?',
                        [$position + 1, $id, $sectionId]
                    );
                }
            });
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    /** @return array<int, int> category ID => number of entries (all statuses) */
    public static function categoryCounts(int $sectionId): array
    {
        global $database;
        $counts = [];
        foreach ($database->fetchAll(
            'SELECT `cat_id`, COUNT(*) AS `n` FROM `' . self::ITEM_CATS . '` WHERE `section_id` = ? GROUP BY `cat_id`',
            [$sectionId]
        ) as $row) {
            $counts[(int) $row['cat_id']] = (int) $row['n'];
        }
        return $counts;
    }

    // ---- tags ----

    /** @return list<array<string, mixed>> by name, with `count` (entries of any status) */
    public static function tags(int $sectionId): array
    {
        global $database;
        $rows = $database->fetchAll(
            'SELECT t.`tag_id`, t.`name`, t.`slug`, COUNT(it.`item_id`) AS `count`
               FROM `' . self::TAGS . '` t
               LEFT JOIN `' . self::ITEM_TAGS . '` it ON it.`tag_id` = t.`tag_id`
              WHERE t.`section_id` = ?
              GROUP BY t.`tag_id`, t.`name`, t.`slug`
              ORDER BY t.`name`',
            [$sectionId]
        );
        return array_map(static fn ($r) => ['tag_id' => (int) $r['tag_id'], 'count' => (int) $r['count']] + $r, $rows);
    }

    public static function findTag(int $sectionId, int $tagId): ?array
    {
        global $database;
        $row = $database->fetchRow('SELECT * FROM `' . self::TAGS . '` WHERE `tag_id` = ? AND `section_id` = ?', [$tagId, $sectionId]);
        return $row === null ? null : ['tag_id' => (int) $row['tag_id']] + $row;
    }

    /**
     * "Garden, Pets" → normalised names, at most MAX_TAGS_PER_ITEM.
     *
     * @return list<string>
     */
    public static function parseTags(string $text): array
    {
        $names = [];
        foreach (explode(',', $text) as $name) {
            $name = mb_substr(trim(preg_replace('/\s+/u', ' ', $name) ?? ''), 0, 100);
            if ($name !== '' && !in_array(mb_strtolower($name), array_map('mb_strtolower', $names), true)) {
                $names[] = $name;
            }
        }
        return array_slice($names, 0, self::MAX_TAGS_PER_ITEM);
    }

    /**
     * IDs of the tags with these names; unknown names become new tags
     * only when $create is true.
     *
     * @param list<string> $names
     * @return list<int>
     */
    public static function tagIds(int $sectionId, array $names, bool $create): array
    {
        global $database;
        $ids = [];
        foreach ($names as $name) {
            $slug = self::slugify($name);
            $id = (int) $database->fetchValue(
                'SELECT `tag_id` FROM `' . self::TAGS . '` WHERE `section_id` = ? AND `slug` = ?',
                [$sectionId, $slug]
            );
            if ($id === 0 && $create) {
                $database->insertRow(self::TAGS, ['section_id' => $sectionId, 'name' => $name, 'slug' => $slug]);
                $id = $database->hasError() ? 0 : (int) $database->lastInsertId();
            }
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * Renames a tag. When another tag already has the name, both become
     * one: the entries of this tag get the other, and this one goes.
     *
     * @return array{0: ?string, 1: ?string} [error message or null, name of the tag merged into or null]
     */
    public static function renameTag(int $sectionId, array $tag, string $name): array
    {
        global $database;
        $name = mb_substr(trim(preg_replace('/\s+/u', ' ', $name) ?? ''), 0, 100);
        if ($name === '') {
            return [L_('MOD_LISTINGS:ERR_TAG_NAME'), null];
        }
        $slug = self::slugify($name);
        $other = (int) $database->fetchValue(
            'SELECT `tag_id` FROM `' . self::TAGS . '` WHERE `section_id` = ? AND `slug` = ? AND `tag_id` <> ?',
            [$sectionId, $slug, $tag['tag_id']]
        );
        if ($other > 0) {
            $into = (string) $database->fetchValue('SELECT `name` FROM `' . self::TAGS . '` WHERE `tag_id` = ?', [$other]);
            return [self::mergeTag($sectionId, $tag['tag_id'], $other) ? null : L_('MOD_LISTINGS:ERR_DATABASE'), $into];
        }
        $database->query(
            'UPDATE `' . self::TAGS . '` SET `name` = ?, `slug` = ? WHERE `tag_id` = ? AND `section_id` = ?',
            [$name, $slug, $tag['tag_id'], $sectionId]
        );
        return [$database->hasError() ? L_('MOD_LISTINGS:ERR_DATABASE') : null, null];
    }

    /** The entries of tag $from get tag $into (once), then $from is deleted. */
    public static function mergeTag(int $sectionId, int $from, int $into): bool
    {
        try {
            $GLOBALS['database']->transaction(function (\Database $db) use ($sectionId, $from, $into) {
                // portable "insert what is missing": no INSERT IGNORE, no ON CONFLICT
                $db->query(
                    'INSERT INTO `' . self::ITEM_TAGS . '` (`section_id`, `item_id`, `tag_id`)
                     SELECT `section_id`, `item_id`, ? FROM `' . self::ITEM_TAGS . '` f
                      WHERE f.`tag_id` = ? AND f.`section_id` = ?
                        AND NOT EXISTS (SELECT 1 FROM `' . self::ITEM_TAGS . '` t WHERE t.`item_id` = f.`item_id` AND t.`tag_id` = ?)',
                    [$into, $from, $sectionId, $into]
                );
                $db->query('DELETE FROM `' . self::ITEM_TAGS . '` WHERE `tag_id` = ? AND `section_id` = ?', [$from, $sectionId]);
                $db->query('DELETE FROM `' . self::TAGS . '` WHERE `tag_id` = ? AND `section_id` = ?', [$from, $sectionId]);
                if ($db->hasError()) {
                    throw new \RuntimeException($db->getError());
                }
            });
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public static function deleteTag(int $sectionId, array $tag): bool
    {
        global $database;
        $database->query('DELETE FROM `' . self::ITEM_TAGS . '` WHERE `tag_id` = ? AND `section_id` = ?', [$tag['tag_id'], $sectionId]);
        $database->query('DELETE FROM `' . self::TAGS . '` WHERE `tag_id` = ? AND `section_id` = ?', [$tag['tag_id'], $sectionId]);
        return !$database->hasError();
    }

    // ---- assignments ----

    /**
     * Categories and tags of several entries at once.
     *
     * @param list<int> $itemIds
     * @return array<int, array{categories: list<array<string, mixed>>, tags: list<array<string, mixed>>}>
     */
    public static function forItems(int $sectionId, array $itemIds): array
    {
        global $database;
        $result = [];
        foreach ($itemIds as $id) {
            $result[$id] = ['categories' => [], 'tags' => []];
        }
        if ($itemIds === []) {
            return $result;
        }
        $in = implode(',', array_fill(0, count($itemIds), '?'));
        foreach ($database->fetchAll(
            'SELECT ic.`item_id`, c.`cat_id`, c.`name`, c.`slug`, c.`visible` FROM `' . self::ITEM_CATS . '` ic
               JOIN `' . self::CATS . '` c ON c.`cat_id` = ic.`cat_id`
              WHERE ic.`section_id` = ? AND ic.`item_id` IN (' . $in . ') ORDER BY c.`position`, c.`name`',
            array_merge([$sectionId], $itemIds)
        ) as $row) {
            $result[(int) $row['item_id']]['categories'][] = ['cat_id' => (int) $row['cat_id'], 'name' => $row['name'], 'slug' => $row['slug'], 'visible' => (int) $row['visible']];
        }
        foreach ($database->fetchAll(
            'SELECT it.`item_id`, t.`tag_id`, t.`name`, t.`slug` FROM `' . self::ITEM_TAGS . '` it
               JOIN `' . self::TAGS . '` t ON t.`tag_id` = it.`tag_id`
              WHERE it.`section_id` = ? AND it.`item_id` IN (' . $in . ') ORDER BY t.`name`',
            array_merge([$sectionId], $itemIds)
        ) as $row) {
            $result[(int) $row['item_id']]['tags'][] = ['tag_id' => (int) $row['tag_id'], 'name' => $row['name'], 'slug' => $row['slug']];
        }
        return $result;
    }

    /**
     * Categories and tags from a submitted entry form; null means "leave as
     * it is" (the form had no such input). Owners pick visible categories
     * only, and new tags only when tags are open; the admin may do both.
     *
     * input: categories[] (IDs), tags (text "a, b") or tag_ids[] (fixed tags as checkboxes)
     *
     * @param array<string, mixed> $input
     * @return array{0: ?list<int>, 1: ?list<int>}
     */
    public static function fromInput(int $sectionId, array $input, string $tagMode, bool $byAdmin): array
    {
        $cats = null;
        $offered = array_column(self::categories($sectionId, !$byAdmin), 'cat_id');
        if ($offered !== []) {
            $chosen = is_array($input['categories'] ?? null) ? array_map('intval', $input['categories']) : [];
            $cats = array_values(array_unique(array_filter($chosen, static fn ($id) => in_array($id, $offered, true))));
        }

        $tags = null;
        if ($tagMode === 'fixed' && !$byAdmin) {
            // owners pick from the list; a text field sent along is ignored
            $known = array_column(self::tags($sectionId), 'tag_id');
            $chosen = is_array($input['tag_ids'] ?? null) ? array_map('intval', $input['tag_ids']) : [];
            $tags = array_slice(array_values(array_unique(array_filter($chosen, static fn ($id) => in_array($id, $known, true)))), 0, self::MAX_TAGS_PER_ITEM);
        } elseif (($byAdmin || $tagMode === 'open') && is_string($input['tags'] ?? null)) {
            $tags = self::tagIds($sectionId, self::parseTags($input['tags']), true);
        }
        return [$cats, $tags];
    }

    /**
     * Replaces the categories and/or tags of an entry; null keeps them.
     *
     * @param ?list<int> $catIds checked against the section
     * @param ?list<int> $tagIds from tagIds()
     */
    public static function assign(int $sectionId, int $itemId, ?array $catIds, ?array $tagIds): bool
    {
        if ($catIds === null && $tagIds === null) {
            return true;
        }
        if ($catIds !== null) {
            $known = array_column(self::categories($sectionId), 'cat_id');
            $catIds = array_values(array_unique(array_filter(array_map('intval', $catIds), static fn ($id) => in_array($id, $known, true))));
        }
        try {
            $GLOBALS['database']->transaction(function (\Database $db) use ($sectionId, $itemId, $catIds, $tagIds) {
                if ($catIds !== null) {
                    $db->query('DELETE FROM `' . self::ITEM_CATS . '` WHERE `item_id` = ? AND `section_id` = ?', [$itemId, $sectionId]);
                    foreach ($catIds as $catId) {
                        $db->insertRow(self::ITEM_CATS, ['section_id' => $sectionId, 'item_id' => $itemId, 'cat_id' => $catId]);
                    }
                }
                if ($tagIds !== null) {
                    $db->query('DELETE FROM `' . self::ITEM_TAGS . '` WHERE `item_id` = ? AND `section_id` = ?', [$itemId, $sectionId]);
                    foreach (array_unique($tagIds) as $tagId) {
                        $db->insertRow(self::ITEM_TAGS, ['section_id' => $sectionId, 'item_id' => $itemId, 'tag_id' => (int) $tagId]);
                    }
                }
            });
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public static function deleteForItem(int $sectionId, int $itemId): void
    {
        global $database;
        $database->query('DELETE FROM `' . self::ITEM_CATS . '` WHERE `item_id` = ? AND `section_id` = ?', [$itemId, $sectionId]);
        $database->query('DELETE FROM `' . self::ITEM_TAGS . '` WHERE `item_id` = ? AND `section_id` = ?', [$itemId, $sectionId]);
    }

    // ---- helpers ----

    public static function slugify(string $text): string
    {
        $slug = strtolower(page_filename($text));
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
        return substr($slug !== '' ? $slug : 'item', 0, 110);
    }

    private static function uniqueSlug(string $table, string $idColumn, int $sectionId, string $source, int $exceptId): string
    {
        global $database;
        $base = self::slugify($source);
        $slug = $base;
        for ($n = 2; ; $n++) {
            $taken = $database->fetchValue(
                'SELECT `' . $idColumn . '` FROM `' . $table . '` WHERE `section_id` = ? AND `slug` = ? AND `' . $idColumn . '` <> ?',
                [$sectionId, $slug, $exceptId]
            );
            if ($taken === '') {
                return $slug;
            }
            $slug = $base . '-' . $n;
        }
    }
}
