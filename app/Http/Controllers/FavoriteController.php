<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * Toggle favorite status for the given post.
     */
    public function toggle(Request $request, Post $post): RedirectResponse
    {
        $user = $request->user();
        $nowFavorited = $user->toggleFavorite($post);
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

        $posts = $user->favorites()
            ->with('user')
            ->latest('favorites.created_at')
            ->paginate(10);

        return view('posts.favorites', compact('posts'));
    }
}
