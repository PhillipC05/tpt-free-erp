<?php

namespace App\Http\Controllers\Api\Documents;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Documents\Document;
use App\Models\Documents\DocumentFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends BaseApiController
{
    protected string $cacheTag = 'documents';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = Document::query();

        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->query('folder_id'));
        }

        if ($request->filled('documentable_type')) {
            $query->where('documentable_type', $request->query('documentable_type'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $documents = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $documents->items(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:255',
            'original_filename' => 'required|string|max:255',
            'storage_path' => 'required|string|max:500',
            'mime_type' => 'required|string|max:100',
            'file_size' => 'required|integer|min:0',
            'folder_id' => 'nullable|exists:document_folders,id',
            'documentable_type' => 'nullable|string|max:255',
            'documentable_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        if ($error) {
            return $error;
        }

        $document = Document::create(array_merge($request->all(), [
            'uploaded_by' => $request->user()->id,
        ]));

        $this->cacheFlush();

        return $this->respondCreated($document);
    }

    public function show(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->respondNotFound('Document not found');
        }

        return $this->respond(['success' => true, 'data' => $document]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->respondNotFound('Document not found');
        }

        $error = $this->validate($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'folder_id' => 'nullable|exists:document_folders,id',
        ]);

        if ($error) {
            return $error;
        }

        $document->update($request->only(['name', 'description', 'tags', 'folder_id']));
        $this->cacheFlush();

        return $this->respondSuccess('Document updated successfully', $document);
    }

    public function destroy(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->respondNotFound('Document not found');
        }

        $document->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Document deleted successfully');
    }

    public function download(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->respondNotFound('Document not found');
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'id' => $document->id,
                'name' => $document->name,
                'original_filename' => $document->original_filename,
                'storage_path' => $document->storage_path,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
            ],
        ]);
    }

    public function folders(): JsonResponse
    {
        $folders = DocumentFolder::with(['children', 'children.children'])
            ->whereNull('parent_id')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => $folders,
        ]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:document_folders,id',
        ]);

        if ($error) {
            return $error;
        }

        $folder = DocumentFolder::create([
            'name' => $request->input('name'),
            'parent_id' => $request->input('parent_id'),
            'created_by' => $request->user()->id,
        ]);

        return $this->respondCreated($folder);
    }
}
