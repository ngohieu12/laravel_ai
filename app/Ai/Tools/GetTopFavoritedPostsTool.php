<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTopFavoritedPostsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Lấy danh sách những bài viết được yêu thích nhiều nhất (top bài phổ biến). Mặc định trả về top 5, có thể tùy chỉnh số lượng.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Số lượng bài viết muốn lấy (mặc định: 5, tối đa: 20)')
                ->default(5),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = (int) $request->input('limit', 5);
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 20) {
            $limit = 20;
        }

        $posts = Post::published()
            ->withCount('favoritedBy as favorites_count')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.id', 'posts.title', 'posts.summary', 'posts.category', 'posts.created_at', 'users.name as author_name')
            ->orderByDesc('favorites_count')
            ->orderByDesc('posts.created_at')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            return 'Hiện tại chưa có bài viết nào được yêu thích.';
        }

        $totalWithFavs = $posts->where('favorites_count', '>', 0)->count();

        $result = "**Top {$limit} bài viết được yêu thích nhất:**\n\n";

        foreach ($posts as $i => $post) {
            $rank = $i + 1;
            $medal = match ($rank) {
                1 => '🥇',
                2 => '🥈',
                3 => '🥉',
                default => "{$rank}.",
            };
            $result .= "{$medal} **{$post->title}**\n";
            $result .= "   ❤️ {$post->favorites_count} lượt yêu thích\n";
            $result .= "   📝 {$post->summary}\n";
            $result .= "   📂 {$post->category} | ✍️ {$post->author_name} | 📅 {$post->created_at->format('d/m/Y')}\n\n";
        }

        if ($totalWithFavs === 0) {
            $result .= "\n_(Chưa có lượt yêu thích nào cho các bài viết này — danh sách được sắp xếp theo ngày đăng mới nhất.)_\n";
        }

        return $result;
    }
}
