<?php

namespace App\Http\Controllers\Api\Documents;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Documents\Document;
use App\Models\Documents\DocumentFolder;
use App\Models\Documents\DocumentVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        if ($request->filled('documentable_id')) {
            $query->where('documentable_id', $request->query('documentable_id'));
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
            'file' => 'required|file|max:51200', // 50 MB max
            'name' => 'nullable|string|max:255',
            'folder_id' => 'nullable|exists:document_folders,id',
            'documentable_type' => 'nullable|string|max:255',
            'documentable_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        if ($error) {
            return $error;
        }

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $name = $request->input('name') ?: pathinfo($originalFilename, PATHINFO_FILENAME);

        $path = $file->store('documents', 'local');

        $document = Document::create([
            'name' => $name,
            'original_filename' => $originalFilename,
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'folder_id' => $request->input('folder_id'),
            'documentable_type' => $request->input('documentable_type'),
            'documentable_id' => $request->input('documentable_id'),
            'description' => $request->input('description'),
            'tags' => $request->input('tags'),
            'uploaded_by' => $request->user()->id,
        ]);

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

        $nextVersion = $document->version + 1;

        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $document->version,
            'name' => $document->name,
            'original_filename' => $document->original_filename,
            'storage_path' => $document->storage_path,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'description' => $document->description,
            'tags' => $document->tags,
            'uploaded_by' => $document->uploaded_by,
        ]);

        $document->update(array_merge(
            $request->only(['name', 'description', 'tags', 'folder_id']),
            ['version' => $nextVersion],
        ));

        $this->cacheFlush();

        return $this->respondSuccess('Document updated successfully', $document);
    }

    public function destroy(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->respondNotFound('Document not found');
        }

        Storage::disk('local')->delete($document->storage_path);

        $document->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Document deleted successfully');
    }

    public function download(int $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->respondNotFound('Document not found');
        }

        if (!Storage::disk('local')->exists($document->storage_path)) {
            return $this->respondError('File not found on disk', 404);
        }

        return Storage::disk('local')->download(
            $document->storage_path,
            $document->original_filename,
            ['Content-Type' => $document->mime_type]
        );
    }

    public function share(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->respondNotFound('Document not found');
        }

        $error = $this->validate($request->all(), [
            'expires_in_hours' => 'nullable|integer|min:1|max:720',
        ]);

        if ($error) {
            return $error;
        }

        $expiresInHours = $request->input('expires_in_hours', 24);
        $token = Str::random(64);
        $expiresAt = now()->addHours($expiresInHours);

        // Store share token in cache so it can be validated on access
        cache()->put("doc_share_{$token}", [
            'document_id' => $document->id,
            'shared_by' => $request->user()->id,
        ], $expiresAt);

        return $this->respond([
            'success' => true,
            'data' => [
                'document_id' => $document->id,
                'document_name' => $document->name,
                'share_token' => $token,
                'expires_at' => $expiresAt->toIso8601String(),
                'share_url' => url("/api/v1/documents/shared/{$token}"),
            ],
        ]);
    }

    public function sharedDownload(string $token)
    {
        $payload = cache()->get("doc_share_{$token}");

        if (!$payload) {
            return response()->json(['success' => false, 'message' => 'Share link is invalid or has expired'], 404);
        }

        $document = Document::find($payload['document_id']);

        if (!$document || !Storage::disk('local')->exists($document->storage_path)) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        return Storage::disk('local')->download(
            $document->storage_path,
            $document->original_filename,
            ['Content-Type' => $document->mime_type]
        );
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

    public function versions(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->respondNotFound('Document not found');
        }

        $versions = $document->versions()->with('uploader')->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'current_version' => $document->version,
                'versions' => $versions,
            ],
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
