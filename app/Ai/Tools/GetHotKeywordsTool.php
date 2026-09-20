<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * "Từ khoá nào đang có lượt tương tác cao?" — extracts keywords from post
 * titles/summaries/categories/content and ranks them by engagement.
 */
class GetHotKeywordsTool extends AnalyticsTool
{
    public function description(): Stringable|string
    {
        return 'Phân tích từ khoá đang có lượt tương tác cao nhất (từ khoá được trích tự động từ tiêu đề, tóm tắt, danh mục và nội dung bài viết). Trả về số bài viết chứa từ khoá, tổng lượt xem, lượt chia sẻ, lượt yêu thích và bài viết nổi bật nhất. CHỈ dành cho admin — dùng khi hỏi "từ khoá nào hot", "keyword nào nhiều lượt xem", "chủ đề/từ khoá đang trend".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Số lượng từ khoá muốn xem (mặc định 10, tối đa 20)')
                ->default(10),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (! $this->isAdmin()) {
            return self::ACCESS_DENIED_MESSAGE;
        }

        $limit = $this->clampLimit($request->input('limit', 10), 10, 20);
        $keywords = $this->analytics()->topKeywords(limit: $limit);

        if ($keywords->isEmpty()) {
            return 'Chưa có từ khoá nào — hệ thống cần ít nhất một bài viết để phân tích.';
        }

        $result = "**🔑 Top {$limit} từ khoá đang có lượt tương tác cao nhất:**\n\n";
        $result .= '| # | Từ khoá | Số bài | 👁️ Xem | 🔗 Chia sẻ | ❤️ Yêu thích | Điểm |\n';
        $result .= "|---|---------|--------|-------|-----------|-------------|------|\n";

        foreach ($keywords->values() as $index => $keyword) {
            $result .= '| '.$this->medal($index + 1)
                .' | **'.$keyword['keyword'].'**'
                .' | '.$this->number($keyword['posts'])
                .' | '.$this->number($keyword['views'])
                .' | '.$this->number($keyword['shares'])
                .' | '.$this->number($keyword['favorites'])
                .' | '.$this->number($keyword['engagement'])." |\n";
        }

        $topKeyword = $keywords->first();

        if ($topKeyword && $topKeyword['top_post_title']) {
            $result .= "\n📌 Bài viết nổi bật nhất cho từ khoá **{$topKeyword['keyword']}**: *{$topKeyword['top_post_title']}*";
        }

        $result .= "\n\n_(Từ khoá được trích tự động từ tiêu đề/tóm tắt/danh mục/nội dung, đã loại bỏ các từ không mang nghĩa.)_";

        return $result;
    }
}
