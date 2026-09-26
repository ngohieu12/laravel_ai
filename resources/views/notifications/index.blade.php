@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🔔 Thông báo</h1>
            <p class="text-sm text-gray-500 mt-1">
                Tương tác trên bài viết và bình luận của bạn.
                @if($unreadCount > 0)
                    <span class="font-medium text-red-600">{{ $unreadCount }} chưa đọc</span>
                @endif
            </p>
        </div>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition text-sm font-medium">
                    ✅ Đánh dấu tất cả đã đọc
                </button>
            </form>
        @endif
    </div>

    <!-- List -->
    @if($notifications->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border divide-y divide-gray-100">
            @foreach($notifications as $notification)
                @php
                    $data = $notification->data;
                    $kindIcons = [
                        'comment' => '💬',
                        'reply' => '↩️',
                        'favorite' => '❤️',
                        'pin' => '📌',
                        'comment_favorite' => '👍',
                    ];
                    $icon = $kindIcons[$data['kind'] ?? ''] ?? '🔔';
                    $targetUrl = $data['url'] ?? null;
                    $isUnread = $notification->read_at === null;
                @endphp
                <div class="flex items-start gap-3 p-4 sm:p-5 {{ $isUnread ? 'bg-slate-50' : '' }}">
                    <span class="w-10 h-10 shrink-0 rounded-full bg-slate-100 flex items-center justify-center text-lg" title="{{ $data['kind'] ?? 'notification' }}">
                        {{ $icon }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700 {{ $isUnread ? 'font-medium' : '' }}">
                            @if($isUnread)
                                <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1.5 align-middle" title="Chưa đọc"></span>
                            @endif
                            {{ $data['message'] ?? '(Thông báo)' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            🕒 {{ $notification->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="shrink-0">
                        @if($isUnread)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-600 text-white hover:bg-slate-700 transition" title="Đánh dấu đã đọc và mở">
                                    Xem →
                                </button>
                            </form>
                        @elseif($targetUrl)
                            <a href="{{ $targetUrl }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 transition">
                                Xem →
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
            <div class="text-5xl mb-3">🔕</div>
            <p class="text-gray-500 font-medium">Chưa có thông báo nào</p>
            <p class="text-sm text-gray-400 mt-1">Bạn sẽ nhận thông báo khi có ai đó bình luận, trả lời, thích hoặc ghim bài viết / bình luận của bạn.</p>
        </div>
    @endif
</div>
@endsection
