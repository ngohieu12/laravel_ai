<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CommentFavorited;
use App\Notifications\CommentReplied;
use App\Notifications\PostCommented;
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

        $this->notifyAboutNewComment($post, $comment, $request->user());

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

        $actor = $request->user();
        $nowFavorited = $actor->toggleCommentFavorite($comment);

        if ($nowFavorited) {
            $commentAuthor = $comment->user;

            if ($commentAuthor !== null && ! $commentAuthor->is($actor)) {
                $commentAuthor->notify(new CommentFavorited($actor, $post, $comment));
            }
        }

        $message = $nowFavorited
            ? 'Đã thích bình luận.'
            : 'Đã bỏ thích bình luận.';

        return back()->with('success', $message);
    }

    /**
     * Notify the interested party about a new comment: the post author for
     * top-level comments, the parent comment's author for replies. Nobody is
     * notified about their own actions.
     */
    private function notifyAboutNewComment(Post $post, Comment $comment, User $actor): void
    {
        $parent = $comment->parent;

        if ($parent !== null) {
            $parentAuthor = $parent->user;

            if ($parentAuthor !== null && ! $parentAuthor->is($actor)) {
                $parentAuthor->notify(new CommentReplied($actor, $post, $comment));
            }

            return;
        }

        $postAuthor = $post->user;

        if ($postAuthor !== null && ! $postAuthor->is($actor)) {
            $postAuthor->notify(new PostCommented($actor, $post, $comment));
        }
    }
}
