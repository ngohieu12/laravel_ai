<?php

namespace App\Notifications;

/**
 * The post author is notified when someone comments on their post.
 */
class PostCommented extends InteractionNotification
{
    protected function kind(): string
    {
        return 'comment';
    }

    protected function message(): string
    {
        return $this->actor->name.' đã bình luận về bài viết "'.$this->post->title.'" của bạn.';
    }
}
