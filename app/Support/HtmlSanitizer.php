<?php

namespace App\Support;

/**
 * Lightweight HTML sanitizer that strips XSS vectors while preserving safe formatting.
 *
 * Uses a whitelist approach: only explicitly allowed tags and attributes pass through.
 * Dangerous tags (script, iframe, object, etc.) and event handlers (onclick, onerror, etc.)
 * are removed. javascript: and data: URLs in href/src are stripped.
 */
class HtmlSanitizer
{
    /** @var array<string, string[]> Allowed tags => allowed attributes */
    private const ALLOWED_TAGS = [
        'p' => [],
        'br' => [],
        'hr' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'del' => [],
        'ins' => [],
        'mark' => [],
        'small' => [],
        'sub' => [],
        'sup' => [],
        'blockquote' => ['cite'],
        'pre' => [],
        'code' => [],
        'kbd' => [],
        'samp' => [],
        'var' => [],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'ul' => [],
        'ol' => ['start', 'type'],
        'li' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tfoot' => [],
        'tr' => [],
        'th' => ['colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
        'caption' => [],
        'div' => [],
        'span' => [],
        'abbr' => ['title'],
        'sup' => [],
        'sub' => [],
        'figure' => [],
        'figcaption' => [],
        'details' => [],
        'summary' => [],
    ];

    /**
     * Editor-generated class names that are safe to keep.
     *
     * The rich text editor (Quill) marks up alignment with `ql-align-*`,
     * indentation with `ql-indent-*`, font size with `ql-size-*` and code
     * blocks with `ql-syntax`. Nothing else starting with `ql-` is accepted,
     * and classes from other namespaces are always dropped.
     */
    private const ALLOWED_CLASS_PATTERN = '/^ql-(?:(?:align|indent|size)-[a-z0-9]+|syntax)$/i';

    /**
     * The only CSS declaration kept from the `style` attribute.
     *
     * Alignment is the one thing a writer genuinely needs, and a single
     * `text-align` value carries no scripting or exfiltration risk. Every
     * other declaration (position, background-image, behavior, …) is dropped.
     */
    private const ALLOWED_STYLE_DECLARATION = '/^\s*text-align\s*:\s*(left|right|center|justify)\s*$/i';

    /** `data-list` values the editor may put on a list item. */
    private const ALLOWED_DATA_LIST = ['bullet', 'ordered', 'checked', 'unchecked'];

    private const DISALLOWED_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'applet',
        'form', 'input', 'textarea', 'select', 'button', 'label',
        'link', 'meta', 'base', 'area', 'param', 'source', 'track',
        'video', 'audio', 'svg', 'math', 'template', 'noscript',
    ];

    /** @var string[] Event handler attribute prefixes */
    private const EVENT_HANDLER_PREFIX = 'on';

    /** @var string[] Dangerous URL schemes */
    private const DANGEROUS_URL_SCHEMES = ['javascript:', 'data:', 'vbscript:'];

    /**
     * Sanitize HTML content, allowing only safe tags and attributes.
     */
    public static function sanitize(string $html): string
    {
        // Ensure valid UTF-8 — replace malformed sequences instead of re-encoding
        if (! mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        }

        // Remove null bytes
        $html = str_replace("\0", '', $html);

        // Remove disallowed tags and their content
        foreach (self::DISALLOWED_TAGS as $tag) {
            $html = preg_replace("/<{$tag}[\s>].*?<\/{$tag}>/is", '', $html);
            $html = preg_replace("/<{$tag}[\s\/][^>]*>/is", '', $html);
        }

        // Process remaining tags: strip disallowed attributes
        $html = preg_replace_callback(
            '/<(\w+)(\s[^>]*)?>/is',
            function (array $matches): string {
                $tagName = strtolower($matches[1]);
                $attributes = $matches[2] ?? '';

                if (! isset(self::ALLOWED_TAGS[$tagName])) {
                    return '';
                }

                $allowedAttrs = self::ALLOWED_TAGS[$tagName];

                // Filters the attributes (including the editor formatting ones)
                // and keeps only what is safe on this tag.
                $cleaned = self::filterAttributes($attributes, $allowedAttrs);

                return "<{$tagName}{$cleaned}>";
            },
            $html
        );

        // Remove any remaining dangerous URL schemes in href/src attributes
        $html = self::cleanUrls($html);

        return $html;
    }

