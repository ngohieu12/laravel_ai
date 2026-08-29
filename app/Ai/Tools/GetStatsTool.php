<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetStatsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Xem thống kê tổng quan: tổng số bài viết, số danh mục, số tác giả, bài viết mới nhất.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $totalPosts = Post::published()->count();
        $totalCategories = Post::published()->distinct()->count('category');
        $totalAuthors = Post::published()->distinct()->count('user_id');
        $latest = Post::published()->latest()->first();

        $result = "**Thống kê Blog Manager:**\n\n";
        $result .= "- Tổng bài viết: **{$totalPosts}**\n";
        $result .= "- Số danh mục: **{$totalCategories}**\n";
        $result .= "- Số tác giả: **{$totalAuthors}**\n";

        if ($latest) {
            $result .= "\nBài viết mới nhất: **{$latest->title}** ({$latest->created_at->format('d/m/Y')})\n";
        }

        return $result;
    }
}
