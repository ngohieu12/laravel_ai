<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @mention support: finds `@username` tokens in user written content and
 * highlights them with the mentioned account's name and role.
 *
 * Two rendering entry points, one per kind of content:
 *  - `renderText()` for plain text (comments), which escapes first;
 *  - `renderHtml()` for post bodies, which are sanitized HTML and therefore
 *    only text nodes are touched, never the markup itself.
 *
 * Unknown handles are left untouched, so ordinary text such as an email
 * address (`me@example.com`) is never rewritten.
 */
class Mentions
{
    /**
     * A mention token: `@` followed by a username (letters, digits, dash,
     * underscore) that does not start mid-word — so `mail@example.com` is
     * not treated as a mention.
     */
    private const TOKEN_PATTERN = '/(?<![\w@.])@([A-Za-z0-9][A-Za-z0-9_-]{1,29})\b/';

    /**
     * Usernames mentioned in the given content, in order of first appearance
     * and without duplicates.
     *
     * @return list<string>
     */
    public static function usernames(?string $content): array
    {
        if ($content === null || ! str_contains($content, '@')) {
            return [];
        }

        preg_match_all(self::TOKEN_PATTERN, $content, $matches);

        $usernames = [];

        foreach ($matches[1] ?? [] as $username) {
            $normalized = mb_strtolower($username, 'UTF-8');

            if (! in_array($normalized, $usernames, true)) {
                $usernames[] = $normalized;
            }
        }

        return $usernames;
    }

    /**
     * Accounts mentioned in the given content.
     *
     * @return Collection<int, User>
     */
    public static function users(?string $content)
    {
        $usernames = self::usernames($content);

        if ($usernames === []) {
            return collect();
        }

        return User::query()
            ->whereIn('username', $usernames)
            ->get()
            ->keyBy('username');
    }

    /**
     * Plain text with mentions highlighted (comments).
     */
    public static function renderText(?string $content): string
    {
        return self::render(e((string) $content));
    }

    /**
     * Sanitized HTML with mentions highlighted (post bodies).
     *
     * Only the text between tags is processed so the markup — and any
     * attribute value — is left exactly as the sanitizer produced it.
     */
    public static function renderHtml(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $html;
        }

        foreach ($parts as $index => $part) {
            // Odd offsets are the captured tags; skip them.
            if ($index % 2 === 1) {
                continue;
            }

            $parts[$index] = self::render($part);
        }

        return implode('', $parts);
    }

    /**
     * Replace every known mention token of a text chunk with a highlight.
     */
    private static function render(string $text): string
    {
        if (! str_contains($text, '@')) {
            return $text;
        }

        $users = self::users($text);

        if ($users->isEmpty()) {
            return $text;
        }

        return (string) preg_replace_callback(
            self::TOKEN_PATTERN,
            function (array $matches) use ($users): string {
                $user = $users->get(mb_strtolower($matches[1], 'UTF-8'));

                if (! $user instanceof User) {
                    return $matches[0];
                }

                return sprintf(
                    '<span class="mention" data-mention="%s" title="%s">@%s</span>',
                    e((string) $user->username),
                    e($user->name.' · '.$user->roleLabel()),
                    e((string) $user->username)
                );
            },
            $text
        );
    }
}