    /**
     * Filter attributes, keeping only whitelisted ones.
     *
     * `class`, `style` and `data-list` are not taken at face value: each is
     * re-validated on its own terms so the editor's formatting survives while
     * arbitrary CSS or markup classes never reach the page.
     */
    private static function filterAttributes(string $attributeString, array $allowedAttrs): string
    {
        if (preg_match_all('/([\w:-]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+)))?/s', $attributeString, $matches, PREG_SET_ORDER)) {
            $result = '';
            foreach ($matches as $match) {
                $attrName = strtolower($match[1]);
                $attrValue = $match[2] ?? $match[3] ?? $match[4] ?? '';

                // Block event handlers
                if (str_starts_with($attrName, self::EVENT_HANDLER_PREFIX)) {
                    continue;
                }

                // Editor formatting attributes go through their own validators
                if (in_array($attrName, ['class', 'style', 'data-list'], true)) {
                    if ($safeValue = self::sanitizeFormattingAttribute($attrName, $attrValue)) {
                        $result .= " {$attrName}=\"{$safeValue}\"";
                    }

                    continue;
                }

                // Check if attribute is allowed
                if (in_array($attrName, $allowedAttrs, true)) {
                    // For href/src, check for dangerous schemes
                    if (in_array($attrName, ['href', 'src'], true)) {
                        if (self::isDangerousUrl($attrValue)) {
                            continue;
                        }
                    }

                    // Encode the value to prevent attribute injection
                    $safeValue = htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8');
                    $result .= " {$attrName}=\"{$safeValue}\"";
                }
            }

            return $result;
        }

        return '';
    }

    /**
     * Validate one of the editor formatting attributes.
     *
     * Returns the escaped value to keep, or null when the whole attribute has
     * to be dropped (a single bad token invalidates the attribute).
     */
    private static function sanitizeFormattingAttribute(string $name, string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return match ($name) {
            'class' => self::isSafeClassList($value)
                ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                : null,
            'style' => self::sanitizeStyle($value),
            'data-list' => in_array(strtolower($value), self::ALLOWED_DATA_LIST, true)
                ? strtolower($value)
                : null,
            default => null,
        };
    }

    /**
     * Keep only the `text-align` declarations of a style attribute.
     *
     * Editors write several declarations in one attribute; the harmless one is
     * kept and everything else (position, background-image, …) is discarded.
     */
    private static function sanitizeStyle(string $value): ?string
    {
        $kept = [];

        foreach (explode(';', $value) as $declaration) {
            if (preg_match(self::ALLOWED_STYLE_DECLARATION, $declaration, $matches)) {
                $kept[] = 'text-align: '.strtolower($matches[1]);
            }
        }

        if ($kept === []) {
            return null;
        }

        return htmlspecialchars(implode('; ', $kept), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Every class in the list must be one the editor is allowed to emit.
     */
    private static function isSafeClassList(string $value): bool
    {
        $classes = preg_split('/\s+/', $value) ?: [];

        if ($classes === []) {
            return false;
        }

        foreach ($classes as $class) {
            if (! preg_match(self::ALLOWED_CLASS_PATTERN, $class)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a URL contains a dangerous scheme.
     */
    private static function isDangerousUrl(string $url): bool
    {
        $url = strtolower(trim($url));

        foreach (self::DANGEROUS_URL_SCHEMES as $scheme) {
            if (str_starts_with($url, $scheme)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Final pass: clean any dangerous URL schemes that slipped through.
     */
    private static function cleanUrls(string $html): string
    {
        // Clean href attributes
        $html = preg_replace_callback(
            '/(href\s*=\s*)(["\'])(.*?)\2/i',
            function (array $matches): string {
                if (self::isDangerousUrl($matches[3])) {
                    return $matches[1].$matches[2].'#'.$matches[2];
                }

                return $matches[0];
            },
            $html
        );

        // Clean src attributes
        $html = preg_replace_callback(
            '/(src\s*=\s*)(["\'])(.*?)\2/i',
            function (array $matches): string {
                if (self::isDangerousUrl($matches[3])) {
                    return $matches[1].$matches[2].'#'.$matches[2];
                }

                return $matches[0];
            },
            $html
        );

        return $html;
    }
}
