<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * "Ghim bài" (pin / save for later) — available to every authenticated user.
 */
class PinController extends Controller
{
    /**
     * Toggle pinned status for the given post.
     */
    public function toggle(Request $request, Post $post): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $nowPinned = $user->togglePin($post);
        $status = $nowPinned ? 'added' : 'removed';
        $message = $nowPinned
            ? 'Đã ghim bài viết để đọc sau.'
            : 'Đã bỏ ghim bài viết.';

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $status,
                'pinned' => $nowPinned,
                'saves_count' => $post->savesCount(),
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * List the authenticated user's pinned posts.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $user->pinnedPosts()
            ->with('user')
            ->withCount(['favoritedBy as favorites_count', 'pinnedBy as saves_count']);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $sort = $request->input('sort', 'recent');
        switch ($sort) {
            case 'saves':
                $query->orderByDesc('saves_count')->latest('post_pins.created_at');
                break;
            case 'recent':
            default:
                $query->latest('post_pins.created_at');
                $sort = 'recent';
                break;
        }

        $posts = $query->paginate(10)->withQueryString();

        $categories = $user->pinnedPosts()
            ->distinct()
            ->pluck('category')
            ->filter();

        return view('posts.pins', compact('posts', 'categories', 'sort'));
    }
}
