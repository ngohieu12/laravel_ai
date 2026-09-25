<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * "Chủ đề nào đang có lượt tương tác cao?" — ranks categories (chủ đề) by the
 * engagement of their posts.
 */
class GetTrendingTopicsTool extends AnalyticsTool
{
    public function description(): Stringable|string
    {
        return 'Phân tích chủ đề (danh mục) đang có lượt tương tác cao nhất: số bài viết, lượt xem, lượt chia sẻ, lượt yêu thích, bình luận và điểm tương tác. CHỈ dành cho admin — dùng khi hỏi "chủ đề nào hot", "chủ đề tương tác cao", "danh mục nào nhiều lượt xem/yêu thích nhất".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Số lượng chủ đề muốn xem (mặc định 5, tối đa 15)')
                ->default(5),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (! $this->isAdmin()) {
            return self::ACCESS_DENIED_MESSAGE;
        }

        $limit = $this->clampLimit($request->integer('limit', 5), 5, 15);
        $topics = $this->analytics()->topCategories(limit: $limit);

        if ($topics->isEmpty()) {
            return 'Chưa có chủ đề nào có dữ liệu tương tác.';
        }

        $result = "**🔥 Top {$limit} chủ đề đang có lượt tương tác cao nhất:**\n\n";

        foreach ($topics->values() as $index => $topic) {
            $rank = $index + 1;
            $result .= $this->medal($rank).' **'.ucfirst($topic['category'])."** — điểm tương tác ".$this->number($topic['engagement'])."\n";
            $result .= '   👁️ '.$this->number($topic['views'])
                .' lượt xem | 🔗 '.$this->number($topic['shares'])
                .' lượt chia sẻ | ❤️ '.$this->number($topic['favorites'])
                .' lượt yêu thích | 💬 '.$this->number($topic['comments'])
                .' bình luận | 📝 '.$this->number($topic['posts'])." bài viết\n";
            $result .= '   📊 Chiếm '.$topic['share_of_engagement']."% tổng tương tác toàn blog\n\n";
        }

        $result .= "_(Điểm tương tác = lượt xem ×1 + chia sẻ ×4 + yêu thích ×6 + bình luận ×3, tính trên toàn bộ thời gian.)_";

        return $result;
    }
}
