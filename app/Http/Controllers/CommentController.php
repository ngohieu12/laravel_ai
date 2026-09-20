<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\PostEngagementTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a new comment or reply.
     */
    public function store(StoreCommentRequest $request, Post $post, PostEngagementTracker $engagement): RedirectResponse
    {
        $validated = $request->validated();

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
        ]);

        $engagement->trackComment($request, $post, $request->user());

        return redirect()
            ->route('posts.show', ['post' => $post])
            ->withFragment('comment-' . $comment->id)
            ->with('success', 'Bình luận của bạn đã được đăng.');
    }

    /**
     * Toggle favorite/like on a comment.
     */
    public function toggleFavorite(Request $request, Post $post, Comment $comment): RedirectResponse
    {
        // Ensure the comment belongs to the post.
        abort_if($comment->post_id !== $post->id, 404);

        $nowFavorited = $request->user()->toggleCommentFavorite($comment);

        $message = $nowFavorited
            ? 'Đã thích bình luận.'
            : 'Đã bỏ thích bình luận.';

        return back()->with('success', $message);
    }
}
