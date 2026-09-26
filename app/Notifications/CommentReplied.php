<?php

namespace App\Notifications;

/**
 * A commenter is notified when someone replies to their comment.
 */
class CommentReplied extends InteractionNotification
{
    protected function kind(): string
    {
        return 'reply';
    }

    protected function message(): string
    {
        return $this->actor->name.' đã trả lời bình luận của bạn trong bài viết "'.$this->post->title.'".';
    }
}
