<?php

namespace App\Http\Controllers;

use App\Http\Requests\BanUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Admin-only user management (danh sách, thêm, sửa, đổi quyền, khóa/mở, xóa).
 */
class AdminUserController extends Controller
{
    /**
     * List every account with its role and activity counters.
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount([
                'posts',
                'posts as published_posts_count' => fn ($q) => $q->where('is_published', true),
                'comments',
                'favorites',
                'pinnedPosts',
            ])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && in_array($request->input('role'), User::ROLES, true)) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            match ($request->input('status')) {
                'banned' => $query->where('is_banned', true),
                'active' => $query->where('is_banned', false),
                default => null,
            };
        }

        $users = $query->paginate(15)->withQueryString();
        $totals = [
            'users' => User::query()->count(),
            'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'creators' => User::query()->where('role', User::ROLE_CREATOR)->count(),
            'readers' => User::query()->where('role', User::ROLE_USER)->count(),
            'banned' => User::query()->where('is_banned', true)->count(),
        ];

        return view('admin.users.index', compact('users', 'totals'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Đã tạo người dùng mới thành công.');
    }

    /**
     * Display the specified user (profile + their posts/comments).
     */
    public function show(User $user): View
    {
        $user->loadCount([
            'posts',
            'posts as published_posts_count' => fn ($q) => $q->where('is_published', true),
            'comments',
            'favorites',
            'pinnedPosts',
        ]);

        $recentPosts = $user->posts()
            ->with('tags')
            ->withCount('comments')
            ->latest()
            ->take(5)
            ->get();

        $recentComments = $user->comments()
            ->with('post')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.users.show', compact('user', 'recentPosts', 'recentComments'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        // Prevent the last admin from being demoted.
        if ($user->isAdmin() && $data['role'] !== User::ROLE_ADMIN) {
            $adminCount = User::query()->where('role', User::ROLE_ADMIN)->where('id', '!=', $user->id)->count();
            if ($adminCount === 0) {
                return back()->withErrors(['role' => 'Không thể thay đổi quyền của quản trị viên cuối cùng.']);
            }
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Đã cập nhật thông tin người dùng thành công.');
    }

    /**
     * Ban the user.
     */
    public function ban(BanUserRequest $request, User $user): RedirectResponse
    {
        // Defense in depth — never ban yourself even if form bypasses the authorize rule.
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->withErrors(['email' => 'Bạn không thể tự khóa tài khoản của mình.']);
        }

        $user->ban($request->input('ban_reason'));

        return redirect()->route('admin.users.index')
            ->with('success', 'Đã khóa tài khoản thành công.');
    }

    /**
     * Unban the user.
     */
    public function unban(User $user): RedirectResponse
    {
        abort_unless($user->isBanned(), 404);

        $user->unban();

        return redirect()->route('admin.users.index')
            ->with('success', 'Đã mở khóa tài khoản thành công.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Do not let an admin delete their own account.
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->withErrors(['email' => 'Bạn không thể tự xóa tài khoản của mình.']);
        }

        // Do not remove the last administrator account.
        if ($user->isAdmin() && User::query()->where('role', User::ROLE_ADMIN)->where('id', '!=', $user->id)->count() === 0) {
            return back()->withErrors(['email' => 'Không thể xóa quản trị viên cuối cùng.']);
        }

        // Detach user's relations before deleting to avoid FK errors.
        $user->favorites()->detach();
        $user->pinnedPosts()->detach();
        $user->commentFavorites()->detach();
        $user->comments()->delete();
        // Reassign posts? Simpler: delete posts as well. To keep content, you could
        // reassign them to a "system" account — here we match current delete behavior.
        $user->posts()->each(function ($post) {
            $post->tags()->detach();
            $post->delete();
        });

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Đã xóa người dùng thành công.');
    }
}
