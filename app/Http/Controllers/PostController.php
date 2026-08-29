<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of posts.
     */
    public function index(Request $request)
    {
        $query = Post::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('is_published', $request->input('status') === 'published');
        }

        $posts = $query->latest()->paginate(10)->withQueryString();
        $categories = Post::distinct()->pluck('category');

        return view('posts.index', compact('posts', 'categories'));
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
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $validated['is_published'] = $request->boolean('is_published');
        $validated['user_id'] = auth()->id();

        Post::create($validated);

        return redirect()->route('posts.index')->with('success', 'Bài viết đã được tạo thành công!');
    }

    /**
     * Display the specified post.
     */
    public function show(Post $post)
    {
        return view('posts.show', compact('post'));
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
    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorizeEdit($post);

        $validated = $request->validated();
        $validated['is_published'] = $request->boolean('is_published');

        $post->update($validated);

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
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Bài viết đã được xóa thành công!');
    }
}
