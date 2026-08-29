@extends('layouts.app')

@section('title', 'Chatbot - Hỏi đáp về Bài viết')

@section('content')
<div style="height: calc(100vh - 120px); display: flex; gap: 0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">

    {{-- Left Panel: Conversation List --}}
    <div class="bg-white flex flex-col" style="width: 320px; min-width: 320px; border-right: 1px solid #e5e7eb;">
        {{-- Header --}}
        <div class="px-4 py-3 border-b flex items-center justify-between bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700">💬 Hội thoại</h2>
            <button id="new-chat-btn" class="text-xs bg-slate-600 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg transition">
                + Mới
            </button>
        </div>

        {{-- Conversation List --}}
        <div id="conversation-list" class="flex-1 overflow-y-auto">
            @forelse($conversations as $conv)
            <div class="conversation-item px-4 py-3 border-b cursor-pointer hover:bg-slate-50 transition
                {{ $conv->id === $currentConversationId ? 'bg-slate-100 border-l-4 border-l-slate-500' : '' }}"
                data-id="{{ $conv->id }}"
                onclick="loadConversation('{{ $conv->id }}')">
                <div class="text-sm font-medium text-gray-800 truncate">{{ $conv->title }}</div>
                <div class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($conv->updated_at)->diffForHumans() }}</div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-400 text-sm">
                Chưa có hội thoại nào
            </div>
            @endforelse
        </div>
    </div>

    {{-- Right Panel: Chat Detail --}}
    <div class="bg-white flex flex-col flex-1">

        {{-- Chat Header --}}
        <div class="bg-gradient-to-r from-slate-600 to-slate-500 px-6 py-3 text-white flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center text-lg">🤖</div>
                <div>
                    <h1 class="text-base font-bold">AI Chatbot</h1>
                    <p class="text-slate-300 text-xs" id="chat-subtitle">Hỏi đáp về thông tin các bài viết</p>
                </div>
            </div>
            <button id="clear-btn" class="text-xs text-white/70 hover:text-white transition" title="Xóa hội thoại hiện tại">
                🗑️ Xóa
            </button>
        </div>

        {{-- AI Not Configured Warning --}}
        @if(!$aiConfigured)
        <div class="bg-amber-50 border-b border-amber-200 px-6 py-2">
            <div class="flex items-center space-x-2 text-amber-800 text-xs">
                <span>⚠️</span>
                <span>Chưa cấu hình API key. Thêm <code class="bg-amber-100 px-1 rounded">OPENAI_API_KEY</code> vào <code class="bg-amber-100 px-1 rounded">.env</code></span>
            </div>
        </div>
        @endif

        {{-- Chat Messages --}}
        <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50">
            @if($currentMessages->isEmpty())
                @if($postContext)
                <div id="post-context-card" class="bg-white border border-slate-200 rounded-xl p-4 max-w-[85%] shadow-sm mx-auto">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="text-slate-500 text-lg">📄</span>
                        <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Bài viết được chọn</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 mb-1">{{ $postContext['title'] }}</h3>
                    <p class="text-xs text-gray-500 mb-2">{{ $postContext['summary'] }}</p>
                    <div class="text-xs text-gray-400 border-t pt-2 mt-2">
                        {{ \Illuminate\Support\Str::limit($postContext['snippet'], 200) }}
                    </div>
                </div>
                <div class="chat-bubble flex items-start space-x-3">
                    <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>
                    <div class="bg-white border rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%] shadow-sm">
                        <p class="text-gray-800">Xin chào! 👋 Tôi thấy bạn đang xem bài viết <strong>"{{ $postContext['title'] }}"</strong>.</p>
                        <p class="text-gray-600 mt-1 text-sm">Bạn muốn hỏi gì về bài viết này? Ví dụ:</p>
                        <ul class="text-gray-600 text-sm mt-2 space-y-1">
                            <li>• Tóm tắt nội dung chính</li>
                            <li>• Giải thích chi tiết phần nào?</li>
                            <li>• Có liên quan gì đến bài viết khác không?</li>
                        </ul>
                    </div>
                </div>
                @else
                <div id="welcome-msg" class="chat-bubble flex items-start space-x-3">
                    <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>
                    <div class="bg-white border rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%] shadow-sm">
                        <p class="text-gray-800">Xin chào! 👋 Tôi là trợ lý AI của Blog Manager.</p>
                        <p class="text-gray-600 mt-1 text-sm">Bạn có thể hỏi tôi về các bài viết, ví dụ:</p>
                        <ul class="text-gray-600 text-sm mt-2 space-y-1">
                            <li>• Có bao nhiêu bài viết về "công nghệ"?</li>
                            <li>• Tóm tắt bài viết mới nhất</li>
                            <li>• Viết bài mới với nội dung HTML</li>
                        </ul>
                    </div>
                </div>
                @endif
            @else
                @foreach($currentMessages as $msg)
                <div class="chat-bubble flex items-start space-x-3 {{ $msg->role === 'user' ? 'flex-row-reverse space-x-reverse' : '' }}">
                    <div class="w-8 h-8 {{ $msg->role === 'user' ? 'bg-slate-200' : 'bg-slate-100' }} rounded-full flex items-center justify-center text-sm flex-shrink-0">
                        {{ $msg->role === 'user' ? '👤' : '🤖' }}
                    </div>
                    <div class="max-w-[80%]">
                        <div class="px-4 py-3 rounded-2xl shadow-sm {{ $msg->role === 'user' ? 'bg-slate-500 text-white rounded-tr-none' : 'bg-white border rounded-tl-none' }}">
                            <p class="{{ $msg->role === 'user' ? 'text-white' : 'text-gray-800' }} leading-relaxed">{!! nl2br(e($msg->content)) !!}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        {{-- Typing Indicator --}}
        <div id="typing-indicator" class="hidden px-6 pb-2">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>
                <div class="bg-white border rounded-2xl rounded-tl-none px-4 py-3 shadow-sm">
                    <div class="typing-dots flex space-x-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat Input --}}
        <div class="border-t bg-white p-4">
            <form id="chat-form" class="flex items-center space-x-3">
                @csrf
                <div class="flex-1">
                    <textarea id="chat-input" name="message" rows="1" required
                        class="w-full border-gray-300 rounded-xl px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400 resize-none"
                        placeholder="Nhập câu hỏi của bạn..."
                        style="max-height: 120px;"
                    ></textarea>
                </div>
                <button type="submit" id="send-btn"
                    class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-3 rounded-xl transition flex-shrink-0 disabled:opacity-50"
                    title="Gửi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-btn');
    const typingIndicator = document.getElementById('typing-indicator');
    const conversationList = document.getElementById('conversation-list');
    const newChatBtn = document.getElementById('new-chat-btn');
    const clearBtn = document.getElementById('clear-btn');

    let currentConversationId = '{{ $currentConversationId }}';

    // Post context from query param (no DB conversation yet)
    const postContext = @json($postContext);

    // Track whether post context has been consumed by first message
    let postContextUsed = false;

    // If postContext exists, show a temporary sidebar item (no DB record yet)
    if (postContext && !currentConversationId) {
        const emptyMsg = conversationList.querySelector('.px-4.py-8');
        if (emptyMsg) emptyMsg.remove();

        const tempItem = document.createElement('div');
        tempItem.className = 'conversation-item px-4 py-3 border-b cursor-pointer hover:bg-slate-50 transition bg-slate-100 border-l-4 border-l-slate-500';
        tempItem.dataset.temp = 'true';
        tempItem.innerHTML = `
            <div class="text-sm font-medium text-gray-800 truncate">Hỏi về: ${postContext.title}</div>
            <div class="text-xs text-gray-400 mt-1">vừa xong</div>`;
        conversationList.prepend(tempItem);
    }

    // Per-conversation state: { [id]: { html: string, isLoading: boolean } }
    const conversationStates = {};

    // Initialize current conversation state from server-rendered HTML
    if (currentConversationId) {
        conversationStates[currentConversationId] = {
            html: chatMessages.innerHTML,
            isLoading: false,
        };
    }

    // Auto-resize textarea
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Send message on Enter (Shift+Enter for new line)
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    // Save current conversation's visible state
    function saveCurrentState() {
        if (!currentConversationId) return;
        conversationStates[currentConversationId] = {
            html: chatMessages.innerHTML,
            isLoading: !typingIndicator.classList.contains('hidden'),
        };
    }

    // Restore a conversation's visible state
    function restoreState(id) {
        const state = conversationStates[id];
        if (!state) return;

        chatMessages.innerHTML = state.html;

        if (state.isLoading) {
            typingIndicator.classList.remove('hidden');
            sendBtn.disabled = true;
        } else {
            typingIndicator.classList.add('hidden');
            sendBtn.disabled = false;
        }
    }

    // New conversation button
    newChatBtn.addEventListener('click', async function() {
        // Save outgoing conversation first
        saveCurrentState();

        try {
            const response = await fetch('{{ route("chatbot.new") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();

            if (data.conversation_id) {
                currentConversationId = data.conversation_id;

                // Remove empty-state message if present
                const emptyMsg = conversationList.querySelector('.px-4.py-8');
                if (emptyMsg) emptyMsg.remove();

                // Add new item to top of sidebar
                const item = document.createElement('div');
                item.className = 'conversation-item px-4 py-3 border-b cursor-pointer hover:bg-slate-50 transition bg-slate-100 border-l-4 border-l-slate-500';
                item.dataset.id = data.conversation_id;
                item.setAttribute('onclick', `loadConversation('${data.conversation_id}')`);
                item.innerHTML = `
                    <div class="text-sm font-medium text-gray-800 truncate">Cuộc trò chuyện mới</div>
                    <div class="text-xs text-gray-400 mt-1">vừa xong</div>`;
                conversationList.prepend(item);

                // Remove active highlight from other items
                document.querySelectorAll('.conversation-item').forEach(el => {
                    if (el.dataset.id !== data.conversation_id) {
                        el.classList.remove('bg-slate-100', 'border-l-4', 'border-l-slate-500');
                    }
                });

                // Initialize empty state for new conversation
                const welcomeHTML = `
                    <div class="chat-bubble flex items-start space-x-3">
                        <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>
                        <div class="bg-white border rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%] shadow-sm">
                            <p class="text-gray-800">Xin chào! 👋 Tôi là trợ lý AI của Blog Manager.</p>
                            <p class="text-gray-600 mt-1 text-sm">Hãy bắt đầu một cuộc trò chuyện mới!</p>
                        </div>
                    </div>`;
                conversationStates[currentConversationId] = { html: welcomeHTML, isLoading: false };
                chatMessages.innerHTML = welcomeHTML;
                typingIndicator.classList.add('hidden');
                sendBtn.disabled = false;
            }
        } catch(e) { /* ignore */ }
    });

    // Clear history button
    clearBtn.addEventListener('click', async function() {
        const clearedId = currentConversationId;

        try {
            await fetch('{{ route("chatbot.clear") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
            });
        } catch(e) { /* ignore */ }

        // Remove state
        if (clearedId) delete conversationStates[clearedId];
        currentConversationId = '';

        chatMessages.innerHTML = `
            <div class="chat-bubble flex items-start space-x-3">
                <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>
                <div class="bg-white border rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%] shadow-sm">
                    <p class="text-gray-800">Đã xóa lịch sử hội thoại. Bắt đầu lại nào! 👋</p>
                </div>
            </div>`;
        typingIndicator.classList.add('hidden');
        sendBtn.disabled = false;

        // Remove active highlight
        document.querySelectorAll('.conversation-item').forEach(el => {
            el.classList.remove('bg-slate-100', 'border-l-4', 'border-l-slate-500');
        });

        // Remove from sidebar
        if (clearedId) {
            const item = document.querySelector(`.conversation-item[data-id="${clearedId}"]`);
            if (item) item.remove();
        }
    });

    // Load a conversation
    window.loadConversation = async function(id) {
        if (id === currentConversationId) return;

        // Save current conversation state before switching
        saveCurrentState();

        try {
            const response = await fetch(`/chatbot/${id}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await response.json();
            if (data.error) return;

            currentConversationId = data.id;

            // Update sidebar active state
            document.querySelectorAll('.conversation-item').forEach(el => {
                el.classList.remove('bg-slate-100', 'border-l-4', 'border-l-slate-500');
                if (el.dataset.id === id) {
                    el.classList.add('bg-slate-100', 'border-l-4', 'border-l-slate-500');
                }
            });

            // Build initial HTML from server messages (first load only)
            if (!conversationStates[id]) {
                let html = '';
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        html += buildBubbleHTML(msg.content, msg.role === 'user' ? 'user' : 'bot');
                    });
                } else {
                    html = `
                        <div class="chat-bubble flex items-start space-x-3">
                            <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>
                            <div class="bg-white border rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%] shadow-sm">
                                <p class="text-gray-800">Hội thoại trống. Hãy gửi tin nhắn đầu tiên!</p>
                            </div>
                        </div>`;
                }
                conversationStates[id] = { html, isLoading: false };
            }

            // Restore the saved state (preserves loading indicator)
            restoreState(id);

        } catch(e) {
            console.error('Failed to load conversation:', e);
        }
    };

    // Send message
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const message = chatInput.value.trim();
        if (!message) return;

        const activeId = currentConversationId;

        // Prepend post context to message for AI (first message only)
        let messageToSend = message;
        if (postContext && !postContextUsed) {
            postContextUsed = true;
            messageToSend = `[Bài viết: ${postContext.title}]\nTóm tắt: ${postContext.summary}\nNội dung tóm tắt: ${postContext.snippet}\n\nCâu hỏi: ${message}`;
            // Remove post context card from DOM
            const ctxCard = document.getElementById('post-context-card');
            if (ctxCard) ctxCard.remove();
        }

        // Add user message to state and UI
        const userBubble = buildBubbleHTML(message, 'user');
        if (conversationStates[activeId]) {
            conversationStates[activeId].html += userBubble;
            conversationStates[activeId].isLoading = true;
        }
        chatMessages.innerHTML += userBubble;

        chatInput.value = '';
        chatInput.style.height = 'auto';

        // Show typing indicator
        typingIndicator.classList.remove('hidden');
        chatMessages.scrollTop = chatMessages.scrollHeight;
        sendBtn.disabled = true;

        try {
            const response = await fetch('{{ route("chatbot.chat") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: messageToSend, conversation_id: activeId || null }),
            });

            const data = await response.json();

            // Sync conversation ID from server
            if (data.conversation_id) {
                if (activeId !== data.conversation_id) {
                    // Server created a new conversation — migrate state
                    conversationStates[data.conversation_id] = conversationStates[activeId] || { html: '', isLoading: false };
                    delete conversationStates[activeId];
                }
                currentConversationId = data.conversation_id;

                // Update temp sidebar item with real conversation ID
                const tempItem = conversationList.querySelector('[data-temp="true"]');
                if (tempItem) {
                    tempItem.dataset.id = data.conversation_id;
                    tempItem.dataset.temp = 'false';
                    tempItem.setAttribute('onclick', `loadConversation('${data.conversation_id}')`);
                }
            }

            const targetId = currentConversationId;

            if (data.reply) {
                const botBubble = buildBubbleHTML(data.reply, 'bot');
                if (conversationStates[targetId]) {
                    conversationStates[targetId].html += botBubble;
                    conversationStates[targetId].isLoading = false;
                }
                // Only update UI if we're still viewing this conversation
                if (targetId === currentConversationId) {
                    chatMessages.innerHTML += botBubble;
                    typingIndicator.classList.add('hidden');
                }
            } else {
                const errBubble = buildBubbleHTML('Xin lỗi, có lỗi xảy ra. Vui lòng thử lại!', 'bot');
                if (conversationStates[targetId]) {
                    conversationStates[targetId].html += errBubble;
                    conversationStates[targetId].isLoading = false;
                }
                if (targetId === currentConversationId) {
                    chatMessages.innerHTML += errBubble;
                    typingIndicator.classList.add('hidden');
                }
            }
        } catch (error) {
            const errBubble = buildBubbleHTML('Không thể kết nối đến server. Vui lòng thử lại!', 'bot');
            if (conversationStates[activeId]) {
                conversationStates[activeId].html += errBubble;
                conversationStates[activeId].isLoading = false;
            }
            if (activeId === currentConversationId) {
                chatMessages.innerHTML += errBubble;
                typingIndicator.classList.add('hidden');
            }
            console.error('Chat error:', error);
        }

        sendBtn.disabled = false;
        chatInput.focus();
    });

    function buildBubbleHTML(text, sender) {
        const avatar = sender === 'user'
            ? '<div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-sm flex-shrink-0">👤</div>'
            : '<div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>';

        const bgColor = sender === 'user' ? 'bg-slate-500 text-white rounded-tr-none' : 'bg-white border rounded-tl-none';
        const textColor = sender === 'user' ? 'text-white' : 'text-gray-800';
        const rowReverse = sender === 'user' ? 'flex-row-reverse space-x-reverse' : '';

        let formattedText = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 rounded text-sm">$1</code>')
            .replace(/\n/g, '<br>');

        return `<div class="chat-bubble flex items-start space-x-3 ${rowReverse}">
            ${avatar}
            <div class="max-w-[80%]">
                <div class="px-4 py-3 rounded-2xl shadow-sm ${bgColor}">
                    <p class="${textColor} leading-relaxed">${formattedText}</p>
                </div>
            </div>
        </div>`;
    }

    // Update placeholder when postContext is present
    if (postContext && !currentConversationId) {
        chatInput.placeholder = 'Hỏi về bài viết: ' + postContext.title + '...';
    }
});
</script>

<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush
