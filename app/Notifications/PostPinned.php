<?php

namespace App\Notifications;

/**
 * The post author is notified when someone pins (ghim) their post.
 */
class PostPinned extends InteractionNotification
{
    protected function kind(): string
    {
        return 'pin';
    }

    protected function message(): string
    {
        return $this->actor->name.' đã ghim bài viết "'.$this->post->title.'" của bạn.';
    }
}
