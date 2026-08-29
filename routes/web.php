<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

// Posts CRUD
Route::resource('posts', PostController::class);

// Chatbot
Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
Route::post('/chatbot/chat', [ChatbotController::class, 'chat'])->name('chatbot.chat');
Route::post('/chatbot/clear', [ChatbotController::class, 'clearHistory'])->name('chatbot.clear');
Route::get('/chatbot/{id}', [ChatbotController::class, 'show'])->name('chatbot.show');
Route::post('/chatbot/new', [ChatbotController::class, 'newConversation'])->name('chatbot.new');

// Redirect root to posts
Route::get('/', function () {
    return redirect()->route('posts.index');
});
