<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $notifications = auth()->user()
            ->notifications()
            ->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $notifications = auth()->user()
            ->unreadNotifications()
            ->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->unreadNotifications()->count();
        return $this->respondSuccess('Unread count', ['count' => $count]);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->find($id);
        if (!$notification) return $this->respondNotFound();

        $notification->markAsRead();
        return $this->respondSuccess('Notification marked as read');
    }

    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        return $this->respondSuccess('All notifications marked as read');
    }

    public function destroy(int $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->find($id);
        if (!$notification) return $this->respondNotFound();

        $notification->delete();
        return $this->respondSuccess('Notification deleted');
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'preferences' => 'required|array',
        ]);
        if ($error) return $error;

        return $this->respondSuccess('Preferences updated', $request->get('preferences'));
    }
}
