@extends('layouts.dashboard')

@section('title', 'Quản lý người dùng')

@section('actions')
    <a href="{{ route('admin.users.create') }}"
       class="px-3 py-1.5 bg-slate-600 text-white rounded-lg hover:bg-slate-700 text-sm font-medium">
        ➕ Thêm người dùng
    </a>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-800">👥 Quản lý người dùng</h1>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-xs uppercase text-slate-500 tracking-wide">Tổng cộng</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $totals['users'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-xs uppercase text-slate-500 tracking-wide">Quản trị viên</div>
            <div class="text-2xl font-bold text-slate-800 mt-1">{{ $totals['admins'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-xs uppercase text-slate-500 tracking-wide">Người đăng bài</div>
            <div class="text-2xl font-bold text-slate-600 mt-1">{{ $totals['creators'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-xs uppercase text-slate-500 tracking-wide">Thành viên</div>
            <div class="text-2xl font-bold text-gray-600 mt-1">{{ $totals['readers'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-xs uppercase text-slate-500 tracking-wide">Đã khóa</div>
            <div class="text-2xl font-bold text-red-600 mt-1">{{ $totals['banned'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3 items-center">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Tìm theo tên hoặc email..."
                   class="flex-1 min-w-[200px] border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
            <select name="role" class="border-gray-300 rounded-lg px-3 py-2 border focus:ring-2 focus:ring-slate-400">
                <option value="">Tất cả vai trò</option>
                @foreach(App\Models\User::ROLE_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="border-gray-300 rounded-lg px-3 py-2 border focus:ring-2 focus:ring-slate-400">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Hoạt động</option>
                <option value="banned" @selected(request('status') === 'banned')>Đã khóa</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 font-medium">Lọc</button>
            @if(request('search') || request('role') || request('status'))
                <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-slate-600 hover:text-slate-800">Xóa bộ lọc</a>
            @endif
        </form>
    </div>

    {{-- Users table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Tên</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Vai trò</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3">Bài viết</th>
                    <th class="px-4 py-3">Bình luận</th>
                    <th class="px-4 py-3">Tham gia</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($users as $user)
                    <tr class="{{ $user->isBanned() ? 'bg-red-50/50' : '' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-gradient-to-br
                                    {{ $user->isAdmin() ? 'from-amber-500 to-red-600' : ($user->isCreator() ? 'from-sky-500 to-indigo-600' : 'from-slate-400 to-slate-600') }}
                                    rounded-full flex items-center justify-center text-white font-semibold text-xs">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.users.show', $user) }}" class="font-medium text-gray-800 hover:underline">
                                        {{ $user->name }}
                                    </a>
                                    @if($user->id === auth()->id())
                                        <span class="ml-1 text-[10px] font-semibold uppercase text-sky-600">(bạn)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $user->isAdmin() ? 'bg-slate-800 text-white' : ($user->isCreator() ? 'bg-slate-600 text-white' : 'bg-slate-100 text-slate-700') }}">
                                {{ $user->roleLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($user->isBanned())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    🔒 Đã khóa
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    ✓ Hoạt động
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <span class="font-medium">{{ $user->published_posts_count }}</span>
                            <span class="text-slate-400">/ {{ $user->posts_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->comments_count }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('admin.users.show', $user) }}"
                                   class="px-2 py-1 text-slate-600 hover:bg-slate-100 rounded" title="Xem">
                                    👁️
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="px-2 py-1 text-slate-600 hover:bg-slate-100 rounded" title="Sửa">
                                    ✏️
                                </a>

                                @if($user->id !== auth()->id())
                                    @if($user->isBanned())
                                        <form method="POST" action="{{ route('admin.users.unban', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2 py-1 text-green-600 hover:bg-green-50 rounded"
                                                    title="Mở khóa"
                                                    onclick="return confirm('Mở khóa tài khoản này?')">
                                                🔓
                                            </button>
                                        </form>
                                    @else
                                        <button type="button"
                                                onclick="document.getElementById('ban-{{ $user->id }}').classList.remove('hidden')"
                                                class="px-2 py-1 text-amber-600 hover:bg-amber-50 rounded" title="Khóa">
                                            🔒
                                        </button>
                                    @endif

                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này? Tất cả bài viết và bình luận của họ sẽ bị xóa.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1 text-red-600 hover:bg-red-50 rounded"
                                                title="Xóa">
                                            🗑️
                                        </button>
                                    </form>
                                @endif
                            </div>

                            {{-- Ban form (inline collapse) --}}
                            @if(!$user->isBanned() && $user->id !== auth()->id())
                            <div id="ban-{{ $user->id }}" class="hidden mt-2 p-2 bg-amber-50 border border-amber-200 rounded-lg text-left">
                                <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="space-y-2">
                                    @csrf
                                    <label class="block text-xs font-medium text-amber-800">Lý do khóa (tùy chọn):</label>
                                    <textarea name="ban_reason" rows="2" maxlength="500"
                                              class="w-full text-xs border-amber-300 rounded px-2 py-1 border focus:ring-2 focus:ring-amber-400"
                                              placeholder="Vi phạm quy định..."></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                                onclick="document.getElementById('ban-{{ $user->id }}').classList.add('hidden')"
                                                class="px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 rounded">Hủy</button>
                                        <button type="submit"
                                                class="px-3 py-1 text-xs bg-amber-600 text-white rounded hover:bg-amber-700">
                                            Xác nhận khóa
                                        </button>
                                    </div>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Không tìm thấy người dùng nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
