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

                // If no attributes allowed for this tag, strip all
                if (empty($allowedAttrs) && $attributes !== '') {
                    return "<{$tagName}>";
                }

                // Filter attributes
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
     */
    private static function filterAttributes(string $attributeString, array $allowedAttrs): string
    {
        if (preg_match_all('/(\w+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+)))?/s', $attributeString, $matches, PREG_SET_ORDER)) {
            $result = '';
            foreach ($matches as $match) {
                $attrName = strtolower($match[1]);
                $attrValue = $match[2] ?? $match[3] ?? $match[4] ?? '';

                // Block event handlers
                if (str_starts_with($attrName, self::EVENT_HANDLER_PREFIX)) {
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
