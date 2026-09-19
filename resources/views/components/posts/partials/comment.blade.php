@props(['comment', 'post', 'replyTo' => null])

<div id="comment-{{ $comment->id }}" class="py-4 {{ $comment->depth > 0 ? 'pl-4 sm:pl-6 border-l-2 border-slate-200 ml-2' : '' }}">
    <div class="flex space-x-3">
        <!-- Avatar -->
        <div class="flex-shrink-0">
            <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-semibold text-sm">
                {{ strtoupper(substr($comment->user?->name ?? 'A', 0, 1)) }}
            </div>
        </div>

        <!-- Body -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2 text-sm flex-wrap">
                <span class="font-medium text-gray-800">{{ $comment->user?->name ?? 'Người dùng' }}</span>
                <span class="text-gray-400">·</span>
                <span class="text-gray-500" title="{{ $comment->created_at->format('d/m/Y H:i') }}">{{ $comment->created_at->diffForHumans() }}</span>
                @if($replyTo)
                    <span class="text-gray-400 text-xs">trả lời <span class="text-slate-600 font-medium">{{ '@' . \Illuminate\Support\Str::words($replyTo, 2, '') }}</span></span>
                @endif
            </div>

            <div class="mt-1 text-gray-700 whitespace-pre-wrap break-words text-[15px] leading-relaxed">{{ $comment->content }}</div>

            <!-- Actions -->
            <div class="mt-2 flex items-center space-x-3 text-sm">
                @auth
                <form action="{{ route('posts.comments.favorite', ['post' => $post, 'comment' => $comment]) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center space-x-1 px-2 py-1 rounded-md transition {{ $comment->is_favorited ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-gray-500 hover:text-red-600 hover:bg-red-50' }}"
                        title="{{ $comment->is_favorited ? 'Bỏ thích' : 'Thích' }}">
                        <span class="text-base leading-none">{{ $comment->is_favorited ? '❤️' : '🤍' }}</span>
                        <span class="text-xs">{{ $comment->favorites_count }}</span>
                    </button>
                </form>
                @else
                <a href="{{ route('login') }}"
                    class="inline-flex items-center space-x-1 px-2 py-1 rounded-md transition text-gray-500 hover:text-red-600 hover:bg-red-50"
                    title="Đăng nhập để thích">
                    <span class="text-base leading-none">🤍</span>
                    <span class="text-xs">{{ $comment->favorites_count }}</span>
                </a>
                @endauth

                @auth
                    @if($comment->canReply())
                        <button type="button"
                            data-reply-toggle="{{ $comment->id }}"
                            class="inline-flex items-center space-x-1 px-2 py-1 rounded-md text-gray-500 hover:text-slate-700 hover:bg-slate-100 transition">
                            <span>💬</span>
                            <span>Trả lời</span>
                        </button>
                    @else
                        <span class="text-xs text-gray-400 italic">Đã đạt giới hạn trả lời</span>
                    @endif
                @endauth
            </div>

            <!-- Inline reply form (hidden by default) -->
            @auth
            @if($comment->canReply())
                <div id="reply-form-{{ $comment->id }}" class="mt-3 hidden">
                    <form action="{{ route('posts.comments.store', $post) }}" method="POST">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                            <textarea name="content" rows="2" required maxlength="2000"
                            placeholder="Trả lời {{ '@' . ($comment->user?->name ?? 'người dùng') }}..."
                            class="w-full border-gray-300 rounded-lg px-3 py-2 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400 text-sm resize-y"></textarea>
                        <div class="flex justify-end space-x-2 mt-2">
                            <button type="button" data-reply-cancel="{{ $comment->id }}" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 transition">
                                Hủy
                            </button>
                            <button type="submit" class="px-4 py-1.5 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                                Gửi trả lời
                            </button>
                        </div>
                    </form>
                </div>
            @endif
            @endauth

            <!-- Nested replies -->
            @if($comment->replies && $comment->replies->count() > 0)
                <div class="mt-1">
                    @foreach($comment->replies as $reply)
                        <x-posts.partials.comment :comment="$reply" :post="$post" :replyTo="$comment->user?->name" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
