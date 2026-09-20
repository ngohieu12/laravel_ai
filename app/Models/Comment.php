<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    /**
     * Maximum nesting depth for replies (0 = top level only, we allow up to 3 => 4 levels total).
     */
    public const MAX_DEPTH = 3;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
    ];

    protected $casts = [
        'post_id' => 'integer',
        'user_id' => 'integer',
        'parent_id' => 'integer',
    ];

    protected $appends = ['favorites_count', 'is_favorited', 'depth'];

    // Transient (non-stored) attribute values keyed in $attributes.
    protected int $computedDepth = 0;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->latest();
    }

    /**
     * Users who liked/favorited this comment.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_favorites', 'comment_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Comments written since the given moment.
     */
    public function scopeSince(Builder $query, ?\DateTimeInterface $since): Builder
    {
        return $since ? $query->where('created_at', '>=', $since) : $query;
    }

    // ------------- Accessors -------------

    public function getDepthAttribute(): int
    {
        return $this->computedDepth;
    }

    public function setComputedDepth(int $depth): void
    {
        $this->computedDepth = $depth;
    }

    public function canReply(): bool
    {
        return $this->computedDepth < self::MAX_DEPTH;
    }

    public function getFavoritesCountAttribute(): int
    {
        return (int) ($this->attributes['favorites_count'] ?? 0);
    }

    public function getIsFavoritedAttribute(): bool
    {
        return (bool) ($this->attributes['is_favorited'] ?? false);
    }

    public function setIsFavorited(bool $value): void
    {
        $this->attributes['is_favorited'] = $value;
    }

    /**
     * Load all comments for a post, build a nested tree of replies, attach
     * favorites counts and the current user's favorited state in 3 queries total.
     *
     * @return \Illuminate\Database\Eloquent\Collection root comments (with replies loaded recursively)
     */
    public static function loadForPost(Post $post, ?User $user = null)
    {
        // 1 query: all comments for this post, with user + favorites count.
        $all = static::where('post_id', $post->id)
            ->with('user')
            ->withCount('favoritedBy as favorites_count')
            ->oldest() // load oldest first so parents exist before children
            ->get()
            ->keyBy('id');

        // Build children map (parent_id => [comments])
        $children = [];
        /** @var Comment $comment */
        foreach ($all as $comment) {
            $pid = $comment->parent_id;
            if ($pid === null) {
                continue;
            }
            if (! isset($children[$pid])) {
                $children[$pid] = [];
            }
            $children[$pid][] = $comment;
        }

        // 2nd query: which comment ids this user has favorited.
        if ($user && $all->isNotEmpty()) {
            $favIds = $user->commentFavorites()
                ->whereIn('comment_id', $all->keys())
                ->pluck('comment_id')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        } else {
            $favIds = [];
        }

        // Attach replies relation recursively + set depth + set favorited state.
        $roots = collect();
        foreach ($all as $comment) {
            if ($comment->parent_id === null) {
                $roots->push($comment);
                static::hydrateTree($comment, $children, $favIds, 0);
            }
        }

        // Sort roots newest first.
        return $roots->sortByDesc('created_at')->values();
    }

    /**
     * Recursively attach replies to $comment, set depth and is_favorited flags.
     */
    private static function hydrateTree(Comment $comment, array &$children, array $favIds, int $depth): void
    {
        $comment->setComputedDepth($depth);
        $comment->setIsFavorited(in_array($comment->id, $favIds, true));
        $comment->attributes['favorites_count'] = (int) ($comment->attributes['favorites_count'] ?? 0);

        $replyModels = collect($children[$comment->id] ?? []);

        foreach ($replyModels as $reply) {
            static::hydrateTree($reply, $children, $favIds, $depth + 1);
        }

        // Sort replies newest first.
        $comment->setRelation('replies', $replyModels->sortByDesc('created_at')->values());
    }
}
