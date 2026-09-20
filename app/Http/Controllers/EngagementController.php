<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\PostEngagementTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Records engagement signals fired from the browser (currently: share clicks).
 */
class EngagementController extends Controller
{
    public function __construct(private readonly PostEngagementTracker $tracker) {}

    /**
     * Register a share click for a post.
     */
    public function trackShare(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'max:30'],
        ]);

        $event = $this->tracker->trackShare($request, $post, $validated['platform']);

        return response()->json([
            'ok' => true,
            'shares_count' => (int) $post->fresh()?->shares_count,
            'recorded_at' => $event->created_at?->toIso8601String(),
        ]);
    }
}
