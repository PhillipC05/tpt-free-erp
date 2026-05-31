<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

abstract class BaseApiController extends Controller
{
    protected ?Model $model;
    protected array $defaultIncludes = [];
    protected array $validationRules = [];
    protected array $validationMessages = [];

    /** Override in subclass to enable tag-based cache invalidation on mutations. */
    protected string $cacheTag = '';

    /** Default TTL in seconds for cached responses (30 minutes). */
    protected int $cacheTtl = 1800;

    public function __construct(?Model $model = null)
    {
        $this->model = $model;
    }

    protected function respond(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($data, $status, $headers);
    }

    protected function respondSuccess(string $message = 'Operation successful', mixed $data = null): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->respond($response);
    }

    protected function respondError(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors) {
            $response['errors'] = $errors;
        }
        return $this->respond($response, $status);
    }

    protected function respondCreated(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->respond($response, 201);
    }

    protected function respondNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->respondError($message, 404);
    }

    protected function respondValidationError(array $errors): JsonResponse
    {
        return $this->respondError('Validation failed', 422, $errors);
    }

    /**
     * Cache-aside helper. Falls back to no-cache when cache tags are unsupported
     * (e.g., file/database driver) so the app works without Redis.
     */
    protected function cacheRemember(string $key, callable $callback, ?int $ttl = null, ?string $tag = null): mixed
    {
        $ttl ??= $this->cacheTtl;
        $tag ??= $this->cacheTag;

        try {
            if ($tag) {
                return Cache::tags([$tag])->remember($key, $ttl, $callback);
            }
            return Cache::remember($key, $ttl, $callback);
        } catch (\BadMethodCallException) {
            // Cache driver doesn't support tags — execute without caching.
            return $callback();
        }
    }

    /** Flush all cached data for a tag. No-ops gracefully when unsupported. */
    protected function cacheFlush(?string $tag = null): void
    {
        $tag ??= $this->cacheTag;
        if (!$tag) return;

        try {
            Cache::tags([$tag])->flush();
        } catch (\BadMethodCallException) {
            // Not supported by driver — no-op.
        }
    }

    protected function validate(array $data, array $rules = [], array $messages = []): ?JsonResponse
    {
        $rules = $rules ?: $this->validationRules;
        $messages = $messages ?: $this->validationMessages;

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return $this->respondValidationError($validator->errors()->toArray());
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->model->query();

        // Apply pagination
        $perPage = $request->query('per_page', 15);
        $items = $query->paginate(min($perPage, 100));

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

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $item = $this->model->create($request->all());
        $this->cacheFlush();
        return $this->respondCreated($item);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->model->find($id);

        if (!$item) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->model->find($id);

        if (!$item) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $item->update($request->all());
        $this->cacheFlush();
        return $this->respondSuccess('Updated successfully', $item);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = $this->model->find($id);

        if (!$item) {
            return $this->respondNotFound();
        }

        $item->delete();
        $this->cacheFlush();
        return $this->respondSuccess('Deleted successfully');
    }
}