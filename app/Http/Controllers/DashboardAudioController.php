<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\PostAudio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thư viện MP3 — a dedicated screen for the audio posts, kept apart from the
 * regular post list so the files can be reviewed, played and cleaned up in one
 * place.
 *
 * Admins see every uploaded file; creators only their own.
 */
class DashboardAudioController extends Controller
{
    /**
     * Files per page on the library screen.
     */
    private const PER_PAGE = 12;

    /**
     * List the audio posts (mp3 files) the current user manages.
     */
    public function index(Request $request, PostAudio $audios): View
    {
        $user = $request->user();

        $query = Post::query()
            ->withAudio()
            ->with(['user', 'tags'])
            ->withCount([
                'favoritedBy as favorites_count',
                'pinnedBy as saves_count',
            ]);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        if ($request->filled('status')) {
            $query->where('is_published', $request->input('status') === 'published');
        }

        $sort = $request->input('sort', 'newest');

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'title':
                $query->orderBy('title');
                break;
            case 'newest':
            default:
                $sort = 'newest';
                $query->latest();
                break;
        }

        $posts = $query->paginate(self::PER_PAGE)->withQueryString();

        $sizes = $posts->getCollection()
            ->mapWithKeys(fn (Post $post): array => [$post->id => $audios->humanSize($post->audio)]);

        $totalBytes = (int) $posts->getCollection()
            ->sum(fn (Post $post): int => $audios->size($post->audio) ?? 0);

        return view('dashboard.audio.index', [
            'posts' => $posts,
            'sort' => $sort,
            'sizes' => $sizes,
            'pageBytes' => PostAudio::formatBytes($totalBytes),
        ]);
    }

    /**
     * Remove the mp3 file of a post (the post itself is kept).
     */
    public function destroy(Post $post, PostAudio $audios): RedirectResponse
    {
        abort_unless($post->isManagedBy(auth()->user()), 403, 'Bạn chỉ có thể quản lý bài viết của mình.');

        if (! $post->isAudio() || $post->audio === null) {
            return redirect()->route('dashboard.audio.index')->with('success', 'Bài viết này không có file MP3 nào.');
        }

        $audios->delete($post->audio);

        // An audio post without a file is not a valid state: fall back to a
        // written post when there is no body to keep.
        $attributes = ['audio' => null, 'audio_title' => null];

        if (trim((string) $post->content) === '') {
            $attributes['content_type'] = Post::CONTENT_TYPE_TEXT;
        }

        $post->update($attributes);

        return redirect()->route('dashboard.audio.index')->with('success', "Đã xoá file MP3 của bài viết \"{$post->title}\".");
    }
}
