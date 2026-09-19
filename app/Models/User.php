<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_CREATOR = 'creator';

    public const ROLE_USER = 'user';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_CREATOR, self::ROLE_USER];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Posts favorited by this user.
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'favorites', 'user_id', 'post_id')
            ->withTimestamps();
    }

    /**
     * Comments written by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Comments favorited/liked by this user.
     */
    public function commentFavorites(): BelongsToMany
    {
        return $this->belongsToMany(Comment::class, 'comment_favorites', 'user_id', 'comment_id')
            ->withTimestamps();
    }

    /**
     * Check if user has favorited the given post.
     */
    public function hasFavorited(Post $post): bool
    {
        return $this->favorites()->where('post_id', $post->id)->exists();
    }

    /**
     * Check if user has favorited the given comment.
     */
    public function hasFavoritedComment(Comment $comment): bool
    {
        return $this->commentFavorites()->where('comment_id', $comment->id)->exists();
    }

    /**
     * Toggle favorite status for a comment.
     * Returns true if now favorited, false if unfavorited.
     */
    public function toggleCommentFavorite(Comment $comment): bool
    {
        if ($this->hasFavoritedComment($comment)) {
            $this->commentFavorites()->detach($comment->id);

            return false;
        }

        $this->commentFavorites()->syncWithoutDetaching([$comment->id]);

        return true;
    }

    /**
     * Toggle favorite status for a post.
     * Returns true if now favorited, false if unfavorited.
     */
    public function toggleFavorite(Post $post): bool
    {
        if ($this->hasFavorited($post)) {
            $this->favorites()->detach($post->id);

            return false;
        }

        $this->favorites()->syncWithoutDetaching([$post->id]);

        return true;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCreator(): bool
    {
        return $this->role === self::ROLE_CREATOR;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Check if user has one of the given roles.
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
