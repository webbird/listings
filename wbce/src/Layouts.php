<?php
/**
 * Listings – list and detail layouts. The defaults are files in layouts/;
 * an edited layout is stored per directory in the settings.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Layouts
{
    public const NAMES = ['list', 'detail'];

    public static function defaultLayout(string $name): string
    {
        $file = dirname(__DIR__) . '/layouts/' . $name . '.htt';
        return in_array($name, self::NAMES, true) && is_file($file) ? (string) file_get_contents($file) : '';
    }

    /** The layout in use: the edited one, or the default. */
    public static function get(array $settings, string $name): string
    {
        $custom = (string) ($settings['layout_' . $name] ?? '');
        return trim($custom) !== '' ? $custom : self::defaultLayout($name);
    }

    /** Layouts are edited in the backend, so they never run PHP or include files. */
    public static function parser(): \LayoutParser
    {
        return (new \LayoutParser())->setSandbox(true);
    }

    /**
     * What is stored for a submitted layout: '' when it equals the default,
     * so a later update of the default reaches this directory too.
     */
    public static function normalise(string $name, string $submitted): string
    {
        $submitted = str_replace(["\r\n", "\r"], "\n", $submitted);
        $default = str_replace(["\r\n", "\r"], "\n", self::defaultLayout($name));
        return trim($submitted) === trim($default) ? '' : $submitted;
    }
}
