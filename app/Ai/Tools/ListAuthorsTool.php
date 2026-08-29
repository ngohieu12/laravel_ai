<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListAuthorsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Liệt kê tất cả tác giả kèm số lượng bài viết của mỗi người.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $authors = Post::published()
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('count')
            ->get();

        if ($authors->isEmpty()) {
            return 'Chưa có tác giả nào.';
        }

        $result = "Tác giả:\n\n";
        foreach ($authors as $author) {
            $result .= "- **{$author->name}**: {$author->count} bài viết\n";
        }

        return $result;
    }
}
