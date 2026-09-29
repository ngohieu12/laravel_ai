<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\VietnameseText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backing endpoint for the @mention autocomplete used by the comment box and
 * the post editor.
 *
 * Suggestions are matched on the mention handle (`username`) as well as on
 * the display name, so typing `@minh` finds "Minh Anh" (`@minh-anh`) too.
 * The response carries only what the dropdown renders — never the email.
 */
class MentionController extends Controller
{
    /** Maximum number of suggestions shown in the dropdown. */
    public const LIMIT = 8;

    /** How many accounts are scanned before the display-name match is applied. */
    public const SEARCH_POOL = 100;

    /**
     * Suggest accounts matching the given fragment of a mention.
     */
    public function index(Request $request): JsonResponse
    {
        $needle = mb_strtolower(trim(VietnameseText::toAscii($request->string('q')->toString())), 'UTF-8');

        $users = User::query()
            ->where('is_banned', false)
            ->when($needle !== '', function ($builder) use ($needle): void {
                $builder->where('username', 'like', '%'.$needle.'%');
            })
            ->orderBy('name')
            // The display name is matched in PHP (diacritic folding), so a
            // wider window is scanned before the dropdown limit is applied.
            ->limit($needle === '' ? self::LIMIT : self::SEARCH_POOL)
            ->get(['id', 'name', 'username', 'role']);

        if ($needle !== '') {
            $users = $users
                ->filter(fn (User $user): bool => str_contains(
                    mb_strtolower(VietnameseText::toAscii((string) $user->name), 'UTF-8'),
                    $needle
                ) || str_contains(mb_strtolower((string) $user->name, 'UTF-8'), $needle))
                ->take(self::LIMIT)
                ->values();
        }

        return response()->json([
            'data' => $users->map(fn (User $user): array => [
                'username' => $user->username,
                'name' => $user->name,
                'role' => $user->roleLabel(),
            ])->values(),
        ]);
    }
}
