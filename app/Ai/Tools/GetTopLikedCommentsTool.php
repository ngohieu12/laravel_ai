<?php

namespace App\Ai\Tools;

use App\Models\Comment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTopLikedCommentsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Lấy danh sách các bình luận được thích/yêu thích nhiều nhất trên toàn blog. Dùng khi người dùng hỏi về bình luận hay nhất, nhiều tim nhất, bình luận phổ biến, ai bình luận hay, v.v.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Số lượng bình luận muốn lấy (mặc định 5, tối đa 20)')
                ->default(5),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = $request->integer('limit', 5);
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 20) {
            $limit = 20;
        }

        $comments = Comment::query()
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->where('posts.is_published', true)
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->select('comments.id', 'comments.content', 'comments.post_id', 'posts.title as post_title', 'users.name as author_name', 'comments.created_at')
            ->withCount('favoritedBy as likes_count')
            ->having('likes_count', '>', 0)
            ->orderByDesc('likes_count')
            ->orderByDesc('comments.created_at')
            ->limit($limit)
            ->get();

        if ($comments->isEmpty()) {
            return 'Hiện tại chưa có bình luận nào được thích.';
        }

        $result = "**Top {$limit} bình luận được thích nhiều nhất:**\n\n";
        foreach ($comments as $i => $c) {
            $rank = $i + 1;
            $medal = match ($rank) {
                1 => '🥇',
                2 => '🥈',
                3 => '🥉',
                default => "{$rank}.",
            };
            $excerpt = mb_substr(trim(preg_replace('/\s+/', ' ', $c->content)), 0, 120);
            if (mb_strlen($c->content) > 120) {
                $excerpt .= '...';
            }
            $result .= "{$medal} ❤️ {$c->likes_count} — **{$c->author_name}** bình luận trong bài *{$c->post_title}* ({$c->created_at->format('d/m/Y')}):\n";
            $result .= "   > {$excerpt}\n\n";
        }

        return $result;
    }
}
