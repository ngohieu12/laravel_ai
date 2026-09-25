@extends('layouts.dashboard')

@section('title', 'Danh sách người dùng')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">👥 Danh sách người dùng</h1>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Tên</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Vai trò</th>
                    <th class="px-4 py-3">Bài viết</th>
                    <th class="px-4 py-3">Lượt yêu thích</th>
                    <th class="px-4 py-3">Bình luận</th>
                    <th class="px-4 py-3">Bài đã lưu</th>
                    <th class="px-4 py-3">Tham gia</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $user->isAdmin() ? 'bg-slate-800 text-white' : ($user->isCreator() ? 'bg-slate-600 text-white' : 'bg-slate-100 text-slate-700') }}">
                                {{ $user->roleLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->posts_count ?? $user->posts()->count() }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->favorites_count ?? $user->favorites()->count() }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->comments_count ?? $user->comments()->count() }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->pinned_count ?? $user->pinnedPosts()->count() }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
