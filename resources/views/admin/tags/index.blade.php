@extends('layouts.dashboard')

@section('title', 'Quản lý tag')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🔖 Quản lý tag</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ number_format($totalTags) }} tag · {{ number_format($unusedTags) }} tag chưa gắn bài viết nào
            </p>
        </div>
        @if($unusedTags > 0)
            <form action="{{ route('admin.tags.destroy-unused') }}" method="POST"
                onsubmit="return confirm('Xóa {{ $unusedTags }} tag không được bài viết nào sử dụng?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm font-medium">
                    🧹 Dọn {{ $unusedTags }} tag không dùng
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Thêm tag mới</h2>
        <form action="{{ route('admin.tags.store') }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                class="flex-1 border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400"
                placeholder="VD: laravel, php, trí tuệ nhân tạo (nhiều tag cách nhau bằng dấu phẩy)">
            <button type="submit" class="px-5 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition font-medium">➕ Thêm</button>
        </form>
        @error('name')
            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-500 mt-2">
            Tag không phân biệt hoa/thường và dấu: "Trí tuệ nhân tạo" và "tri tue nhan tao" là cùng một tag.
            Đổi tên một tag thành tên của tag khác sẽ gộp hai tag lại.
        </p>
    </div>

    <form method="GET" action="{{ route('admin.tags.index') }}" class="bg-white rounded-xl shadow-sm p-4 border flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[220px]">
            <label for="tag-search" class="block text-xs font-medium text-gray-500 mb-1">Tìm tag</label>
            <input type="text" id="tag-search" name="search" value="{{ $search }}"
                class="w-full border-gray-300 rounded-lg px-3 py-2 border text-sm focus:ring-2 focus:ring-slate-400"
                placeholder="Nhập tên tag...">
        </div>
        <div>
            <label for="tag-sort" class="block text-xs font-medium text-gray-500 mb-1">Sắp xếp</label>
            <select name="sort" id="tag-sort" class="border-gray-300 rounded-lg px-3 py-2 border text-sm" onchange="this.form.submit()">
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>🔤 Tên A → Z</option>
                <option value="posts" {{ $sort === 'posts' ? 'selected' : '' }}>📚 Nhiều bài viết nhất</option>
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>🕒 Mới tạo</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg text-sm">Lọc</button>
        @if($search !== '' || $sort !== 'name')
            <a href="{{ route('admin.tags.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Xóa lọc</a>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Tag</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Số bài viết</th>
                    <th class="px-4 py-3">Đổi tên / gộp</th>
                    <th class="px-4 py-3">Xóa</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($tags as $tag)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">
                            <a href="{{ route('posts.index', ['tag' => $tag->slug]) }}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                                #{{ $tag->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $tag->slug }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($tag->posts_count > 0)
                                <a href="{{ route('dashboard.posts.index', ['tag' => $tag->slug]) }}" class="hover:underline" title="Xem các bài viết gắn tag này">
                                    {{ number_format($tag->posts_count) }}
                                </a>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <form action="{{ route('admin.tags.update', ['tag' => $tag, 'search' => $search ?: null, 'sort' => $sort !== 'name' ? $sort : null, 'page' => $tags->currentPage() > 1 ? $tags->currentPage() : null]) }}" method="POST" class="flex gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="new_name" required maxlength="{{ \App\Models\Tag::MAX_NAME_LENGTH }}"
                                    class="border-gray-300 rounded-lg px-3 py-1.5 border text-sm w-40" placeholder="{{ $tag->name }}">
                                <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Đổi tên</button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <form action="{{ route('admin.tags.destroy', ['tag' => $tag, 'search' => $search ?: null, 'sort' => $sort !== 'name' ? $sort : null]) }}" method="POST"
                                onsubmit="return confirm('{{ $tag->posts_count > 0 ? 'Tag này đang gắn với '.$tag->posts_count.' bài viết. Vẫn xóa?' : 'Xóa tag này?' }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            {{ $search !== '' ? 'Không tìm thấy tag phù hợp.' : 'Chưa có tag nào.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $tags->links() }}
    </div>
</div>
@endsection
