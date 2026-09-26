<?php

namespace App\Notifications;

/**
 * A commenter is notified when someone likes (thích) their comment.
 */
class CommentFavorited extends InteractionNotification
{
    protected function kind(): string
    {
        return 'comment_favorite';
    }

    protected function message(): string
    {
        return $this->actor->name.' đã thích bình luận của bạn trong bài viết "'.$this->post->title.'".';
    }
}
