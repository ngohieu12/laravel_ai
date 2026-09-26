<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * In-app notifications (chuông 🔔): list interactions on the user's posts
 * and comments, mark them read, jump to the related post.
 */
class NotificationController extends Controller
{
    /**
     * List the authenticated user's notifications (newest first).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->paginate(15)
            ->withQueryString();

        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark one notification as read, then follow its stored target URL.
     */
    public function read(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($this->belongsToListingUser($request, $notification), 404);

        $notification->markAsRead();

        $target = $notification->data['url'] ?? null;

        return $target !== null && is_string($target)
            ? redirect()->to($target)
            : redirect()->route('notifications.index');
    }

    /**
     * Mark every notification of the authenticated user as read.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }

    /**
     * A notification may only be read by the user it was sent to.
     */
    private function belongsToListingUser(Request $request, DatabaseNotification $notification): bool
    {
        $user = $request->user();

        return $notification->notifiable_type === $user->getMorphClass()
            && (string) $notification->notifiable_id === (string) $user->getAuthIdentifier();
    }
}
