<?php

namespace App\Http\Controllers;

use App\Models\Series;
use App\Services\PostImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Series management for creators and admins.
 *
 * Series are referenced by id from the post form, so this screen is where a
 * series is actually created, named, illustrated and retired.
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

    public function store(Request $request, PostImage $images): RedirectResponse
    {
        $validated = $this->validateSeries($request);

        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $validated['image'] = $images->storeSeries($image);
        }

        Series::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', "Đã tạo chuỗi bài viết \"{$validated['title']}\".");
    }

    public function update(Request $request, Series $series, PostImage $images): RedirectResponse
    {
        $validated = $this->validateSeries($request, $series);

        $previousImage = $series->image;
        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            $validated['image'] = $images->storeSeries($image);
        } elseif ($request->boolean('remove_image')) {
            $validated['image'] = null;
            $validated['image_alt'] = null;
        }

        $series->update($validated);

        // The old file is garbage once it has been replaced or removed.
        if ($series->image !== $previousImage) {
            $images->delete($previousImage);
        }

        return back()->with('success', 'Đã cập nhật chuỗi bài viết.');
    }

    /**
     * Delete a series. Its posts are kept and simply leave the series, because
     * the series link is a null-on-delete foreign key. The cover image goes
     * with it, since nothing else references that file.
     */
    public function destroy(Series $series, PostImage $images): RedirectResponse
    {
        $images->forgetSeries($series);

        $series->delete();

        return back()->with('success', 'Đã xoá chuỗi bài viết. Các bài viết vẫn được giữ nguyên.');
    }

    /**
     * Validate the series form, which creates and updates share.
     *
     * @return array<string, mixed>
     */
    private function validateSeries(Request $request, ?Series $series = null): array
    {
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:150',
                Rule::unique('series', 'title')->ignore($series?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image_alt' => ['nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'Vui lòng nhập tên chuỗi bài viết.',
            'title.unique' => 'Chuỗi bài viết này đã tồn tại.',
            'image.image' => 'Tệp tải lên phải là ảnh.',
            'image.mimes' => 'Ảnh đại diện phải có định dạng JPG, PNG, WEBP hoặc GIF.',
            'image.max' => 'Kích thước ảnh đại diện tối đa là 4MB.',
            'image_alt.max' => 'Mô tả ảnh tối đa 255 ký tự.',
        ]);

        // The alt text is optional on the form, so normalize an empty value to
        // null instead of storing an empty string. A field the form did not
        // send at all is left out entirely, so updating a series without the
        // alt box keeps the description already on file.
        if (array_key_exists('image_alt', $validated)) {
            $validated['image_alt'] = trim((string) $validated['image_alt']) ?: null;
        }

        return $validated;
    }
}
