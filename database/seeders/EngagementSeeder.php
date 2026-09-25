<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\PostEvent;
use App\Models\User;
use App\Services\PostEngagementTracker;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Generates realistic engagement history (views, shares, favorites, comments)
 * spread over the last weeks so the admin analytics dashboard and the analytics
 * chatbot tools have something to analyse.
 *
 * Run with: php artisan db:seed --class=EngagementSeeder
 */
class EngagementSeeder extends Seeder
{
    /**
     * Number of days of history to generate.
     */
    private const DAYS = 30;

    /** @var string[] */
    private const PLATFORM_WEIGHTS = ['facebook', 'facebook', 'facebook', 'x', 'x', 'linkedin', 'telegram', 'copy', 'email'];

    /** @var string[] */
    private const VIEW_SOURCES = ['direct', 'internal', 'chatbot', 'search', 'facebook', 'external'];

    /** @var string[] */
    private const COMMENT_SAMPLES = [
        'Bài viết rất hữu ích, cảm ơn tác giả đã chia sẻ!',
        'Mình đang tìm hiểu đúng chủ đề này, lưu lại để đọc kỹ hơn.',
        'Phần ví dụ hơi khó với người mới, bạn có thể giải thích thêm không?',
        'Áp dụng vào dự án của mình và thấy hiệu quả rõ rệt 👍',
        'Nội dung ngắn gọn, dễ hiểu, mong bạn viết thêm phần nâng cao.',
        'Cho mình hỏi phần cấu hình thì nên bắt đầu từ đâu?',
    ];

    public function run(): void
    {
        $random = new Randomizer(new Mt19937(20260920));

        $users = User::query()->pluck('id')->all();

        if ($users === []) {
            $users = User::factory(5)->create()->pluck('id')->all();
        }

        $posts = Post::query()->get(['id', 'user_id', 'category']);

        if ($posts->count() < 6) {
            $authorIds = User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_CREATOR])->pluck('id')->all();

            Post::factory()
                ->count(6 - $posts->count())
                ->when($authorIds !== [], fn ($factory) => $factory->state(['user_id' => $this->pick($random, $authorIds)]))
                ->create();

            $posts = Post::query()->get(['id', 'user_id', 'category']);
        }

        if ($posts->isEmpty()) {
            return;
        }

        $today = CarbonImmutable::now()->startOfDay();
        $events = [];
        $comments = [];
        $favorites = [];
        $seenFavorites = [];

        // Each post gets its own popularity so rankings are not flat.
        $weights = [];
        foreach ($posts as $post) {
            $weights[$post->id] = $random->getInt(3, 10);
        }

        for ($daysAgo = self::DAYS; $daysAgo >= 0; $daysAgo--) {
            $day = $today->subDays($daysAgo);
            $isWeekend = in_array($day->dayOfWeekIso, [6, 7], true);

            // Recent days are busier than older ones (growth trend).
            $baseViews = $random->getInt(1, 6) + (int) round((self::DAYS - $daysAgo) / 10);

            if ($isWeekend) {
                $baseViews = (int) max(1, round($baseViews * 0.6));
            }

            foreach ($posts as $post) {
                $views = (int) max(0, round($baseViews * ($weights[$post->id] / 10)));

                for ($i = 0; $i < $views; $i++) {
                    $events[] = $this->event($random, $post->id, $users, PostEvent::TYPE_VIEW, $day, [
                        'source' => $this->pick($random, self::VIEW_SOURCES),
                    ]);
                }

                if ($views === 0) {
                    continue;
                }

                $shares = $random->getInt(0, max(1, (int) ceil($views / 4)));

                for ($i = 0; $i < $shares; $i++) {
                    $events[] = $this->event($random, $post->id, $users, PostEvent::TYPE_SHARE, $day, [
                        'platform' => $this->pick($random, self::PLATFORM_WEIGHTS),
                    ]);
                }

                if ($random->getInt(1, 100) <= 55) {
                    $userId = $this->pick($random, $users);
                    $events[] = $this->event($random, $post->id, [$userId], PostEvent::TYPE_FAVORITE_ADD, $day);

                    $key = $userId.':'.$post->id;

                    if (! isset($seenFavorites[$key])) {
                        $seenFavorites[$key] = true;
                        $favorites[] = [
                            'user_id' => $userId,
                            'post_id' => $post->id,
                            'created_at' => $this->timestamp($random, $day),
                            'updated_at' => $this->timestamp($random, $day),
                        ];
                    }
                }

                if ($random->getInt(1, 100) <= 30) {
                    $userId = $this->pick($random, $users);
                    $timestamp = $this->timestamp($random, $day);
                    $events[] = $this->event($random, $post->id, [$userId], PostEvent::TYPE_COMMENT, $day);

                    $comments[] = [
                        'post_id' => $post->id,
                        'user_id' => $userId,
                        'parent_id' => null,
                        'content' => $this->pick($random, self::COMMENT_SAMPLES),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        $this->insertInChunks(PostEvent::query(), $events);
        $this->insertInChunks(Comment::query(), $comments);

        foreach ($favorites as $row) {
            Favorite::firstOrCreate([
                'user_id' => $row['user_id'],
                'post_id' => $row['post_id'],
            ]);
        }

        // Reconcile the denormalised counters with the relations + event log.
        $tracker = app(PostEngagementTracker::class);

        Post::query()->each(function (Post $post) use ($tracker): void {
            $tracker->syncCounters($post);
        });

        $this->command?->info(sprintf(
            'EngagementSeeder: %s sự kiện, %s bình luận, %s lượt yêu thích cho %s bài viết (%s ngày).',
            number_format(count($events)),
            number_format(count($comments)),
            number_format(count($favorites)),
            number_format($posts->count()),
            self::DAYS,
        ));
    }

    /**
     * @param  int[]  $candidateUsers
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function event(Randomizer $random, int $postId, array $candidateUsers, string $type, CarbonImmutable $day, array $meta = []): array
    {
        $userId = $candidateUsers === [] ? null : $this->pick($random, $candidateUsers);
        $timestamp = $this->timestamp($random, $day);

        return [
            'post_id' => $postId,
            // Most views come from guests, the rest are attributable.
            'user_id' => $type === PostEvent::TYPE_VIEW && $random->getInt(1, 100) <= 60 ? null : $userId,
            'type' => $type,
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'session_hash' => hash('sha256', 'seed:'.$postId.':'.$day->toDateString().':'.$random->getInt(1, 500)),
            'ip_hash' => hash('sha256', 'seed-ip:'.$random->getInt(1, 2000)),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * Pick one random element of a non-empty list (deterministic for the seeded engine).
     *
     * @template T
     *
     * @param  array<array-key, T>  $items
     * @return T
     */
    private function pick(Randomizer $random, array $items): mixed
    {
        return $items[$random->pickArrayKeys($items, 1)[0]];
    }

    private function timestamp(Randomizer $random, CarbonImmutable $day): CarbonImmutable
    {
        return $day->addHours($random->getInt(6, 22))->addMinutes($random->getInt(0, 59));
    }

    /**
     * Bulk insert rows without hydrating models.
     *
     * @param  Builder<Model>  $query
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function insertInChunks(Builder $query, array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            $query->insert($chunk);
        }
    }
}
