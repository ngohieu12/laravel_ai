<?php

namespace App\Notifications;

/**
 * A user is notified when their @mention handle appears in a comment or in a
 * post body.
 */
class UserMentioned extends InteractionNotification
{
    protected function kind(): string
    {
        return 'mention';
    }

    protected function message(): string
    {
        $where = $this->comment !== null
            ? 'trong bình luận ở bài viết'
            : 'trong bài viết';

        return $this->actor->name.' đã nhắc tên bạn '.$where.' "'.$this->post->title.'".';
    }
}
