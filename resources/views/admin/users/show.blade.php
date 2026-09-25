@extends('layouts.dashboard')

@section('title', 'Hồ sơ: ' . $user->name)

@section('actions')
    <a href="{{ route('admin.users.edit', $user) }}" class="px-3 py-1.5 bg-slate-600 text-white rounded-lg hover:bg-slate-700 text-sm font-medium">
        ✏️ Chỉnh sửa
    </a>
    <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg text-sm">← Danh sách</a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Profile header --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-6">
            <div class="flex flex-wrap items-center gap-5">
                <div class="w-20 h-20 bg-gradient-to-br
                    {{ $user->isAdmin() ? 'from-amber-500 to-red-600' : ($user->isCreator() ? 'from-sky-500 to-indigo-600' : 'from-slate-400 to-slate-600') }}
                    rounded-full flex items-center justify-center text-white font-bold text-3xl shadow">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        {{ $user->name }}
                        @if($user->id === auth()->id())
                            <span class="text-xs bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full font-semibold">Bạn</span>
                        @endif
                    </h1>
                    <p class="text-gray-500 mt-1">{{ $user->email }}</p>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $user->isAdmin() ? 'bg-slate-800 text-white' : ($user->isCreator() ? 'bg-slate-600 text-white' : 'bg-slate-100 text-slate-700') }}">
                            {{ $user->roleLabel() }}
                        </span>
                        @if($user->isBanned())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                🔒 Đã khóa
                                @if($user->banned_at)· {{ $user->banned_at->format('d/m/Y H:i') }}@endif
                            </span>
                            @if($user->ban_reason)
                                <span class="text-xs text-red-600 italic">"{{ $user->ban_reason }}"</span>
                            @endif
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                ✓ Hoạt động
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Quick actions --}}
                @if($user->id !== auth()->id())
                <div class="flex flex-col gap-2 w-full sm:w-auto">
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 text-sm font-medium text-center">
                        ✏️ Chỉnh sửa
                    </a>
                    @if($user->isBanned())
                        <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                            @csrf
                            <button type="submit" onclick="return confirm('Mở khóa tài khoản này?')"
                                class="w-full px-4 py-2 border border-green-300 text-green-700 rounded-lg hover:bg-green-50 text-sm font-medium">
                                🔓 Mở khóa
                            </button>
                        </form>
                    @else
                        <button type="button" onclick="document.getElementById('ban-box').classList.toggle('hidden')"
                            class="px-4 py-2 border border-amber-300 text-amber-700 rounded-lg hover:bg-amber-50 text-sm font-medium">
                            🔒 Khóa tài khoản
                        </button>
                    @endif
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                          onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này? Tất cả bài viết và bình luận sẽ bị xóa.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm font-medium">
                            🗑️ Xóa tài khoản
                        </button>
                    </form>
                </div>
                @endif
            </div>

            @if(!$user->isBanned() && $user->id !== auth()->id())
            <div id="ban-box" class="hidden mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="space-y-3">
                    @csrf
                    <label class="block text-sm font-medium text-amber-800">Lý do khóa tài khoản:</label>
                    <textarea name="ban_reason" rows="2" maxlength="500"
                        class="w-full text-sm border-amber-300 rounded-lg px-3 py-2 border focus:ring-2 focus:ring-amber-400"
                        placeholder="Vi phạm quy định..."></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('ban-box').classList.add('hidden')"
                            class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded">Hủy</button>
                        <button type="submit"
                            class="px-4 py-1.5 text-sm bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium">
                            Xác nhận khóa
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 md:grid-cols-5 divide-x divide-y md:divide-y-0 border-t bg-slate-50/50">
            <div class="p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $user->posts_count }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mt-1">Tổng bài viết</div>
            </div>
            <div class="p-4 text-center">
                <div class="text-2xl font-bold text-green-700">{{ $user->published_posts_count }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mt-1">Bài đã đăng</div>
            </div>
            <div class="p-4 text-center">
                <div class="text-2xl font-bold text-sky-700">{{ $user->comments_count }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mt-1">Bình luận</div>
            </div>
            <div class="p-4 text-center">
                <div class="text-2xl font-bold text-rose-600">{{ $user->favorites_count }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mt-1">Yêu thích</div>
            </div>
            <div class="p-4 text-center">
                <div class="text-2xl font-bold text-indigo-700">{{ $user->pinned_posts_count }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wide mt-1">Đã lưu</div>
            </div>
        </div>

        <div class="px-6 py-3 bg-white border-t text-xs text-gray-500 grid grid-cols-2 gap-2">
            <div>📅 Ngày tham gia: <span class="font-medium text-gray-700">{{ $user->created_at->format('d/m/Y H:i') }}</span></div>
            <div>🔄 Cập nhật cuối: <span class="font-medium text-gray-700">{{ $user->updated_at->format('d/m/Y H:i') }}</span></div>
            @if($user->email_verified_at)
                <div>✉️ Email xác thực: <span class="font-medium text-green-600">{{ $user->email_verified_at->format('d/m/Y H:i') }}</span></div>
            @else
                <div>✉️ Email xác thực: <span class="font-medium text-red-600">Chưa xác thực</span></div>
            @endif
        </div>
    </div>

    {{-- Recent posts --}}
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📝 Bài viết gần đây</h2>
        @if($recentPosts->isEmpty())
            <p class="text-gray-500 text-sm">Người dùng chưa có bài viết nào.</p>
        @else
            <div class="divide-y">
                @foreach($recentPosts as $post)
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('posts.show', $post) }}" class="font-medium text-gray-800 hover:text-slate-700 truncate block">
                                {{ $post->title }}
                            </a>
                            <div class="text-xs text-gray-500 mt-1 flex gap-3">
                                <span>{{ $post->created_at->format('d/m/Y H:i') }}</span>
                                <span>👁 {{ $post->views_count }}</span>
                                <span>💬 {{ $post->comments_count }}</span>
                                @if(!$post->is_published)
                                    <span class="text-amber-600">Bản nháp</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('posts.show', $post) }}" class="text-slate-600 hover:underline text-sm">Xem →</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Recent comments --}}
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">💬 Bình luận gần đây</h2>
        @if($recentComments->isEmpty())
            <p class="text-gray-500 text-sm">Người dùng chưa có bình luận nào.</p>
        @else
            <div class="divide-y">
                @foreach($recentComments as $comment)
                    <div class="py-3">
                        <div class="text-xs text-gray-500 mb-1">
                            Về bài:
                            <a href="{{ route('posts.show', $comment->post) }}" class="text-slate-700 hover:underline">{{ $comment->post->title }}</a>
                            · {{ $comment->created_at->format('d/m/Y H:i') }}
                        </div>
                        <p class="text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($comment->content, 200) }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
