<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\UserMentioned;
use App\Services\PostAudio;
use App\Services\PostImage;
use App\Support\Mentions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

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

        $query->ofType($request->input('type'));

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
    public function create(Request $request)
    {
        $categories = Category::query()->ordered()->pluck('name');
        $tagSuggestions = Tag::query()->ordered()->pluck('name');
        $series = $this->seriesOptions();

        // Bài video / audio mặc định đã nằm sẵn trong danh mục Video / MP3.
        $defaultCategory = Category::defaultFor($request->input('content_type')) ?? 'general';

        return view('dashboard.posts.create', compact('categories', 'tagSuggestions', 'series', 'defaultCategory'));
    }

    /**
     * Store a newly created post.
     */
    public function store(StorePostRequest $request, PostImage $images, PostAudio $audios)
    {
        $validated = collect($request->validated())->except('tags')->all();
        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_long_form'] = $request->boolean('is_long_form');
        $validated['user_id'] = auth()->id();
        $validated = $this->normalizeContentAttributes($validated);

        if (trim((string) ($validated['category'] ?? '')) === '') {
            $validated['category'] = Category::defaultFor($validated['content_type'] ?? null) ?? 'general';
        }

        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $validated['image'] = $images->store($image);
        }

        $audio = $request->file('audio');

        if ($audio instanceof UploadedFile) {
            $validated['audio'] = $audios->store($audio);
        }

        $post = Post::create($validated);
        $post->syncTags($request->input('tags'));

        $this->notifyMentionedUsers($post);

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
        $series = $this->seriesOptions();
        $post->load('tags');

        return view('dashboard.posts.edit', compact('post', 'categories', 'tagSuggestions', 'series'));
    }

    /**
     * Series offered by the post form, newest first.
     *
     * @return Collection<int, Series>
     */
    private function seriesOptions()
    {
        return Series::query()->withCount('posts')->orderByDesc('updated_at')->get();
    }

    /**
     * Update the specified post.
     */
    public function update(UpdatePostRequest $request, Post $post, PostImage $images, PostAudio $audios)
    {
        $this->authorizeEdit($post);

        $validated = collect($request->validated())->except('tags')->all();
        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_long_form'] = $request->boolean('is_long_form');
        $validated = $this->normalizeContentAttributes($validated);

        if (trim((string) ($validated['category'] ?? '')) === '') {
            $validated['category'] = Category::defaultFor($validated['content_type'] ?? null) ?? 'general';
        }

        $previousImage = $post->image;
        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $validated['image'] = $images->store($image);
        } elseif ($request->boolean('remove_image')) {
            $validated['image'] = null;
            $validated['image_alt'] = null;
        }

        $previousAudio = $post->audio;
        $audio = $request->file('audio');

        if ($audio instanceof UploadedFile) {
            $validated['audio'] = $audios->store($audio);
        } elseif ($request->boolean('remove_audio')) {
            $validated['audio'] = null;
            $validated['audio_title'] = null;
        }

        // Only mentions that are not already in the published body are worth
        // a new notification, so re-saving a post stays quiet.
        $previousMentions = $post->is_published
            ? Mentions::usernames((string) $post->content)
            : [];

        $post->update($validated);

        // Only touch tags when the form actually sent the field.
        if ($request->has('tags')) {
            $post->syncTags($request->input('tags'));
        }

        // The old file is garbage once it has been replaced or removed.
        if ($post->image !== $previousImage) {
            $images->delete($previousImage);
        }

        if ($post->audio !== $previousAudio) {
            $audios->delete($previousAudio);
        }

        $this->notifyMentionedUsers($post, $previousMentions);

        return redirect()->route('dashboard.posts.index')->with('success', 'Bài viết đã được cập nhật thành công!');
    }

    /**
     * Remove the specified post (admins: any post, creators: their own).
     */
    public function destroy(Post $post, PostImage $images, PostAudio $audios)
    {
        $this->authorizeEdit($post);

        $images->forget($post);
        $audios->forget($post);

        $post->delete();

        return redirect()->route('dashboard.posts.index')->with('success', 'Bài viết đã được xóa thành công!');
    }

    /**
     * Notify every account @mentioned in the post body. Only published posts
     * notify, and handles listed in $skip are ignored (already mentioned in a
     * previous, published version of the same post).
     *
     * @param  list<string>  $skip
     */
    private function notifyMentionedUsers(Post $post, array $skip = []): void
    {
        if (! $post->is_published) {
            return;
        }

        $author = $post->user ?? auth()->user();

        Mentions::users((string) $post->content)
            ->reject(fn (User $user): bool => $user->is($author) || in_array($user->username, $skip, true))
            ->each(fn (User $user) => $user->notify(new UserMentioned($author, $post)));
    }

    /**
     * Check if current user can manage (edit / delete) this post.
     */
    private function authorizeEdit(Post $post): void
    {
        abort_unless($post->isManagedBy(auth()->user()), 403, 'Bạn chỉ có thể quản lý bài viết của mình.');
    }

    /**
     * Normalize content type / video / series fields coming from the form, so
     * text posts never keep a stale video URL and non-series posts never keep
     * a stale part number.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeContentAttributes(array $validated): array
    {
        $contentType = $validated['content_type'] ?? Post::CONTENT_TYPE_TEXT;
        $validated['content_type'] = in_array($contentType, Post::CONTENT_TYPES, true)
            ? $contentType
            : Post::CONTENT_TYPE_TEXT;

        $validated['video_url'] = $validated['content_type'] === Post::CONTENT_TYPE_VIDEO
            ? trim((string) ($validated['video_url'] ?? ''))
            : null;

        // Video and audio posts may skip the written body (it becomes a short
        // description), and a post that is not an audio post never keeps an
        // audio file around.
        $validated['content'] = (string) ($validated['content'] ?? '');

        if ($validated['content_type'] !== Post::CONTENT_TYPE_AUDIO) {
            $validated['audio'] = null;
            $validated['audio_title'] = null;
        } else {
            $validated['audio_title'] = trim((string) ($validated['audio_title'] ?? '')) ?: null;
        }

        $validated['series_id'] = isset($validated['series_id']) && (int) $validated['series_id'] > 0
            ? (int) $validated['series_id']
            : null;

        $validated['series_part'] = $validated['series_id'] !== null && isset($validated['series_part'])
            ? (int) $validated['series_part']
            : null;

        return $validated;
    }
}
