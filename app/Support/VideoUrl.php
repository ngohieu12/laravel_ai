<?php

namespace App\Support;

/**
 * Parses public video URLs (YouTube, Vimeo) into embeddable player URLs.
 *
 * Video posts store the original link the author pasted; the embeddable URL
 * is derived on demand so pattern upgrades never require a data migration.
 */
final class VideoUrl
{
    /**
     * Host URL patterns mapped to their embeddable player URL template.
     *
     * @var array<int, array{pattern: non-empty-string, embed: string}>
     */
    private const PATTERNS = [
        [
            // youtube.com/watch?v=ID, youtu.be/ID, /shorts/ID, /embed/ID, /live/ID
            'pattern' => '/(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{6,20})/i',
            'embed' => 'https://www.youtube.com/embed/$1',
        ],
        [
            // vimeo.com/ID, player.vimeo.com/video/ID
            'pattern' => '/vimeo\.com\/(?:video\/)?(\d{6,12})/i',
            'embed' => 'https://player.vimeo.com/video/$1',
        ],
    ];

    /**
     * Embeddable player URL for the given video link, or null when the link
     * is not a URL we can embed.
     */
    public static function embedUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern['pattern'], $url, $matches) === 1) {
                return str_replace('$1', $matches[1], $pattern['embed']);
            }
        }

        return null;
    }

    /**
     * Whether the given link can be embedded as a video player.
     */
    public static function isEmbeddable(?string $url): bool
    {
        return self::embedUrl($url) !== null;
    }
}
