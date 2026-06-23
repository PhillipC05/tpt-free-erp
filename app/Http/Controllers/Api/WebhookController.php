<?php

namespace App\Http\Controllers\Api;

use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends BaseApiController
{
    public function __construct(private readonly WebhookService $webhookService)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $webhooks = Webhook::where('user_id', $request->user()->id)
            ->withCount('deliveries')
            ->paginate(20);

        return $this->respond(['success' => true, 'data' => $webhooks->items(), 'meta' => [
            'total'        => $webhooks->total(),
            'current_page' => $webhooks->currentPage(),
            'last_page'    => $webhooks->lastPage(),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'url'    => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ]);
        if ($error) return $error;

        $webhook = Webhook::create([
            'user_id'   => $request->user()->id,
            'url'       => $request->url,
            'secret'    => Str::random(32),
            'events'    => $request->events,
            'is_active' => true,
        ]);

        return $this->respondCreated($webhook);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$webhook) return $this->respondNotFound();

        return $this->respondSuccess('Webhook retrieved', $webhook);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$webhook) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'url'      => 'sometimes|url|max:500',
            'events'   => 'sometimes|array|min:1',
            'is_active' => 'sometimes|boolean',
        ]);
        if ($error) return $error;

        $webhook->update($request->only('url', 'events', 'is_active'));
        return $this->respondSuccess('Webhook updated', $webhook);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$webhook) return $this->respondNotFound();

        $webhook->delete();
        return $this->respondSuccess('Webhook deleted');
    }

    public function test(Request $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$webhook) return $this->respondNotFound();

        $delivery = $this->webhookService->testFire($webhook);

        return $this->respondSuccess('Test delivery sent', [
            'delivery_id' => $delivery->id,
            'status'      => $delivery->status,
        ]);
    }

    public function deliveries(Request $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$webhook) return $this->respondNotFound();

        $deliveries = $webhook->deliveries()
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->respond(['success' => true, 'data' => $deliveries->items(), 'meta' => [
            'total' => $deliveries->total(),
        ]]);
    }
}
