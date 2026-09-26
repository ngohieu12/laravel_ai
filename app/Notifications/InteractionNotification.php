<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Base class for in-app interaction notifications: someone commented on,
 * replied to, favorited, pinned or liked the recipient's content.
 *
 * Delivered through the database channel only — the bell 🔔 on the navbar
 * and the notifications page render the stored payload.
 */
abstract class InteractionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly User $actor,
        public readonly Post $post,
        public readonly ?Comment $comment = null,
    ) {}

    /**
     * Delivery channels for this notification.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Machine-friendly interaction kind rendered as an icon in the UI.
     */
    abstract protected function kind(): string;

    /**
     * Human-readable message shown in the notifications list.
     */
    abstract protected function message(): string;

    /**
     * Payload stored in the notifications table.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind(),
            'message' => $this->message(),
            'actor_name' => $this->actor->name,
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'post_slug' => $this->post->slug,
            'comment_id' => $this->comment?->id,
            'url' => $this->targetUrl(),
        ];
    }

    /**
     * Where clicking the notification leads: the post, jumping to the
     * comment when one is attached.
     */
    private function targetUrl(): string
    {
        $url = route('posts.show', ['post' => $this->post->id]);

        return $this->comment !== null
            ? $url.'#comment-'.$this->comment->id
            : $url;
    }
}
