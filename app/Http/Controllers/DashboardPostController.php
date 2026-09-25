<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Backend (dashboard) post management for admins and creators.
 *
 * Admins manage every post; creators have full control over their own posts.
 * Everything renders inside the dashboard layout — the public listing never
 * links here.
 */
class DashboardPostController extends Controller
{
    /**
     * List the posts the current user manages.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Post::query()
            ->with(['user', 'tags'])
            ->withCount([
                'favoritedBy as favorites_count',
                'pinnedBy as saves_count',
            ]);

        // Creators manage their own posts only; admins see everything.
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('tag')) {
            $query->withTag($request->string('tag')->toString());
        }

        if ($request->filled('status')) {
            $query->where('is_published', $request->input('status') === 'published');
        }

        if ($request->filled('author') && $user->isAdmin()) {
            $query->where('user_id', (int) $request->input('author'));
        }

        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'title':
                $query->orderBy('title');
                break;
            case 'views':
                $query->orderByDesc('views_count');
                break;
            case 'newest':
            default:
                $query->latest();
                $sort = 'newest';
                break;
        }

        $posts = $query->paginate(10)->withQueryString();
        $categories = Category::query()->ordered()->pluck('name');
        $authors = $user->isAdmin()
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $activeTag = $request->filled('tag')
            ? Tag::query()->where('slug', $request->string('tag')->toString())->first()
            : null;

        return view('dashboard.posts.index', compact('posts', 'categories', 'authors', 'sort', 'activeTag'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        $categories = Category::query()->ordered()->pluck('name');
        $tagSuggestions = Tag::query()->ordered()->pluck('name');

        return view('dashboard.posts.create', compact('categories', 'tagSuggestions'));
    }

    /**
     * Store a newly created post.
     */
    public function store(StorePostRequest $request, PostImage $images)
    {
        $validated = collect($request->validated())->except('tags')->all();
        $validated['is_published'] = $request->boolean('is_published');
        $validated['user_id'] = auth()->id();

        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $validated['image'] = $images->store($image);
        }

        $post = Post::create($validated);
        $post->syncTags($request->input('tags'));

        return redirect()->route('dashboard.posts.index')->with('success', 'Bài viết đã được tạo thành công!');
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Post $post)
    {
        $this->authorizeEdit($post);

        $categories = Category::query()->ordered()->pluck('name');
        $tagSuggestions = Tag::query()->ordered()->pluck('name');
        $post->load('tags');

        return view('dashboard.posts.edit', compact('post', 'categories', 'tagSuggestions'));
    }

    /**
     * Update the specified post.
     */
    public function update(UpdatePostRequest $request, Post $post, PostImage $images)
    {
        $this->authorizeEdit($post);

        $validated = collect($request->validated())->except('tags')->all();
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

        // Only touch tags when the form actually sent the field.
        if ($request->has('tags')) {
            $post->syncTags($request->input('tags'));
        }

        // The old file is garbage once it has been replaced or removed.
        if ($post->image !== $previousImage) {
            $images->delete($previousImage);
        }

        return redirect()->route('dashboard.posts.index')->with('success', 'Bài viết đã được cập nhật thành công!');
    }

    /**
     * Remove the specified post (admins: any post, creators: their own).
     */
    public function destroy(Post $post, PostImage $images)
    {
        $this->authorizeEdit($post);

        $images->forget($post);

        $post->delete();

        return redirect()->route('dashboard.posts.index')->with('success', 'Bài viết đã được xóa thành công!');
    }

    /**
     * Check if current user can manage (edit / delete) this post.
     */
    private function authorizeEdit(Post $post): void
    {
        abort_unless($post->isManagedBy(auth()->user()), 403, 'Bạn chỉ có thể quản lý bài viết của mình.');
    }
}
