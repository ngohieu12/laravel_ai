<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

// Auth (guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Posts (auth)
Route::middleware('auth')->group(function () {
    // View: all authenticated users
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

    // Create/Edit/Delete: admin & creator only
    Route::middleware('role:admin,creator')->group(function () {
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    });

    // Delete: admin only
    Route::middleware('role:admin')->group(function () {
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    });
});

// Chatbot (auth)
Route::middleware('auth')->prefix('chatbot')->name('chatbot.')->group(function () {
    Route::get('/', [ChatbotController::class, 'index'])->name('index');
    Route::post('/chat', [ChatbotController::class, 'chat'])->name('chat');
    Route::post('/clear', [ChatbotController::class, 'clearHistory'])->name('clear');
    Route::get('/{id}', [ChatbotController::class, 'show'])->name('show');
    Route::post('/new', [ChatbotController::class, 'newConversation'])->name('new');
});

// Redirect root to posts
Route::get('/', function () {
    return redirect()->route('posts.index');
});
