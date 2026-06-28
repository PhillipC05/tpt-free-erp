<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification\NotificationMessage;
use App\Models\Notification\NotificationPreference;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationEnhancedController extends BaseApiController
{
    protected string $cacheTag = 'notification_messages';

    public function index(Request $request): JsonResponse
    {
        $query = NotificationMessage::query()
            ->where('user_id', Auth::id())
            ->with('template');

        if ($request->has('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = $request->query('per_page', 20);
        $items = $query->orderByDesc('created_at')->paginate(min($perPage, 50));

        return $this->respond([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $notification = NotificationMessage::with('template')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $notification) {
            return $this->respondNotFound();
        }

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $this->respond(['success' => true, 'data' => $notification]);
    }

    public function markRead(int $id): JsonResponse
    {
        $service = new NotificationService;
        $service->markRead(Auth::id(), $id);

        return $this->respondSuccess('Notification marked as read');
    }

    public function markAllRead(): JsonResponse
    {
        $service = new NotificationService;
        $count = $service->markAllRead(Auth::id());

        return $this->respondSuccess("{$count} notifications marked as read");
    }

    public function unreadCount(): JsonResponse
    {
        $service = new NotificationService;
        $count = $service->unreadCount(Auth::id());

        return $this->respond(['success' => true, 'data' => ['count' => $count]]);
    }

    public function destroy(int $id): JsonResponse
    {
        $notification = NotificationMessage::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $notification) {
            return $this->respondNotFound();
        }

        $notification->delete();

        return $this->respondSuccess('Notification deleted');
    }

    public function preferences(): JsonResponse
    {
        $prefs = NotificationPreference::where('user_id', Auth::id())->get();

        return $this->respond(['success' => true, 'data' => $prefs]);
    }

    public function savePreferences(Request $request): JsonResponse
    {

        $prefs = NotificationPreference::updateOrCreate(
            ['user_id' => Auth::id(), 'template_code' => $request->input('template_code')],
            [
                'channels' => $request->input('channels', ['in_app']),
                'email_enabled' => $request->boolean('email_enabled', true),
                'in_app_enabled' => $request->boolean('in_app_enabled', true),
                'webhook_enabled' => $request->boolean('webhook_enabled', false),
                'email_address' => $request->input('email_address'),
            ]
        );

        return $this->respondSuccess('Preferences saved', $prefs);
    }

    public function send(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'user_id' => 'required|exists:users,id',
            'template_code' => 'required|exists:notification_templates,code',
            'data' => 'nullable|array',
            'channels' => 'nullable|array',
            'channels.*' => 'in:email,in_app,webhook',
        ]);
        if ($error) {
            return $error;
        }

        $user = User::findOrFail($request->input('user_id'));
        $service = new NotificationService;
        $messages = $service->send(
            $user,
            $request->input('template_code'),
            $request->input('data', []),
            $request->input('channels')
        );

        return $this->respondCreated($messages, count($messages).' notification(s) sent');
    }
}
