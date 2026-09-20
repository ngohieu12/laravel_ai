<?php

use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\FavoriteController;
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

// Posts — publicly viewable (guests can read index + show)
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

// Authenticated-only actions (favorites, comments, create/edit/delete)
Route::middleware('auth')->group(function () {
    // Favorites
    Route::post('/posts/{post}/favorite', [FavoriteController::class, 'toggle'])->name('posts.favorite');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');

    // Comments (nested replies + favorites)
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('posts.comments.store');
    Route::post('/posts/{post}/comments/{comment}/favorite', [CommentController::class, 'toggleFavorite'])->name('posts.comments.favorite');

    // Engagement tracking (share buttons report the click before opening the network)
    Route::post('/posts/{post}/shares', [EngagementController::class, 'trackShare'])->name('posts.shares.track');

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

// Admin analytics — engagement insights (views / shares / favorites) & hot topics
Route::middleware(['auth', 'role:admin'])->prefix('admin/analytics')->name('admin.analytics.')->group(function () {
    Route::get('/', [AdminAnalyticsController::class, 'index'])->name('index');
    Route::get('/posts/{post}', [AdminAnalyticsController::class, 'show'])->name('posts.show');
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
