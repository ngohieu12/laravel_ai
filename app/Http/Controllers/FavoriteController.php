<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Notifications\PostFavorited;
use App\Services\PostEngagementTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * Toggle favorite status for the given post.
     */
    public function toggle(Request $request, Post $post, PostEngagementTracker $engagement): RedirectResponse
    {
        $user = $request->user();
        $nowFavorited = $user->toggleFavorite($post);
        $engagement->trackFavorite($request, $post, $nowFavorited, $user);

        if ($nowFavorited) {
            $author = $post->user;

            if ($author !== null && ! $author->is($user)) {
                $author->notify(new PostFavorited($user, $post));
            }
        }
        $status = $nowFavorited ? 'added' : 'removed';
        $message = $nowFavorited
            ? 'Đã thêm bài viết vào danh sách yêu thích.'
            : 'Đã bỏ yêu thích bài viết.';

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $status,
                'favorited' => $nowFavorited,
                'favorites_count' => $post->favoritedBy()->count(),
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * List authenticated user's favorite posts.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $user->favorites()
            ->with('user')
            ->withCount('favoritedBy as favorites_count');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Sort options: favorited_at (default), or favorites_count.
        $sort = $request->input('sort', 'recent');
        switch ($sort) {
            case 'favorites':
                $query->orderByDesc('favorites_count')->latest('favorites.created_at');
                break;
            case 'recent':
            default:
                $query->latest('favorites.created_at');
                $sort = 'recent';
                break;
        }

        $posts = $query->paginate(10)->withQueryString();

        // Categories that exist among the user's favorites, for the chip filter.
        $categories = $user->favorites()
            ->distinct()
            ->pluck('category')
            ->filter();

        return view('posts.favorites', compact('posts', 'categories', 'sort'));
    }
}
