<?php
/**
 * Listings – the field set of a directory.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Fields
{
    private const TABLE = '{TP}mod_listings_fields';

    /** public: everybody; owner: the owner of the entry and the admin; admin: the admin only */
    public const VISIBILITY = ['public', 'owner', 'admin'];

    /** Keys the layouts already use for every entry. */
    private const RESERVED = [
        'id', 'title', 'slug', 'url', 'status', 'created', 'updated', 'published',
        'expires', 'fields', 'raw', 'page', 'item', 'section', 'categories', 'tags',
        // keys of the list filter (lst_f[…][c|t|q])
        'c', 't', 'q',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function forSection(int $sectionId): array
    {
        global $database;
        $rows = $database->fetchAll(
            'SELECT * FROM `' . self::TABLE . '` WHERE `section_id` = ? ORDER BY `position`, `field_id`',
            [$sectionId]
        );
        return array_map([self::class, 'decode'], $rows);
    }

    public static function find(int $sectionId, int $fieldId): ?array
    {
        global $database;
        $row = $database->fetchRow(
            'SELECT * FROM `' . self::TABLE . '` WHERE `field_id` = ? AND `section_id` = ?',
            [$fieldId, $sectionId]
        );
        return $row === null ? null : self::decode($row);
    }

    /**
     * Checks a submitted definition. The name cannot change once the
     * directory has entries, because their values are stored under it.
     *
     * @param array<string, mixed> $input
     * @return array{0: array<string, mixed>, 1: list<string>} [row data, errors]
     */
    public static function check(int $sectionId, array $input, ?array $existing, bool $nameLocked): array
    {
        $errors = [];
        $str = static fn (string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';

        $type = $str('type');
        if (!in_array($type, FieldTypes::TYPES, true)) {
            $errors[] = L_('MOD_LISTINGS:ERR_FIELD_TYPE');
            $type = 'text';
        }

        $label = $str('label');
        if ($label === '') {
            $errors[] = L_('MOD_LISTINGS:ERR_FIELD_LABEL');
        } elseif (mb_strlen($label) > 255) {
            $label = mb_substr($label, 0, 255);
        }

        if ($existing !== null && $nameLocked) {
            $name = $existing['name'];
        } else {
            $name = strtolower($str('name'));
            if ($name === '') {
                $name = self::nameFromLabel($label);
            }
            if (preg_match('/^[a-z][a-z0-9_]{0,39}$/', $name) !== 1) {
                $errors[] = L_('MOD_LISTINGS:ERR_FIELD_NAME');
            } elseif (in_array($name, self::RESERVED, true)) {
                $errors[] = L_('MOD_LISTINGS:ERR_FIELD_RESERVED', $name);
            } elseif (self::nameTaken($sectionId, $name, $existing === null ? 0 : (int) $existing['field_id'])) {
                $errors[] = L_('MOD_LISTINGS:ERR_FIELD_TAKEN', $name);
            }
        }

        $visibility = in_array($str('visibility'), self::VISIBILITY, true) ? $str('visibility') : 'public';

        $options = [];
        if (FieldTypes::isChoice($type)) {
            $options['choices'] = self::parseChoices($str('choices'));
            if ($options['choices'] === []) {
                $errors[] = L_('MOD_LISTINGS:ERR_FIELD_CHOICES');
            }
        }
        if ($type === 'text' || $type === 'textarea') {
            $maxlength = $str('maxlength');
            if ($maxlength !== '') {
                if (!ctype_digit($maxlength) || (int) $maxlength < 1 || (int) $maxlength > 65535) {
                    $errors[] = L_('MOD_LISTINGS:ERR_FIELD_MAXLENGTH');
                } else {
                    $options['maxlength'] = (int) $maxlength;
                }
            }
        }
        if ($type === 'number') {
            foreach (['min', 'max'] as $key) {
                $value = str_replace(',', '.', $str($key));
                if ($value !== '' && !is_numeric($value)) {
                    $errors[] = L_('MOD_LISTINGS:ERR_FIELD_RANGE');
                    $value = '';
                }
                $options[$key] = $value;
            }
            if ($options['min'] !== '' && $options['max'] !== '' && (float) $options['min'] > (float) $options['max']) {
                $errors[] = L_('MOD_LISTINGS:ERR_FIELD_RANGE');
            }
        }

        $stores = FieldTypes::stores($type);
        $data = [
            'name'       => $name,
            'type'       => $type,
            'label'      => $label,
            'help'       => mb_substr($str('help'), 0, 255),
            'required'   => $stores && $str('required') === '1' ? 1 : 0,
            'visibility' => $visibility,
            'in_list'    => $stores && $str('in_list') === '1' ? 1 : 0,
            'editable'   => $stores && $visibility !== 'admin' && $str('editable') === '1' ? 1 : 0,
            'filterable' => self::canFilter($type, $visibility) && $str('filterable') === '1' ? 1 : 0,
            'options'    => $options,
        ];
        return [$data, $errors];
    }

    /**
     * Inserts ($fieldId null) or updates a field; returns its ID, or null on a database error.
     *
     * @param array<string, mixed> $data from check()
     */
    public static function save(int $sectionId, ?int $fieldId, array $data): ?int
    {
        global $database;
        $row = ['options' => json_encode($data['options'], JSON_UNESCAPED_UNICODE)] + $data;

        if ($fieldId === null) {
            $position = (int) $database->fetchValue(
                'SELECT MAX(`position`) FROM `' . self::TABLE . '` WHERE `section_id` = ?',
                [$sectionId]
            ) + 1;
            $database->insertRow(self::TABLE, ['section_id' => $sectionId, 'position' => $position] + $row);
            if ($database->hasError()) {
                return null;
            }
            return (int) $database->lastInsertId();
        }

        $database->query(
            'UPDATE `' . self::TABLE . '` SET `name` = ?, `type` = ?, `label` = ?, `help` = ?, `required` = ?,
                    `visibility` = ?, `in_list` = ?, `editable` = ?, `filterable` = ?, `options` = ?
              WHERE `field_id` = ? AND `section_id` = ?',
            [
                $row['name'], $row['type'], $row['label'], $row['help'], $row['required'],
                $row['visibility'], $row['in_list'], $row['editable'], $row['filterable'], $row['options'], $fieldId, $sectionId,
            ]
        );
        return $database->hasError() ? null : $fieldId;
    }

    /** Values stored under the field stay in the entries; they are no longer shown. */
    public static function delete(int $sectionId, int $fieldId): bool
    {
        global $database;
        $database->query(
            'DELETE FROM `' . self::TABLE . '` WHERE `field_id` = ? AND `section_id` = ?',
            [$fieldId, $sectionId]
        );
        return !$database->hasError();
    }

    /**
     * Swaps the field with its neighbour above (-1) or below (+1).
     */
    public static function move(int $sectionId, int $fieldId, int $direction): bool
    {
        $fields = self::forSection($sectionId);
        $ids = array_map(static fn ($f) => (int) $f['field_id'], $fields);
        $index = array_search($fieldId, $ids, true);
        $other = $index === false ? false : $index + ($direction < 0 ? -1 : 1);
        if ($index === false || $other < 0 || $other >= count($ids)) {
            return true;
        }
        // renumber all, so equal or missing positions cannot block the swap
        [$ids[$index], $ids[$other]] = [$ids[$other], $ids[$index]];
        try {
            $GLOBALS['database']->transaction(function (\Database $db) use ($ids, $sectionId) {
                foreach ($ids as $position => $id) {
                    $db->query(
                        'UPDATE `' . self::TABLE . '` SET `position` = ? WHERE `field_id` = ? AND `section_id` = ?',
                        [$position + 1, $id, $sectionId]
                    );
                }
            });
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    /**
     * "value|Label" or "Label" per line.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function parseChoices(string $text): array
    {
        $choices = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            [$value, $label] = str_contains($line, '|')
                ? array_map('trim', explode('|', $line, 2))
                : [$line, $line];
            $value = mb_substr($value, 0, 100);
            if ($value === '' || isset($choices[$value])) {
                continue;
            }
            $choices[$value] = ['value' => $value, 'label' => $label !== '' ? $label : $value];
        }
        return array_values($choices);
    }

    /** The textarea form of the choices, for editing. */
    public static function choicesText(array $field): string
    {
        $lines = [];
        foreach ($field['options']['choices'] ?? [] as $choice) {
            $lines[] = $choice['value'] === $choice['label'] ? $choice['value'] : $choice['value'] . '|' . $choice['label'];
        }
        return implode("\n", $lines);
    }

    private static function nameFromLabel(string $label): string
    {
        // page_filename() transliterates umlauts and accents
        $name = strtolower(page_filename($label));
        $name = trim((string) preg_replace('/[^a-z0-9]+/', '_', $name), '_');
        if ($name !== '' && !ctype_alpha($name[0])) {
            $name = 'f_' . $name;
        }
        return substr($name, 0, 40);
    }

    private static function nameTaken(int $sectionId, string $name, int $exceptId): bool
    {
        global $database;
        return $database->fetchValue(
            'SELECT `field_id` FROM `' . self::TABLE . '` WHERE `section_id` = ? AND `name` = ? AND `field_id` <> ?',
            [$sectionId, $name, $exceptId]
        ) !== '';
    }

    private static function decode(array $row): array
    {
        $options = json_decode((string) ($row['options'] ?? ''), true);
        $row['options'] = is_array($options) ? $options : [];
        $row['field_id'] = (int) $row['field_id'];
        $row['required'] = (int) $row['required'];
        $row['in_list'] = (int) $row['in_list'];
        // admin fields are never the owner's, whatever the column says (it defaults to 1 for older fields)
        $row['editable'] = $row['visibility'] === 'admin' ? 0 : (int) ($row['editable'] ?? 1);
        $row['filterable'] = self::canFilter($row['type'], $row['visibility']) ? (int) ($row['filterable'] ?? 0) : 0;
        return $row;
    }

    /**
     * Visitors filter by public choice fields only: a filter on a hidden
     * field would tell them its value.
     */
    public static function canFilter(string $type, string $visibility): bool
    {
        return FieldTypes::isChoice($type) && $visibility === 'public';
    }

    /**
     * The fields of the owner's form: what the owner may see and edit, and
     * the headings between them. Admin fields keep their values on save.
     *
     * @param list<array<string, mixed>> $fields
     * @return list<array<string, mixed>>
     */
    public static function forOwner(array $fields): array
    {
        $owner = array_values(array_filter(
            $fields,
            static fn ($f) => $f['type'] === 'heading' || ($f['visibility'] !== 'admin' && $f['editable'])
        ));
        // a heading with nothing under it (until the next heading) is left out
        $result = [];
        foreach ($owner as $i => $field) {
            $next = $owner[$i + 1] ?? null;
            if ($field['type'] === 'heading' && ($next === null || $next['type'] === 'heading')) {
                continue;
            }
            $result[] = $field;
        }
        return $result;
    }
}
