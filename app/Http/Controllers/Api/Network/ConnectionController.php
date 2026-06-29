<?php

namespace App\Http\Controllers\Api\Network;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Network\UserConnection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConnectionController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $connections = UserConnection::with(['requester:id,name,email', 'addressee:id,name,email'])
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('addressee_id', $userId);
            })
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $connections->items(),
            'meta' => [
                'current_page' => $connections->currentPage(),
                'last_page' => $connections->lastPage(),
                'per_page' => $connections->perPage(),
                'total' => $connections->total(),
            ],
        ]);
    }

    public function request(Request $request, int $userId): JsonResponse
    {
        if ($userId === $request->user()->id) {
            return $this->respondError('You cannot connect with yourself', 422);
        }

        $existing = UserConnection::where(function ($q) use ($request, $userId) {
            $q->where('requester_id', $request->user()->id)->where('addressee_id', $userId);
        })->orWhere(function ($q) use ($request, $userId) {
            $q->where('requester_id', $userId)->where('addressee_id', $request->user()->id);
        })->first();

        if ($existing) {
            return $this->respondError('A connection request already exists between these users', 422);
        }

        $connection = UserConnection::create([
            'requester_id' => $request->user()->id,
            'addressee_id' => $userId,
            'status' => 'pending',
            'message' => $request->input('message'),
        ]);

        // Notify the addressee about the incoming connection request
        $addressee = User::find($userId);
        if ($addressee) {
            DB::table('notifications')->insert([
                'id'               => \Illuminate\Support\Str::uuid(),
                'type'             => 'connection_request',
                'notifiable_type'  => User::class,
                'notifiable_id'    => $userId,
                'data'             => json_encode([
                    'type'           => 'connection_request',
                    'requester_id'   => $request->user()->id,
                    'requester_name' => $request->user()->name,
                    'connection_id'  => $connection->id,
                    'message'        => $connection->message,
                ]),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        return $this->respondCreated($connection);
    }

    public function accept(Request $request, int $connectionId): JsonResponse
    {
        $connection = UserConnection::where('id', $connectionId)
            ->where('addressee_id', $request->user()->id)
            ->first();

        if (!$connection) {
            return $this->respondNotFound('Connection request not found');
        }

        if ($connection->status !== 'pending') {
            return $this->respondError('Connection request is not in pending status', 422);
        }

        $connection->update(['status' => 'accepted']);

        return $this->respondSuccess('Connection accepted', $connection);
    }

    public function decline(Request $request, int $connectionId): JsonResponse
    {
        $connection = UserConnection::where('id', $connectionId)
            ->where('addressee_id', $request->user()->id)
            ->first();

        if (!$connection) {
            return $this->respondNotFound('Connection request not found');
        }

        $connection->update(['status' => 'declined']);

        return $this->respondSuccess('Connection declined');
    }

    public function destroy(int $connectionId): JsonResponse
    {
        $userId = request()->user()->id;

        $connection = UserConnection::where('id', $connectionId)
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('addressee_id', $userId);
            })
            ->first();

        if (!$connection) {
            return $this->respondNotFound('Connection not found');
        }

        $connection->delete();

        return $this->respondSuccess('Connection removed');
    }
}
