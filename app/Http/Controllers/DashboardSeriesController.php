<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Series management for creators and admins.
 *
 * Series are referenced by id from the post form, so this screen is where a
 * series is actually created, named and retired.
 */
class DashboardSeriesController extends Controller
{
    public function index(): View
    {
        $series = Series::query()
            ->withCount('posts')
            ->withCount(['posts as published_parts_count' => fn ($query) => $query->published()])
            ->withCount(['posts as long_form_parts_count' => fn ($query) => $query->where('is_long_form', true)])
            ->latest('updated_at')
            ->get();

        return view('dashboard/series/index', compact('series'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150', 'unique:series,title'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'title.required' => 'Vui lòng nhập tên chuỗi bài viết.',
            'title.unique' => 'Chuỗi bài viết này đã tồn tại.',
        ]);

        Series::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', "Đã tạo chuỗi bài viết \"{$validated['title']}\".");
    }

    public function update(Request $request, Series $series): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150', 'unique:series,title,'.$series->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'title.required' => 'Vui lòng nhập tên chuỗi bài viết.',
            'title.unique' => 'Chuỗi bài viết này đã tồn tại.',
        ]);

        $series->update($validated);

        return back()->with('success', 'Đã cập nhật chuỗi bài viết.');
    }

    /**
     * Delete a series. Its posts are kept and simply leave the series, because
     * the series link is a null-on-delete foreign key.
     */
    public function destroy(Series $series): RedirectResponse
    {
        $series->delete();

        return back()->with('success', 'Đã xoá chuỗi bài viết. Các bài viết vẫn được giữ nguyên.');
    }
}
