<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatbotAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_guest_can_open_the_chatbot(): void
    {
        $response = $this->get(route('chatbot.index'));

        $response->assertOk();
        $response->assertSee('AI Chatbot');
    }

    #[Test]
    public function a_guest_can_start_and_load_a_conversation(): void
    {
        $createResponse = $this->postJson(route('chatbot.new'));
        $createResponse->assertOk()->assertJsonStructure(['ok', 'conversation_id']);

        $conversationId = $createResponse->json('conversation_id');

        $this->assertDatabaseHas('agent_conversations', [
            'id' => $conversationId,
        ]);

        $this->getJson(route('chatbot.show', ['id' => $conversationId]))
            ->assertOk()
            ->assertJsonPath('id', $conversationId);
    }

    #[Test]
    public function a_guest_cannot_load_an_unowned_conversation(): void
    {
        $conversationId = (string) Str::uuid7();
        $this->postJson(route('chatbot.new'));

        // The row exists, but this browser session does not own it.
        DB::table('agent_conversations')->insert([
            'id' => $conversationId,
            'title' => 'Cuộc trò chuyện riêng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson(route('chatbot.show', ['id' => $conversationId]))
            ->assertNotFound();
    }
}
