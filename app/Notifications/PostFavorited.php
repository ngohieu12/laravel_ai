<?php

namespace App\Notifications;

/**
 * The post author is notified when someone favorites (yêu thích) their post.
 */
class PostFavorited extends InteractionNotification
{
    protected function kind(): string
    {
        return 'favorite';
    }

    protected function message(): string
    {
        return $this->actor->name.' đã yêu thích bài viết "'.$this->post->title.'" của bạn.';
    }
}
