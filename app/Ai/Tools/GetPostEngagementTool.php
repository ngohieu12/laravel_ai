<?php

namespace App\Ai\Tools;

use App\Models\Post;
use App\Support\VietnameseText;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Per-post engagement breakdown for admins ("bài X có bao nhiêu lượt xem?").
 */
class GetPostEngagementTool extends AnalyticsTool
{
    public function description(): Stringable|string
    {
        return 'Xem phân tích tương tác chi tiết của MỘT bài viết: số lượt xem, lượt chia sẻ, lượt yêu thích, bình luận, xu hướng theo ngày, từ khoá của bài và nền tảng chia sẻ. CHỈ dành cho admin — dùng khi hỏi "bài viết X có bao nhiêu lượt xem/chia sẻ/yêu thích", "hiệu quả bài X thế nào".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()
                ->description('ID bài viết (nếu biết)')
                ->nullable()
                ->default(null),
            'title' => $schema->string()
                ->description('Tiêu đề (hoặc một phần tiêu đề / slug) của bài viết cần phân tích — dùng khi không có ID')
                ->nullable()
                ->default(null),
            'period' => $schema->string()
                ->description('Khoảng thời gian phân tích: "7", "30", "90" (ngày) hoặc "all". Mặc định: 30 ngày.')
                ->default('30'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (! $this->isAdmin()) {
            return self::ACCESS_DENIED_MESSAGE;
        }

        $post = $this->findPost($request->all()['post_id'] ?? null, $request->all()['title'] ?? null);

        if (! $post) {
            return 'Không tìm thấy bài viết nào khớp với thông tin bạn cung cấp. Bạn có thể dùng tool search_posts để tra cứu trước.';
        }

        $period = $this->resolvePeriod($request->string('period', '30')->toString());
        $summary = $this->analytics()->postSummary($post, $period);

        $totals = $summary['totals'];
        $periodTotals = $summary['period_totals'];

        $result = '**📈 Phân tích tương tác bài viết: '.$post->title."**\n";
        $result .= '📂 '.$post->category.' | ✍️ '.($post->user?->name ?? 'Không rõ')
            .' | 📅 '.$post->created_at?->format('d/m/Y')."\n\n";

        $result .= '_Luỹ kế:_ '
            .'👁️ '.$this->number($totals['views']).' lượt xem | '
            .'🔗 '.$this->number($totals['shares']).' lượt chia sẻ | '
            .'❤️ '.$this->number($totals['favorites']).' lượt yêu thích | '
            .'💬 '.$this->number($totals['comments']).' bình luận | '
            .'🧮 '.$this->number($totals['engagement'])." điểm tương tác\n";

        $result .= '_'.$summary['period']['label'].':_ '
            .'👁️ '.$this->number($periodTotals['views'])
            .' | 🔗 '.$this->number($periodTotals['shares'])
            .' | ❤️ '.$this->number($periodTotals['favorites'])
            .' | 💬 '.$this->number($periodTotals['comments'])."\n";

        $trend = $summary['trend'];

        if ($trend->isNotEmpty()) {
            $peak = $trend->sortByDesc('engagement')->first();
            $latest = $trend->last();

            $result .= "\n_Xu hướng:_ ngày cao nhất là **{$peak['date']}** với "
                .$this->number($peak['views']).' lượt xem, '.$this->number($peak['shares'])
                .' lượt chia sẻ, '.$this->number($peak['favorites']).' lượt yêu thích';

            if ($latest) {
                $result .= ' | hôm nay ('.$latest['date'].'): '.$this->number($latest['views']).' lượt xem';
            }

            $result .= "\n";
        }

        $platforms = collect($summary['platforms'] ?? []);

        if ($platforms->isNotEmpty()) {
            $result .= "\n_📣 Chia sẻ theo nền tảng:_ ";
            $result .= $platforms
                ->map(fn ($platform): string => $platform['label'].' '.$this->number($platform['shares']))
                ->implode(' · ');
            $result .= "\n";
        }

        $keywords = $summary['keywords'];

        if ($keywords !== []) {
            $result .= "\n_🔑 Từ khoá của bài:_ ".implode(', ', array_slice($keywords, 0, 10))."\n";
        }

        return $result;
    }

    private function findPost(mixed $postId, mixed $title): ?Post
    {
        if (is_numeric($postId)) {
            $post = Post::query()->with('user')->find((int) $postId);

            if ($post) {
                return $post;
            }
        }

        $needle = is_string($title) ? trim($title) : '';

        if ($needle === '') {
            return null;
        }

        // Case-insensitive in SQL, diacritic-insensitive in PHP.
        $foldedNeedle = $this->fold($needle);

        return Post::query()
            ->with('user')
            ->orderByDesc('views_count')
            ->get()
            ->first(function (Post $post) use ($needle, $foldedNeedle): bool {
                $title = (string) $post->title;
                $slug = (string) $post->slug;

                return str_contains(mb_strtolower($title), mb_strtolower($needle))
                    || str_contains($slug, $this->fold($needle))
                    || str_contains($this->fold($title), $foldedNeedle)
                    || str_contains($this->fold($slug), $foldedNeedle);
            });
    }

    private function fold(string $value): string
    {
        return mb_strtolower(VietnameseText::toAscii($value), 'UTF-8');
    }
}
