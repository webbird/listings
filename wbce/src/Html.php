<?php
/**
 * Listings – HTML of rich-text fields, reduced to a short list of tags.
 * The input is parsed and written out again element by element, so only
 * what is listed here can reach the page: no scripts, no styles, no event
 * attributes, links only to http, https and mailto.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Html
{
    /** Allowed elements; some are written as another (b → strong). */
    private const TAGS = [
        'p' => 'p', 'br' => 'br', 'strong' => 'strong', 'b' => 'strong', 'em' => 'em', 'i' => 'em',
        'u' => 'u', 's' => 's', 'ul' => 'ul', 'ol' => 'ol', 'li' => 'li', 'a' => 'a',
        'h2' => 'h3', 'h3' => 'h3', 'h4' => 'h4', 'blockquote' => 'blockquote', 'div' => 'p',
    ];

    private const VOID = ['br'];

    /** Dropped with everything inside; other unknown elements keep their text. */
    private const DROP = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript',
        'head', 'title', 'meta', 'link', 'form', 'input', 'textarea', 'select', 'button', 'video', 'audio', 'canvas',
    ];

    public static function clean(string $html): string
    {
        $html = str_replace("\0", '', $html);
        if (trim($html) === '') {
            return '';
        }
        if (!class_exists('DOMDocument')) {
            // without the DOM extension only the text is kept
            return nl2br(h(trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        }
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return '';
        }
        $out = trim(self::children($body));
        // nothing but empty paragraphs and breaks counts as empty
        return trim(strip_tags($out)) === '' ? '' : $out;
    }

    /** The text of an HTML value, for lists, search and feeds. */
    public static function text(string $html): string
    {
        $html = preg_replace('~<(br|/p|/li|/h[1-6]|/blockquote)\b[^>]*>~i', "$0\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace("/[ \t]*\n[ \t\n]*/", "\n", $text));
    }

    private static function children(\DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= self::node($child);
        }
        return $out;
    }

    private static function node(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (!$node instanceof \DOMElement) {
            return '';
        }
        $name = strtolower($node->nodeName);
        if (in_array($name, self::DROP, true)) {
            return '';
        }
        $tag = self::TAGS[$name] ?? null;
        if ($tag === null) {
            return self::children($node);
        }
        if (in_array($tag, self::VOID, true)) {
            return '<' . $tag . '>';
        }
        $attributes = '';
        if ($tag === 'a') {
            $href = trim((string) $node->getAttribute('href'));
            $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
            if ($href === '' || !in_array($scheme, ['http', 'https', 'mailto'], true)) {
                // a link to javascript: or nowhere keeps its text only
                return self::children($node);
            }
            $attributes = ' href="' . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
                . ($scheme === 'mailto' ? '' : ' rel="nofollow noopener ugc"');
        }
        $inner = self::children($node);
        if ($inner === '' && $tag !== 'p') {
            return '';
        }
        return '<' . $tag . $attributes . '>' . $inner . '</' . $tag . '>';
    }
}
