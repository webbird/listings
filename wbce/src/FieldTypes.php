<?php
/**
 * Listings – field types: validation and display value (the inputs are twig/_field.twig).
 * One place per type, so the forms in backend and frontend and the
 * layouts treat a value the same way.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class FieldTypes
{
    public const TYPES = [
        'text', 'textarea', 'number', 'email', 'url', 'phone',
        'date', 'datetime', 'select', 'radio', 'checkboxes', 'boolean', 'heading',
    ];

    public const CHOICE_TYPES = ['select', 'radio', 'checkboxes'];

    /** Default maximum length of text values. */
    private const MAXLENGTH = ['text' => 255, 'textarea' => 10000];

    public static function isChoice(string $type): bool
    {
        return in_array($type, self::CHOICE_TYPES, true);
    }

    /** A heading structures the form and stores nothing. */
    public static function stores(string $type): bool
    {
        return $type !== 'heading';
    }

    public static function maxLength(array $field): int
    {
        $max = (int) ($field['options']['maxlength'] ?? 0);
        return $max > 0 ? $max : (self::MAXLENGTH[$field['type']] ?? 255);
    }

    /**
     * Normalises and checks a submitted value.
     * Returns [value, null] or [null, error message]; an empty optional
     * value comes back as [null, null] and is not stored.
     *
     * @return array{0: mixed, 1: ?string}
     */
    public static function validate(array $field, mixed $raw): array
    {
        $type = $field['type'];
        $label = $field['label'] !== '' ? $field['label'] : $field['name'];

        if ($type === 'checkboxes') {
            $values = is_array($raw) ? array_values(array_unique(array_map('strval', $raw))) : [];
            $values = array_values(array_filter($values, static fn ($v) => $v !== ''));
            if ($values === []) {
                return [null, $field['required'] ? L_('MOD_LISTINGS:ERR_REQUIRED', $label) : null];
            }
            $allowed = array_column($field['options']['choices'] ?? [], 'value');
            foreach ($values as $value) {
                if (!in_array($value, $allowed, true)) {
                    return [null, L_('MOD_LISTINGS:ERR_CHOICE', $label)];
                }
            }
            return [$values, null];
        }

        if ($type === 'boolean') {
            $on = is_string($raw) && $raw === '1';
            if (!$on && $field['required']) {
                return [null, L_('MOD_LISTINGS:ERR_REQUIRED', $label)];
            }
            return [$on, null];
        }

        $value = is_string($raw) ? trim(str_replace("\0", '', $raw)) : '';
        if ($type !== 'textarea') {
            $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        } else {
            $value = str_replace(["\r\n", "\r"], "\n", $value);
        }
        if ($value === '') {
            return [null, $field['required'] ? L_('MOD_LISTINGS:ERR_REQUIRED', $label) : null];
        }

        switch ($type) {
            case 'text':
            case 'textarea':
                $max = self::maxLength($field);
                if (mb_strlen($value) > $max) {
                    return [null, L_('MOD_LISTINGS:ERR_TOO_LONG', $label, $max)];
                }
                return [$value, null];

            case 'number':
                $number = str_replace(',', '.', $value);
                if (!is_numeric($number)) {
                    return [null, L_('MOD_LISTINGS:ERR_NUMBER', $label)];
                }
                $number += 0;
                $min = $field['options']['min'] ?? '';
                $max = $field['options']['max'] ?? '';
                if ($min !== '' && $number < (float) $min) {
                    return [null, L_('MOD_LISTINGS:ERR_MIN', $label, $min)];
                }
                if ($max !== '' && $number > (float) $max) {
                    return [null, L_('MOD_LISTINGS:ERR_MAX', $label, $max)];
                }
                return [$number, null];

            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($value) <= 255
                    ? [$value, null]
                    : [null, L_('MOD_LISTINGS:ERR_EMAIL', $label)];

            case 'url':
                // only web addresses, so a layout can put the value into href=""
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                return filter_var($value, FILTER_VALIDATE_URL) !== false && in_array($scheme, ['http', 'https'], true) && strlen($value) <= 2000
                    ? [$value, null]
                    : [null, L_('MOD_LISTINGS:ERR_URL', $label)];

            case 'phone':
                return preg_match('~^\+?[0-9 ()/.\-]{3,40}$~', $value) === 1
                    ? [$value, null]
                    : [null, L_('MOD_LISTINGS:ERR_PHONE', $label)];

            case 'date':
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                return $date !== false && $date->format('Y-m-d') === $value
                    ? [$value, null]
                    : [null, L_('MOD_LISTINGS:ERR_DATE', $label)];

            case 'datetime':
                // datetime-local sends 2026-11-25T14:30; stored as wall time, shown as stored
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', substr($value, 0, 16));
                return $date !== false
                    ? [$date->format('Y-m-d H:i:00'), null]
                    : [null, L_('MOD_LISTINGS:ERR_DATE', $label)];

            case 'select':
            case 'radio':
                $allowed = array_column($field['options']['choices'] ?? [], 'value');
                return in_array($value, $allowed, true)
                    ? [$value, null]
                    : [null, L_('MOD_LISTINGS:ERR_CHOICE', $label)];
        }

        return [null, null];
    }

    /**
     * Readable form of a stored value; '' when there is nothing to show.
     * Dates stay raw so layouts can format them with |datetime.
     */
    public static function display(array $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }
        switch ($field['type']) {
            case 'boolean':
                return $value ? L_('MOD_LISTINGS:YES') : '';
            case 'select':
            case 'radio':
                return self::choiceLabel($field, (string) $value);
            case 'checkboxes':
                $labels = array_map(static fn ($v) => self::choiceLabel($field, (string) $v), (array) $value);
                return implode(', ', $labels);
        }
        return is_scalar($value) ? (string) $value : '';
    }

    private static function choiceLabel(array $field, string $value): string
    {
        foreach ($field['options']['choices'] ?? [] as $choice) {
            if ($choice['value'] === $value) {
                return $choice['label'];
            }
        }
        return $value;
    }
}
