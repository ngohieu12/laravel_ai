<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only tag management (quản lý tag).
 *
 * Tags are created implicitly by authors from the post form; here admins can
 * add tags up front, rename them (renaming onto an existing tag merges the
 * two), delete them and clean up tags no post uses anymore.
 */
class TagController extends Controller
{
    /**
     * List tags with their post counts (searchable, sortable, paginated).
     */
    public function index(Request $request): View
    {
        $query = Tag::query()->withCount('posts');

        $search = trim($request->string('search')->toString());

        if ($search !== '') {
            $query->search($search);
        }

        $sort = $request->input('sort', 'name');

        match ($sort) {
            'posts' => $query->orderByDesc('posts_count')->orderBy('name'),
            'newest' => $query->latest('id'),
            default => $query->orderBy('name'),
        };

        if (! in_array($sort, ['posts', 'newest'], true)) {
            $sort = 'name';
        }

        $tags = $query->paginate(20)->withQueryString();
        $totalTags = Tag::query()->count();
        $unusedTags = Tag::query()->doesntHave('posts')->count();

        return view('admin.tags.index', compact('tags', 'search', 'sort', 'totalTags', 'unusedTags'));
    }

    /**
     * Create one or more tags (comma separated).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Vui lòng nhập tên tag.',
            'name.max' => 'Danh sách tag tối đa 255 ký tự.',
        ]);

        $names = Tag::parseNames($validated['name']);

        foreach ($names as $name) {
            if (mb_strlen($name) > Tag::MAX_NAME_LENGTH) {
                return back()->withInput()->withErrors([
                    'name' => 'Mỗi tag tối đa '.Tag::MAX_NAME_LENGTH.' ký tự: "'.$name.'".',
                ]);
            }
        }

        if ($names === []) {
            return back()->withInput()->withErrors(['name' => 'Tên tag không hợp lệ.']);
        }

        $created = [];
        $existing = [];

        foreach ($names as $name) {
            $tag = Tag::findOrCreateByName($name);

            if ($tag?->wasRecentlyCreated) {
                $created[] = $tag->name;
            } elseif ($tag) {
                $existing[] = $tag->name;
            }
        }

        if ($created === []) {
            return back()->withInput()->withErrors([
                'name' => count($existing) === 1 ? 'Tag này đã tồn tại.' : 'Các tag này đều đã tồn tại.',
            ]);
        }

        $message = 'Đã thêm tag: '.implode(', ', $created).'.';

        if ($existing !== []) {
            $message .= ' Bỏ qua tag đã có: '.implode(', ', $existing).'.';
        }

        return redirect()->route('admin.tags.index')->with('success', $message);
    }

    /**
     * Rename a tag. Renaming onto the name of another tag merges them.
     */
    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'new_name' => ['required', 'string', 'max:'.Tag::MAX_NAME_LENGTH],
        ], [
            'new_name.required' => 'Vui lòng nhập tên tag mới.',
            'new_name.max' => 'Tên tag tối đa '.Tag::MAX_NAME_LENGTH.' ký tự.',
        ]);

        $newName = Tag::normalizeName($validated['new_name']);

        if (Tag::slugFor($newName) === '') {
            return back()->withErrors(['new_name' => 'Tên tag không hợp lệ.']);
        }

        $oldName = $tag->name;
        $survivor = $tag->renameTo($newName);

        $message = $survivor->is($tag)
            ? 'Đã đổi tag "'.$oldName.'" thành "'.$survivor->name.'".'
            : 'Đã gộp tag "'.$oldName.'" vào tag "'.$survivor->name.'".';

        return redirect()->route('admin.tags.index', $request->only(['search', 'sort', 'page']))
            ->with('success', $message);
    }

    /**
     * Delete a tag (detaches it from every post; the posts themselves stay).
     */
    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        $name = $tag->name;
        $postsCount = $tag->posts()->count();

        $tag->delete();

        $message = 'Đã xóa tag "'.$name.'"'
            .($postsCount > 0 ? ' (gỡ khỏi '.$postsCount.' bài viết).' : '.');

        return redirect()->route('admin.tags.index', $request->only(['search', 'sort']))
            ->with('success', $message);
    }

    /**
     * Delete every tag that no post uses.
     */
    public function destroyUnused(): RedirectResponse
    {
        $deleted = Tag::deleteUnused();

        return redirect()->route('admin.tags.index')->with(
            'success',
            $deleted > 0 ? "Đã dọn {$deleted} tag không dùng." : 'Không có tag nào cần dọn.',
        );
    }
}
