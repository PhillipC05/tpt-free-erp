<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class BaseApiController extends Controller
{
    protected Model $model;
    protected array $defaultIncludes = [];
    protected array $validationRules = [];
    protected array $validationMessages = [];

    public function __construct(?Model $model = null)
    {
        $this->model = $model;
    }

    protected function respond($data, int $status = 200, array $headers = []): JsonResponse
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
        return $this->respondSuccess($message, $data);
    }

    protected function respondNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->respondError($message, 404);
    }

    protected function respondValidationError(array $errors): JsonResponse
    {
        return $this->respondError('Validation failed', 422, $errors);
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
        $perPage = $request->get('per_page', 15);
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
        return $this->respondSuccess('Updated successfully', $item);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = $this->model->find($id);

        if (!$item) {
            return $this->respondNotFound();
        }

        $item->delete();
        return $this->respondSuccess('Deleted successfully');
    }
}