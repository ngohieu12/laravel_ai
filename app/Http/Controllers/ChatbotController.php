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
     * Display chatbot interface.
     */
    public function index(Request $request)
    {
        $prefill = $request->query('q', '');
        $aiConfigured = (bool) config('ai.providers.' . config('ai.default') . '.key');
        return view('chatbot.index', compact('prefill', 'aiConfigured'));
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

        // Get or create conversation
        $conversationId = session()->get('chatbot_conversation_id');
        if (!$conversationId) {
            $conversationId = Str::uuid7()->toString();
            DB::table('agent_conversations')->insert([
                'id' => $conversationId,
                'title' => mb_substr($userMessage, 0, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            session()->put('chatbot_conversation_id', $conversationId);
        }

        // Get recent history from DB (last 6 messages)
        $recentMessages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->reverse();

        // Build context with history
        $contextMessage = $userMessage;
        if ($recentMessages->isNotEmpty()) {
            $historyText = "Lịch sử hội thoại trước đó:\n";
            foreach ($recentMessages as $msg) {
                $role = $msg->role === 'user' ? 'Người dùng' : 'AI';
                $historyText .= "{$role}: {$msg->content}\n";
            }
            $contextMessage = "{$historyText}\n\nCâu hỏi mới nhất của người dùng: {$userMessage}";
        }

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
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot error: ' . $e->getMessage());

            return response()->json([
                'reply' => "⚠️ Chatbot chưa được cấu hình AI Provider.\n\nVui lòng thêm API key vào file `.env`, ví dụ:\n\n```\nOPENAI_API_KEY=sk-xxx...\n```\n\nHoặc đổi sang provider khác trong `config/ai.php`.",
                'error' => true,
            ]);
        }
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
