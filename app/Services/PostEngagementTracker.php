<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostEvent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Records post engagement (views, shares, favorites, comments) and keeps the
 * denormalised counters on the posts table in sync with the event log.
 *
 * The event log (post_events) powers time-based analytics, the counters power
 * fast listing/ranking queries.
 */
class PostEngagementTracker
{
    /**
     * Repeated views from the same visitor within this window are ignored.
     */
    public const VIEW_DEDUPLICATION_MINUTES = 60;

    /**
     * Column on posts that stores the running total for each event type.
     *
     * @var array<string, string>
     */
    private const COUNTER_COLUMNS = [
        PostEvent::TYPE_VIEW => 'views_count',
        PostEvent::TYPE_SHARE => 'shares_count',
        PostEvent::TYPE_FAVORITE_ADD => 'favorites_count',
        PostEvent::TYPE_FAVORITE_REMOVE => 'favorites_count',
        PostEvent::TYPE_COMMENT => 'comments_count',
    ];

    /**
     * Register a view for the given post.
     *
     * @return bool true when the view was unique and counted
     */
    public function trackView(Request $request, Post $post, ?Authenticatable $user = null): bool
    {
        $sessionHash = $this->sessionHash($request);
        $ipHash = $this->ipHash($request);

        // A visitor is the same browser session or (when the session cannot be
        // matched) the same IP — repeated views inside the window are ignored.
        $alreadyViewed = PostEvent::query()
            ->ofType(PostEvent::TYPE_VIEW)
            ->where('post_id', $post->id)
            ->where('created_at', '>=', now()->subMinutes(self::VIEW_DEDUPLICATION_MINUTES))
            ->where(function (Builder $query) use ($sessionHash, $ipHash) {
                $query
                    ->when($sessionHash !== null, fn (Builder $query) => $query->orWhere('session_hash', $sessionHash))
                    ->when($ipHash !== null, fn (Builder $query) => $query->orWhere('ip_hash', $ipHash));
            })
            ->exists();

        if ($alreadyViewed) {
            return false;
        }

        $this->record(
            post: $post,
            type: PostEvent::TYPE_VIEW,
            user: $user ?? $request->user(),
            meta: [
                'source' => $this->viewSource($request),
            ],
            sessionHash: $sessionHash,
            ipHash: $ipHash,
        );

        return true;
    }

    /**
     * Register a share click.
     *
     * @param  string  $platform  facebook|x|linkedin|telegram|email|copy|other
     */
    public function trackShare(Request $request, Post $post, string $platform = 'other', ?Authenticatable $user = null): PostEvent
    {
        $referer = substr((string) $request->headers->get('referer'), 0, 190);

        return $this->record(
            post: $post,
            type: PostEvent::TYPE_SHARE,
            user: $user ?? $request->user(),
            meta: array_filter([
                'platform' => $this->normalizePlatform($platform),
                'referer' => $referer,
            ], fn (string $value): bool => $value !== ''),
            sessionHash: $this->sessionHash($request),
            ipHash: $this->ipHash($request),
        );
    }

    /**
     * Register a favorite being added or removed.
     *
     * @param  bool  $favorited  true when the post was just favorited
     */
    public function trackFavorite(Request $request, Post $post, bool $favorited, ?Authenticatable $user = null): PostEvent
    {
        $user ??= $request->user();

        return $this->record(
            post: $post,
            type: $favorited ? PostEvent::TYPE_FAVORITE_ADD : PostEvent::TYPE_FAVORITE_REMOVE,
            user: $user,
            meta: [],
            sessionHash: $this->sessionHash($request),
            ipHash: $this->ipHash($request),
        );
    }

    /**
     * Register a new comment (used as an engagement signal only).
     */
    public function trackComment(Request $request, Post $post, ?Authenticatable $user = null): PostEvent
    {
        return $this->record(
            post: $post,
            type: PostEvent::TYPE_COMMENT,
            user: $user ?? $request->user(),
            meta: [],
            sessionHash: $this->sessionHash($request),
            ipHash: $this->ipHash($request),
        );
    }

    /**
     * Store an event and adjust the matching counter on the post row.
     *
     * @param  array<string, mixed>  $meta
     */
    public function record(
        Post $post,
        string $type,
        ?Authenticatable $user = null,
        array $meta = [],
        ?string $sessionHash = null,
        ?string $ipHash = null,
    ): PostEvent {
        $event = PostEvent::create([
            'post_id' => $post->id,
            'user_id' => $user?->getAuthIdentifier(),
            'type' => $type,
            'meta' => $meta,
            'session_hash' => $sessionHash ?? ($user ? 'user:'.$user->getAuthIdentifier() : null),
            'ip_hash' => $ipHash,
        ]);

        $column = self::COUNTER_COLUMNS[$type] ?? null;

        if ($type === PostEvent::TYPE_FAVORITE_ADD || $type === PostEvent::TYPE_FAVORITE_REMOVE) {
            // The favorites pivot is the source of truth — resync the counter
            // so it can never drift from the rows that actually exist.
            $post->newQuery()->whereKey($post->getKey())->update([
                'favorites_count' => $post->favoritedBy()->count(),
            ]);
        } elseif ($column !== null) {
            $post->newQuery()->whereKey($post->getKey())->increment($column);
        }

        return $event;
    }

    /**
     * Recount a single post's counters from its persisted relations.
     *
     * Views/shares only live in the event log, so they are summed from there.
     */
    public function syncCounters(Post $post): void
    {
        $post->newQuery()->whereKey($post->getKey())->update([
            'favorites_count' => $post->favoritedBy()->count(),
            'comments_count' => $post->comments()->count(),
            'views_count' => PostEvent::query()->ofType(PostEvent::TYPE_VIEW)->where('post_id', $post->getKey())->count(),
            'shares_count' => PostEvent::query()->ofType(PostEvent::TYPE_SHARE)->where('post_id', $post->getKey())->count(),
        ]);
    }

    /**
     * @return string[]
     */
    public function knownPlatforms(): array
    {
        return ['facebook', 'x', 'linkedin', 'telegram', 'email', 'copy', 'other'];
    }

    private function normalizePlatform(string $platform): string
    {
        $platform = strtolower(trim($platform));

        return in_array($platform, $this->knownPlatforms(), true) ? $platform : 'other';
    }

    /**
     * Where did the reader arrive from? Used to spot traffic driven by shares.
     */
    private function viewSource(Request $request): string
    {
        $referer = (string) $request->headers->get('referer');

        if ($referer === '') {
            return 'direct';
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return 'direct';
        }

        $host = strtolower(preg_replace('/^www\./', '', $host) ?? $host);

        if ($request->getHost() === $host) {
            return str_contains($referer, '/chatbot') ? 'chatbot' : 'internal';
        }

        foreach (['facebook' => 'facebook', 'fb.' => 'facebook', 'twitter' => 'x', 'x.com' => 'x', 'linkedin' => 'linkedin', 't.me' => 'telegram', 'telegram' => 'telegram', 'google' => 'search'] as $needle => $source) {
            if (str_contains($host, $needle)) {
                return $source;
            }
        }

        return 'external';
    }

    private function sessionHash(Request $request): ?string
    {
        try {
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        } catch (\Throwable) {
            $sessionId = null;
        }

        return is_string($sessionId) && $sessionId !== '' ? hash('sha256', 'session:'.$sessionId) : null;
    }

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return is_string($ip) && $ip !== '' ? hash('sha256', 'ip:'.$ip) : null;
    }
}
