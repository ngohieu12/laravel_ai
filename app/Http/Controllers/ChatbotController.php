<?php

namespace App\Http\Controllers;

use App\Ai\Agents\BlogChatbotAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    /**
     * Display chatbot interface with conversation list.
     */
    public function index(Request $request)
    {
        $prefill = $request->query('q', '');
        $aiConfigured = (bool) config('ai.providers.'.config('ai.default').'.key');

        $conversations = DB::table('agent_conversations')
            ->select('id', 'title', 'created_at', 'updated_at')
            ->orderByDesc('updated_at')
            ->get();

        $currentConversationId = session()->get('chatbot_conversation_id');
        $currentMessages = collect();

        if ($currentConversationId) {
            $currentMessages = DB::table('agent_conversation_messages')
                ->where('conversation_id', $currentConversationId)
                ->orderBy('created_at')
                ->get();
        }

        return view('chatbot.index', compact('prefill', 'aiConfigured', 'conversations', 'currentConversationId', 'currentMessages'));
    }

    /**
     * Load a specific conversation's messages (AJAX).
     */
    public function show(string $id)
    {
        $conversation = DB::table('agent_conversations')->where('id', $id)->first();
        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        session()->put('chatbot_conversation_id', $id);

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $id)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $messages,
        ]);
    }

    /**
     * Start a new conversation — creates it immediately so it appears in the sidebar.
     */
    public function newConversation()
    {
        $conversationId = Str::uuid7()->toString();
        DB::table('agent_conversations')->insert([
            'id' => $conversationId,
            'title' => 'Cuộc trò chuyện mới',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        session()->put('chatbot_conversation_id', $conversationId);

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Handle chatbot messages using AI Agent.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $userMessage = $request->input('message');

        // Resolve conversation: prefer request body > session > create new
        $conversationId = $request->input('conversation_id')
            ?? session()->get('chatbot_conversation_id');

        if (! $conversationId || ! DB::table('agent_conversations')->where('id', $conversationId)->exists()) {
            $conversationId = Str::uuid7()->toString();
            DB::table('agent_conversations')->insert([
                'id' => $conversationId,
                'title' => mb_substr($userMessage, 0, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        session()->put('chatbot_conversation_id', $conversationId);

        // Build context with token-limited history
        $contextMessage = $this->buildContextWithHistory($conversationId, $userMessage);

        // Save user message to DB
        DB::table('agent_conversation_messages')->insert([
            'id' => Str::uuid7()->toString(),
            'conversation_id' => $conversationId,
            'agent' => 'BlogChatbotAgent',
            'role' => 'user',
            'content' => $userMessage,
            'attachments' => json_encode([]),
            'tool_calls' => json_encode([]),
            'tool_results' => json_encode([]),
            'usage' => json_encode([]),
            'meta' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $agent = BlogChatbotAgent::make();
            $response = $agent->prompt($contextMessage);
            $reply = $response->text;

            // Save bot response to DB
            DB::table('agent_conversation_messages')->insert([
                'id' => Str::uuid7()->toString(),
                'conversation_id' => $conversationId,
                'agent' => 'BlogChatbotAgent',
                'role' => 'assistant',
                'content' => $reply,
                'attachments' => json_encode([]),
                'tool_calls' => json_encode([]),
                'tool_results' => json_encode([]),
                'usage' => json_encode($response->usage ? $response->usage->toArray() : []),
                'meta' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update conversation title and timestamp
            DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->update(['updated_at' => now()]);

            return response()->json([
                'reply' => $reply,
                'conversation_id' => $conversationId,
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot error: '.$e->getMessage());

            return response()->json([
                'reply' => "⚠️ Chatbot chưa được cấu hình AI Provider.\n\nVui lòng thêm API key vào file `.env`, ví dụ:\n\n```\nOPENAI_API_KEY=sk-xxx...\n```\n\nHoặc đổi sang provider khác trong `config/ai.php`.",
                'error' => true,
            ]);
        }
    }

    /**
     * Resolve the max context token limit based on current provider and model.
     *
     * Priority: model-specific > provider-level > global default.
     */
    private function resolveMaxContextTokens(): int
    {
        $default = (int) config('chatbot.max_context_tokens', 4000);
        $provider = config('ai.default');
        $limits = config('chatbot.context_limits', []);

        if (! isset($limits[$provider])) {
            return $default;
        }

        $providerLimits = $limits[$provider];

        $model = $this->resolveCurrentModel($provider);

        if ($model && isset($providerLimits['models'][$model])) {
            return (int) $providerLimits['models'][$model];
        }

        if (isset($providerLimits['max_tokens'])) {
            return (int) $providerLimits['max_tokens'];
        }

        return $default;
    }

    /**
     * Resolve the model name for the current AI provider.
     */
    private function resolveCurrentModel(string $provider): ?string
    {
        $providerConfig = config("ai.providers.{$provider}", []);

        if (isset($providerConfig['model'])) {
            return $providerConfig['model'];
        }

        if (isset($providerConfig['models']['text']['default'])) {
            return $providerConfig['models']['text']['default'];
        }

        $upperProvider = strtoupper($provider);

        return env("{$upperProvider}_MODEL") ?? env('AI_MODEL');
    }

    /**
     * Build context string from conversation history, respecting token limits.
     *
     * Uses a sliding window: takes the most recent messages that fit within
     * the token budget. Tokens are estimated at ~2 chars per token for
     * Vietnamese/mixed content.
     */
    private function buildContextWithHistory(string $conversationId, string $userMessage): string
    {
        $maxContextTokens = $this->resolveMaxContextTokens();
        $charsPerToken = 2;
        $maxContextChars = $maxContextTokens * $charsPerToken;

        // Fetch all messages for this conversation, newest first
        $allMessages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->reverse();

        if ($allMessages->isEmpty()) {
            return $userMessage;
        }

        // Build history from newest to oldest, stopping when budget is reached
        $historyParts = [];
        $usedChars = 0;

        // Reserve budget for the current user message + overhead
        $overheadChars = strlen($userMessage) + 200;
        $availableChars = $maxContextChars - $overheadChars;

        if ($availableChars <= 0) {
            return $userMessage;
        }

        // Iterate from newest to oldest
        $sortedDesc = $allMessages->sortByDesc('created_at');
        foreach ($sortedDesc as $msg) {
            $role = $msg->role === 'user' ? 'Người dùng' : 'AI';
            $line = "{$role}: {$msg->content}";
            $lineLen = strlen($line);

            if ($usedChars + $lineLen > $availableChars) {
                // Try to fit a truncated version
                $remaining = $availableChars - $usedChars;
                if ($remaining > 50) {
                    $historyParts[] = mb_substr($line, 0, $remaining).'...';
                }
                break;
            }

            $historyParts[] = $line;
            $usedChars += $lineLen;
        }

        if (empty($historyParts)) {
            return $userMessage;
        }

        // Reverse to chronological order
        $historyParts = array_reverse($historyParts);
        $historyText = implode("\n", $historyParts);

        return "Lịch sử hội thoại trước đó:\n{$historyText}\n\nCâu hỏi mới nhất của người dùng: {$userMessage}";
    }

    /**
     * Clear conversation history from DB and session.
     */
    public function clearHistory()
    {
        $conversationId = session()->get('chatbot_conversation_id');
        if ($conversationId) {
            DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversationId)
                ->delete();
            DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->delete();
        }
        session()->forget('chatbot_conversation_id');
        session()->forget('chatbot_history');

        return response()->json(['ok' => true]);
    }
}
