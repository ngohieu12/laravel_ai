<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostEvent extends Model
{
    use HasFactory;

    public const TYPE_VIEW = 'view';

    public const TYPE_SHARE = 'share';

    public const TYPE_FAVORITE_ADD = 'favorite_add';

    public const TYPE_FAVORITE_REMOVE = 'favorite_remove';

    public const TYPE_COMMENT = 'comment';

    /**
     * Event types that make up the "engagement" metric.
     */
    public const ENGAGEMENT_TYPES = [
        self::TYPE_VIEW,
        self::TYPE_SHARE,
        self::TYPE_FAVORITE_ADD,
        self::TYPE_COMMENT,
    ];

    public const TYPES = [
        self::TYPE_VIEW,
        self::TYPE_SHARE,
        self::TYPE_FAVORITE_ADD,
        self::TYPE_FAVORITE_REMOVE,
        self::TYPE_COMMENT,
    ];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        self::TYPE_VIEW => 'Lượt xem',
        self::TYPE_SHARE => 'Lượt chia sẻ',
        self::TYPE_FAVORITE_ADD => 'Yêu thích',
        self::TYPE_FAVORITE_REMOVE => 'Bỏ yêu thích',
        self::TYPE_COMMENT => 'Bình luận',
    ];

    /** @var array<string, string> */
    public const TYPE_ICONS = [
        self::TYPE_VIEW => '👁️',
        self::TYPE_SHARE => '🔗',
        self::TYPE_FAVORITE_ADD => '❤️',
        self::TYPE_FAVORITE_REMOVE => '🤍',
        self::TYPE_COMMENT => '💬',
    ];

    protected $fillable = [
        'post_id',
        'user_id',
        'type',
        'meta',
        'session_hash',
        'ip_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'post_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human readable label, e.g. "Lượt xem".
     */
    public function label(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function icon(): string
    {
        return self::TYPE_ICONS[$this->type] ?? '📌';
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSince(Builder $query, ?\DateTimeInterface $since): Builder
    {
        return $since ? $query->where('created_at', '>=', $since) : $query;
    }

    public function scopeBetween(Builder $query, ?\DateTimeInterface $from, ?\DateTimeInterface $to): Builder
    {
        return $query->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to));
    }
}
