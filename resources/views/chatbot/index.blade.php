@extends('layouts.app')

@section('title', 'Chatbot - Hỏi đáp về Bài viết')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden" style="height: calc(100vh - 180px); display: flex; flex-direction: column;">
        <!-- Chat Header -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 px-6 py-4 text-white">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl">🤖</div>
                <div>
                    <h1 class="text-lg font-bold">AI Chatbot</h1>
                    <p class="text-purple-200 text-sm">Hỏi đáp về thông tin các bài viết</p>
                </div>
            </div>
        </div>

        <!-- AI Not Configured Warning -->
        @if(!$aiConfigured)
        <div class="bg-amber-50 border-b border-amber-200 px-6 py-3">
            <div class="flex items-center space-x-2 text-amber-800 text-sm">
                <span>⚠️</span>
                <span>Chưa cấu hình API key AI Provider. Vui lòng thêm <code class="bg-amber-100 px-1 rounded">OPENAI_API_KEY</code> vào file <code class="bg-amber-100 px-1 rounded">.env</code></span>
            </div>
        </div>
        @endif

        <!-- Chat Messages -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50">
        </div>

        <!-- Typing Indicator (hidden by default) -->
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

        <!-- Chat Input -->
        <div class="border-t bg-white p-4">
            <form id="chat-form" class="flex items-center space-x-3">
                @csrf
                <div class="flex-1">
                    <textarea id="chat-input" name="message" rows="1" required
                        class="w-full border-gray-300 rounded-xl px-4 py-3 border focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none"
                        placeholder="Nhập câu hỏi của bạn..."
                        style="max-height: 120px;"
                    ></textarea>
                </div>
                <button type="submit" id="send-btn"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-xl transition flex-shrink-0 disabled:opacity-50"
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

    // Welcome message
    const welcomeHTML = `
        <div class="chat-bubble flex items-start space-x-3">
            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>
            <div class="bg-white border rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%] shadow-sm">
                <p class="text-gray-800">Xin chào! 👋 Tôi là trợ lý AI của Blog Manager.</p>
                <p class="text-gray-600 mt-1 text-sm">Bạn có thể hỏi tôi bất cứ điều gì về các bài viết, ví dụ:</p>
                <ul class="text-gray-600 text-sm mt-2 space-y-1">
                    <li>• Có bao nhiêu bài viết về "công nghệ"?</li>
                    <li>• Tóm tắt bài viết mới nhất</li>
                    <li>• Bài viết nào được viết bởi tác giả X?</li>
                    <li>• So sánh các bài viết trong danh mục Y</li>
                </ul>
            </div>
        </div>`;

    // Load history from localStorage
    const STORAGE_KEY = 'chatbot_history';
    function loadHistory() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                const messages = JSON.parse(saved);
                if (messages.length > 0) {
                    messages.forEach(m => appendMessage(m.text, m.sender, false));
                    return;
                }
            }
        } catch(e) { /* ignore */ }
        // No history — show welcome
        chatMessages.innerHTML = welcomeHTML;
    }
    function saveHistory() {
        const messages = [];
        chatMessages.querySelectorAll('.chat-bubble').forEach(bubble => {
            const isUser = bubble.classList.contains('flex-row-reverse');
            const text = bubble.querySelector('p')?.textContent || '';
            if (text) messages.push({ text, sender: isUser ? 'user' : 'bot' });
        });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
    }
    loadHistory();

    // Clear history button
    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.textContent = '🗑️ Xóa lịch sử';
    clearBtn.className = 'text-xs text-gray-400 hover:text-red-500 transition mt-1';
    clearBtn.onclick = async function() {
        localStorage.removeItem(STORAGE_KEY);
        chatMessages.innerHTML = welcomeHTML;
        try {
            await fetch('{{ route("chatbot.clear") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
            });
        } catch(e) { /* ignore */ }
    };
    document.querySelector('.bg-gradient-to-r').appendChild(clearBtn);

    // Prefill from URL parameter
    const prefill = '{{ $prefill }}';
    if (prefill) {
        chatInput.value = prefill;
        chatForm.dispatchEvent(new Event('submit'));
    }

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const message = chatInput.value.trim();
        if (!message) return;

        // Add user message
        appendMessage(message, 'user');
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
                body: JSON.stringify({ message }),
            });

            const data = await response.json();
            typingIndicator.classList.add('hidden');

            if (data.reply) {
                appendMessage(data.reply, 'bot');
            } else {
                appendMessage('Xin lỗi, có lỗi xảy ra. Vui lòng thử lại!', 'bot');
            }
        } catch (error) {
            typingIndicator.classList.add('hidden');
            appendMessage('Không thể kết nối đến server. Vui lòng thử lại!', 'bot');
            console.error('Chat error:', error);
        }

        sendBtn.disabled = false;
        chatInput.focus();
    });

    function appendMessage(text, sender, save = true) {
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble flex items-start space-x-3 ' + (sender === 'user' ? 'flex-row-reverse space-x-reverse' : '');

        const avatar = sender === 'user'
            ? '<div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm flex-shrink-0">👤</div>'
            : '<div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-sm flex-shrink-0">🤖</div>';

        const bgColor = sender === 'user' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white border rounded-tl-none';
        const textColor = sender === 'user' ? 'text-white' : 'text-gray-800';

        // Simple markdown-like formatting
        let formattedText = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 rounded text-sm">$1</code>')
            .replace(/\n/g, '<br>');

        bubble.innerHTML = `
            ${avatar}
            <div class="max-w-[80%]">
                <div class="px-4 py-3 rounded-2xl shadow-sm ${bgColor}">
                    <p class="${textColor} leading-relaxed">${formattedText}</p>
                </div>
            </div>
        `;

        chatMessages.appendChild(bubble);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        if (save) saveHistory();
    }
});
</script>

<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush
