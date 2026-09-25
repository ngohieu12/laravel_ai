<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only category management (quản lý danh mục).
 */
class CategoryController extends Controller
{
    /**
     * List all registered categories with their post counts.
     */
    public function index(): View
    {
        $counts = Category::counts();

        $categories = Category::query()
            ->ordered()
            ->get()
            ->map(fn (Category $category): array => [
                'name' => $category->name,
                'posts_count' => (int) ($counts[$category->name] ?? 0),
            ])
            ->all();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Register a new category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.unique' => 'Danh mục này đã tồn tại.',
            'name.max' => 'Tên danh mục tối đa 100 ký tự.',
        ]);

        Category::register($validated['name']);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã thêm danh mục "'.trim($validated['name']).'".');
    }

    /**
     * Rename a category (cascades to every post that uses it; merges into an
     * existing target when the new name is already taken).
     */
    public function update(Request $request, string $category): RedirectResponse
    {
        $validated = $request->validate([
            'new_name' => ['required', 'string', 'max:100'],
        ], [
            'new_name.required' => 'Vui lòng nhập tên danh mục.',
            'new_name.max' => 'Tên danh mục tối đa 100 ký tự.',
        ]);

        $model = Category::query()->where('name', $category)->first();

        if (! $model) {
            return redirect()
                ->route('admin.categories.index')
                ->withErrors(['new_name' => 'Danh mục "'.$category.'" không tồn tại.']);
        }

        $newName = trim($validated['new_name']);
        $oldName = $model->name;

        $model->rename($newName);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã đổi danh mục "'.$oldName.'" thành "'.$newName.'".');
    }

    /**
     * Delete a category. Posts that still use it must be moved to another
     * category first via the optional `move_to` field.
     */
    public function destroy(Request $request, string $category): RedirectResponse
    {
        $model = Category::query()->where('name', $category)->first();

        if (! $model) {
            return redirect()
                ->route('admin.categories.index')
                ->withErrors(['move_to' => 'Danh mục "'.$category.'" không tồn tại.']);
        }

        $postCount = Post::query()->where('category', $model->name)->count();

        if ($postCount > 0) {
            $moveTo = is_string($request->input('move_to')) ? trim($request->input('move_to')) : '';

            if ($moveTo === '' || $moveTo === $model->name) {
                return redirect()
                    ->route('admin.categories.index')
                    ->withErrors([
                        'move_to' => "Danh mục còn {$postCount} bài viết. Chọn danh mục để chuyển chúng sang trước khi xóa.",
                    ]);
            }

            $target = Category::query()->where('name', $moveTo)->first();

            if (! $target) {
                return redirect()
                    ->route('admin.categories.index')
                    ->withErrors(['move_to' => 'Danh mục chuyển sang không tồn tại.']);
            }

            Post::query()->where('category', $model->name)->update(['category' => $target->name]);
        }

        $model->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã xóa danh mục "'.$category.'".');
    }
}
