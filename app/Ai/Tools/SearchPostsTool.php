<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchPostsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Tìm kiếm bài viết trong hệ thống theo từ khóa. Trả về danh sách bài viết匹配 với tiêu đề, nội dung hoặc tóm tắt.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()->description('Từ khóa tìm kiếm (VD: "công nghệ", "Python", "AI")'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $keyword = $request->string('keyword');

        if ($keyword->isEmpty()) {
            return 'Vui lòng cung cấp từ khóa tìm kiếm.';
        }

        $posts = Post::published()
            ->search($keyword->value())
            ->select('title', 'summary', 'category', 'author', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        if ($posts->isEmpty()) {
            return "Không tìm thấy bài viết nào với từ khóa \"{$keyword}\".";
        }

        $result = "Tìm thấy {$posts->count()} bài viết:\n\n";
        foreach ($posts as $post) {
            $result .= "- **{$post->title}**\n";
            $result .= "  Tóm tắt: {$post->summary}\n";
            $result .= "  Danh mục: {$post->category} | Tác giả: {$post->author}\n\n";
        }

        return $result;
    }
}
