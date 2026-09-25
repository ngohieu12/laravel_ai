@extends('layouts.dashboard')

@section('title', 'Quản lý danh mục')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">🏷️ Quản lý danh mục</h1>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Thêm danh mục mới</h2>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="80"
                class="flex-1 border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400"
                placeholder="VD: cong-nghe, doi-song...">
            <button type="submit" class="px-5 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition font-medium">➕ Thêm</button>
        </form>
        @error('name')
            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Tên danh mục</th>
                    <th class="px-4 py-3">Số bài viết</th>
                    <th class="px-4 py-3">Đổi tên</th>
                    <th class="px-4 py-3">Xóa</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($categories as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">
                            <a href="{{ route('posts.index', ['category' => $row['name']]) }}" class="hover:text-slate-600 hover:underline">{{ $row['name'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $row['posts_count'] }}</td>
                        <td class="px-4 py-3">
                            <form action="{{ route('admin.categories.update', $row['name']) }}" method="POST" class="flex gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="new_name" required maxlength="80"
                                    class="border-gray-300 rounded-lg px-3 py-1.5 border text-sm w-40" placeholder="{{ $row['name'] }}">
                                <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Đổi tên</button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <form action="{{ route('admin.categories.destroy', $row['name']) }}" method="POST" class="flex gap-2 items-center"
                                onsubmit="return confirm('Xóa danh mục này?')">
                                @csrf
                                @method('DELETE')
                                @if($row['posts_count'] > 0)
                                    <select name="move_to" required class="border-gray-300 rounded-lg px-2 py-1.5 border text-sm w-36">
                                        <option value="">— Chuyển bài về —</option>
                                        @foreach($categories as $other)
                                            @if($other['name'] !== $row['name'])
                                                <option value="{{ $other['name'] }}">{{ $other['name'] }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                @endif
                                <button type="submit" class="px-3 py-1.5 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-3 text-gray-500">Chưa có danh mục nào.</td>
                        <td colspan="3"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
