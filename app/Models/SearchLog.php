<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One search submitted on the public post listing — powers the admin
 * "lượt tìm kiếm" metrics and the popular-queries table.
 */
class SearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'user_id',
        'results_count',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'results_count' => 'integer',
    ];

    /**
     * Record a search that just ran against the post listing.
     */
    public static function record(string $query, ?int $userId, int $resultsCount): ?self
    {
        $query = mb_substr(trim($query), 0, 255);

        if ($query === '') {
            return null;
        }

        return static::query()->create([
            'query' => $query,
            'user_id' => $userId,
            'results_count' => $resultsCount,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /**
     * Normalised form of the query used to aggregate "popular searches".
     */
    public function normalizedQuery(): string
    {
        return mb_strtolower(trim((string) $this->query));
    }
}
