<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\SearchLog;
use App\Services\PostEngagementTracker;
use Illuminate\Http\Request;

/**
 * Public (frontend) reading surface: the post listing and post detail.
 *
 * This controller never exposes edit / delete links or forms — managing posts
 * lives behind the dashboard (see DashboardPostController).
 */
class PostController extends Controller
{
    /**
     * Display the public listing of published posts.
     */
    public function index(Request $request)
    {
        $query = Post::query()
            ->published()
            ->withCount([
                'favoritedBy as favorites_count',
                'pinnedBy as saves_count',
            ]);

        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'favorites':
                $query->orderByDesc('favorites_count')->orderByDesc('created_at');
                break;
            case 'views':
                $query->orderByDesc('views_count')->orderByDesc('created_at');
                break;
            case 'shares':
                $query->orderByDesc('shares_count')->orderByDesc('created_at');
                break;
            case 'saves':
                $query->orderByDesc('saves_count')->orderByDesc('created_at');
                break;
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'newest':
            default:
                $query->latest();
                $sort = 'newest';
                break;
        }

        $posts = $query->paginate(10)->withQueryString();
        $categories = Category::query()->ordered()->pluck('name');

        // Grid / list viewing mode of the public listing.
        $view = $request->input('view') === 'grid' ? 'grid' : 'list';

        if ($request->filled('search')) {
            SearchLog::record($request->string('search')->toString(), $request->user()?->id, $posts->total());
        }

        $user = $request->user();
        $favoritedIds = $user ? $user->favorites()->pluck('posts.id')->all() : [];
        $pinnedIds = $user ? $user->pinnedPosts()->pluck('posts.id')->all() : [];

        return view('posts.index', compact('posts', 'categories', 'favoritedIds', 'pinnedIds', 'sort', 'view'));
    }

    /**
     * Display the specified post (published posts for everyone; drafts only
     * for their author and admins).
     */
    public function show(Request $request, Post $post, PostEngagementTracker $engagement)
    {
        $user = $request->user();

        if (! $post->is_published && ! ($user && $post->isManagedBy($user))) {
            abort(404);
        }

        // Count this visit (deduplicated per visitor) before rendering the numbers.
        if ($engagement->trackView($request, $post, $user)) {
            $post->views_count = (int) $post->views_count + 1;
        }

        $post->favorites_count = $post->favoritedBy()->count();
        $post->saves_count = $post->savesCount();
        $isFavorited = $user ? $user->hasFavorited($post) : false;
        $isPinned = $user ? $user->hasPinned($post) : false;

        $comments = Comment::loadForPost($post, $user);
        $commentsCount = $post->comments()->count();
        $engagementScore = $post->engagementScore();

        return view('posts.show', compact('post', 'isFavorited', 'isPinned', 'comments', 'commentsCount', 'engagementScore'));
    }
}
