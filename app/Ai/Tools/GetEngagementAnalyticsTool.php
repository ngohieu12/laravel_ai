<?php

namespace App\Ai\Tools;

use App\Models\PostEvent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Engagement overview for admins: totals, per-period numbers with the change
 * vs. the previous period, top posts and the share platform breakdown.
 */
class GetEngagementAnalyticsTool extends AnalyticsTool
{
    public function description(): Stringable|string
    {
        return 'Phân tích tương tác tổng quan cho admin: tổng số lượt xem, lượt chia sẻ, lượt yêu thích, bình luận; số liệu trong khoảng thời gian (7/30/90 ngày hoặc toàn bộ) kèm mức tăng/giảm so với kỳ trước; top bài viết nổi bật và tỷ lệ chia sẻ theo từng nền tảng. CHỈ dành cho admin.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()
                ->description('Khoảng thời gian phân tích: "7", "30", "90", "365" (ngày) hoặc "all" (toàn bộ). Mặc định: 30 ngày.')
                ->default('30'),
            'limit' => $schema->integer()
                ->description('Số bài viết top muốn liệt kê (mặc định 5, tối đa 10)')
                ->default(5),
            'metric' => $schema->string()
                ->description('Tiêu chí xếp hạng bài viết: "views", "shares", "favorites", "comments" hoặc "engagement" (mặc định).')
                ->default('engagement'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (! $this->isAdmin()) {
            return self::ACCESS_DENIED_MESSAGE;
        }

        $analytics = $this->analytics();
        $period = $this->resolvePeriod($request->input('period', '30'));
        $limit = $this->clampLimit($request->input('limit', 5), 5, 10);
        $metric = (string) $request->input('metric', 'engagement');

        $overview = $analytics->overview($period);
        $periodTotals = $overview['period_totals'];
        $totals = $overview['totals'];
        $changes = $overview['changes'];

        $result = '**📊 Phân tích tương tác — '.$overview['period']['label']."**\n\n";
        $result .= '_Trong kỳ:_ '
            .'👁️ '.$this->number($periodTotals['views']).' lượt xem | '
            .'🔗 '.$this->number($periodTotals['shares']).' lượt chia sẻ | '
            .'❤️ '.$this->number($periodTotals['favorites']).' lượt yêu thích | '
            .'💬 '.$this->number($periodTotals['comments'])." bình luận\n";

        if ($changes !== []) {
            $result .= '_So với kỳ trước:_ '
                .$this->changeLabel('xem', $changes['views'])
                .', '.$this->changeLabel('chia sẻ', $changes['shares'])
                .', '.$this->changeLabel('yêu thích', $changes['favorites'])
                .', '.$this->changeLabel('bình luận', $changes['comments'])."\n";
        }

        $result .= '_Luỹ kế toàn blog:_ '
            .'👁️ '.$this->number($totals['views'])
            .' | 🔗 '.$this->number($totals['shares'])
            .' | ❤️ '.$this->number($totals['favorites'])
            .' | 💬 '.$this->number($totals['comments'])
            .' | 🧮 điểm tương tác '.$this->number($totals['engagement'])."\n";

        $result .= '_Nội dung:_ '.$this->number($overview['posts']['published']).' bài đã xuất bản / '
            .$this->number($overview['posts']['total']).' bài ('.$this->number($overview['posts']['draft']).' nháp), '
            .$this->number($overview['authors']['active_in_period'])." người dùng có tương tác trong kỳ\n";

        $topPosts = $analytics->topPosts(limit: $limit, metric: $metric);

        if ($topPosts->isNotEmpty()) {
            $metricLabel = [
                'views' => 'nhiều lượt xem nhất',
                'shares' => 'nhiều lượt chia sẻ nhất',
                'favorites' => 'nhiều lượt yêu thích nhất',
                'comments' => 'nhiều bình luận nhất',
                'engagement' => 'tương tác cao nhất',
            ][$metric] ?? 'tương tác cao nhất';

            $result .= "\n**🏆 Top {$limit} bài viết {$metricLabel}:**\n\n";

            foreach ($topPosts->values() as $index => $post) {
                $result .= $this->medal($index + 1).' **'.$post->title."**\n";
                $result .= '   👁️ '.$this->number($post->views_count)
                    .' | 🔗 '.$this->number($post->shares_count)
                    .' | ❤️ '.$this->number($post->favorites_count)
                    .' | 💬 '.$this->number($post->comments_count)
                    .' | 📂 '.$post->category
                    .' | ✍️ '.($post->user?->name ?? 'Không rõ')."\n\n";
            }
        }

        $platforms = $analytics->sharePlatforms($period);

        if ($platforms->isNotEmpty()) {
            $result .= '**📣 Lượt chia sẻ theo nền tảng ('.$overview['period']['label'].'):** ';
            $result .= $platforms
                ->map(fn (array $platform): string => $platform['label'].' '.$this->number($platform['shares']).' ('.$platform['percentage'].'%)')
                ->implode(' · ');
            $result .= "\n";
        }

        $recentEvent = PostEvent::query()->with('post:id,title')->latest()->first();

        if ($recentEvent) {
            $result .= "\n🕒 Tương tác gần nhất: ".$recentEvent->icon().' '.$recentEvent->label()
                .' trên bài *'.($recentEvent->post?->title ?? 'đã xoá').'* lúc '
                .$recentEvent->created_at?->format('d/m/Y H:i');
        }

        return $result;
    }

    /**
     * @param  array{absolute: float, percentage: float}  $change
     */
    private function changeLabel(string $metric, array $change): string
    {
        $arrow = $change['absolute'] > 0 ? '↑' : ($change['absolute'] < 0 ? '↓' : '→');
        $sign = $change['absolute'] > 0 ? '+' : '';

        return $metric.' '.$arrow.' '.$sign.$this->number($change['absolute']).' ('.$sign.$change['percentage'].'%)';
    }
}
