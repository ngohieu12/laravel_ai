<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPostDetailTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Xem chi tiết nội dung bài viết theo tiêu đề. Trả về nội dung đầy đủ của bài viết.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title_keyword' => $schema->string()->description('Từ khóa trong tiêu đề bài viết muốn xem chi tiết'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $keyword = $request->string('title_keyword');

        $post = Post::published()
            ->where('title', 'like', "%{$keyword}%")
            ->first();

        if (!$post) {
            return "Không tìm thấy bài viết với tiêu đề chứa \"{$keyword}\".";
        }

        return "**{$post->title}**\n\n" .
            "Tác giả: {$post->author}\n" .
            "Danh mục: {$post->category}\n" .
            "Ngày tạo: {$post->created_at->format('d/m/Y')}\n\n" .
            "Nội dung:\n{$post->content}";
    }
}
