<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\PostEngagementTracker;
use App\Services\PostImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class PostController extends Controller
{
    /**
     * Display a listing of posts.
     */
    public function index(Request $request)
    {
        $query = Post::query()->withCount(['favoritedBy as favorites_count']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('is_published', $request->input('status') === 'published');
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
        $categories = Post::distinct()->pluck('category');

        $user = $request->user();
        $favoritedIds = $user
            ? $user->favorites()->pluck('posts.id')->toArray()
            : [];

        return view('posts.index', compact('posts', 'categories', 'favoritedIds', 'sort'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        return view('posts.create');
    }

    /**
     * Store a newly created post.
     */
    public function store(StorePostRequest $request, PostImage $images)
    {
        $validated = $request->validated();
        $validated['is_published'] = $request->boolean('is_published');
        $validated['user_id'] = auth()->id();

        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $validated['image'] = $images->store($image);
        }

        $post = Post::create($validated);

        return redirect()->route('posts.show', $post)->with('success', 'Bài viết đã được tạo thành công!');
    }

    /**
     * Display the specified post.
     */
    public function show(Request $request, Post $post, PostEngagementTracker $engagement)
    {
        $user = $request->user();

        // Count this visit (deduplicated per visitor) before rendering the numbers.
        if ($engagement->trackView($request, $post, $user)) {
            $post->views_count = (int) $post->views_count + 1;
        }

        $post->favorites_count = $post->favoritedBy()->count();
        $isFavorited = $user ? $user->hasFavorited($post) : false;

        $comments = Comment::loadForPost($post, $user);
        $commentsCount = $post->comments()->count();
        $engagementScore = $post->engagementScore();

        return view('posts.show', compact('post', 'isFavorited', 'comments', 'commentsCount', 'engagementScore'));
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Post $post)
    {
        $this->authorizeEdit($post);

        return view('posts.edit', compact('post'));
    }

    /**
     * Update the specified post.
     */
    public function update(UpdatePostRequest $request, Post $post, PostImage $images)
    {
        $this->authorizeEdit($post);

        $validated = $request->validated();
        $validated['is_published'] = $request->boolean('is_published');

        $previousImage = $post->image;
        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $validated['image'] = $images->store($image);
        } elseif ($request->boolean('remove_image')) {
            $validated['image'] = null;
            $validated['image_alt'] = null;
        }

        $post->update($validated);

        // The old file is garbage once it has been replaced or removed.
        if ($post->image !== $previousImage) {
            $images->delete($previousImage);
        }

        return redirect()->route('posts.show', $post)->with('success', 'Bài viết đã được cập nhật thành công!');
    }

    /**
     * Check if current user can edit this post.
     */
    private function authorizeEdit(Post $post): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($post->user_id !== $user->id) {
            abort(403, 'Bạn chỉ có thể chỉnh sửa bài viết của mình.');
        }
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Post $post, PostImage $images)
    {
        $images->forget($post);

        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Bài viết đã được xóa thành công!');
    }
}
