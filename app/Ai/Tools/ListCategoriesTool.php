<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListCategoriesTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Liệt kê tất cả danh mục bài viết kèm số lượng bài viết trong mỗi danh mục.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $categories = Post::published()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        if ($categories->isEmpty()) {
            return 'Chưa có danh mục nào.';
        }

        $result = "Danh mục bài viết:\n\n";
        foreach ($categories as $cat) {
            $result .= "- **{$cat->category}**: {$cat->count} bài viết\n";
        }

        return $result;
    }
}
