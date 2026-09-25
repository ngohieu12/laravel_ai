<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only user directory (danh sách người dùng).
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
            ->withSum('posts as views_sum', 'views_count')
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

        $users = $query->paginate(15)->withQueryString();
        $totals = [
            'users' => User::query()->count(),
            'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'creators' => User::query()->where('role', User::ROLE_CREATOR)->count(),
            'readers' => User::query()->where('role', User::ROLE_USER)->count(),
        ];

        return view('admin.users.index', compact('users', 'totals'));
    }
}
