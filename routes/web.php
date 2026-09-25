<?php

use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CreatorAnalyticsController;
use App\Http\Controllers\DashboardPostController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Auth (guest)
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ---------------------------------------------------------------------------
// Frontend (public reading surface): view, search and share — no management
// ---------------------------------------------------------------------------
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::post('/posts/{post}/shares', [EngagementController::class, 'trackShare'])->name('posts.shares.track');

// ---------------------------------------------------------------------------
// Logged-in readers: favorite, comment, pin (ghim bài)
// ---------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('/posts/{post}/favorite', [FavoriteController::class, 'toggle'])->name('posts.favorite');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');

    Route::post('/posts/{post}/pin', [PinController::class, 'toggle'])->name('posts.pin');
    Route::get('/pins', [PinController::class, 'index'])->name('pins.index');

    // Comments (nested replies + favorites)
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('posts.comments.store');
    Route::post('/posts/{post}/comments/{comment}/favorite', [CommentController::class, 'toggleFavorite'])->name('posts.comments.favorite');
});

// ---------------------------------------------------------------------------
// Dashboard (backend): post management + own analytics for creators & admins
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin,creator'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/posts', [DashboardPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/create', [DashboardPostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [DashboardPostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [DashboardPostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [DashboardPostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [DashboardPostController::class, 'destroy'])->name('posts.destroy');

    Route::get('/analytics', [CreatorAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/posts/{post}', [CreatorAnalyticsController::class, 'show'])->name('analytics.posts.show');
});

// ---------------------------------------------------------------------------
// Admin: global analytics, categories, tags, users
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/posts/{post}', [AdminAnalyticsController::class, 'show'])->name('analytics.posts.show');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::delete('/tags/unused', [TagController::class, 'destroyUnused'])->name('tags.destroy-unused');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});

// ---------------------------------------------------------------------------
// Chatbot — available to guests as well as authenticated users.
// Read-only chatbot features do not require an account; conversations are kept
// private to the current browser session by ChatbotController.
// ---------------------------------------------------------------------------
Route::prefix('chatbot')->name('chatbot.')->group(function () {
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
