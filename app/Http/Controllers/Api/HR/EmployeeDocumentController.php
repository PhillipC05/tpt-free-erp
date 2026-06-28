<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Documents\Document;
use App\Models\HR\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends BaseApiController
{
    private function getEmployee(): ?Employee
    {
        return Employee::where('user_id', Auth::id())->first();
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return $this->respondError('No employee profile linked', 404);
        }

        $query = Document::query()
            ->where('documentable_type', Employee::class)
            ->where('documentable_id', $employee->id)
            ->orderByDesc('created_at');

        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->query('tag'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%");
            });
        }

        if ($request->has('mime_type')) {
            $query->where('mime_type', 'like', $request->query('mime_type').'%');
        }

        $perPage = $request->query('per_page', 15);
        $documents = $query->paginate(min($perPage, 100));

        $totalSize = Document::where('documentable_type', Employee::class)
            ->where('documentable_id', $employee->id)
            ->sum('file_size');

        return $this->respond([
            'success' => true,
            'data' => $documents->items(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1048576, 2),
            ],
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return $this->respondError('No employee profile linked', 404);
        }

        $error = $this->validate($request->all(), [
            'file' => 'required|file|max:51200',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);
        if ($error) {
            return $error;
        }

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $name = $request->input('name') ?: pathinfo($originalFilename, PATHINFO_FILENAME);

        $path = $file->store('documents/employees/'.$employee->id, 'local');

        $tags = $request->has('category') ? [$request->input('category')] : [];

        $document = Document::create([
            'name' => $name,
            'original_filename' => $originalFilename,
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'documentable_type' => Employee::class,
            'documentable_id' => $employee->id,
            'description' => $request->input('description'),
            'tags' => $tags,
            'uploaded_by' => Auth::id(),
        ]);

        return $this->respondCreated($document, 'Document uploaded');
    }

    public function show(int $id): JsonResponse
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return $this->respondError('No employee profile linked', 404);
        }

        $document = Document::where('id', $id)
            ->where('documentable_type', Employee::class)
            ->where('documentable_id', $employee->id)
            ->first();

        if (! $document) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $document]);
    }

    public function download(int $id): StreamedResponse
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return response()->json(['success' => false, 'message' => 'No employee profile linked'], 404);
        }

        $document = Document::where('id', $id)
            ->where('documentable_type', Employee::class)
            ->where('documentable_id', $employee->id)
            ->first();

        if (! $document || ! Storage::disk('local')->exists($document->storage_path)) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        return Storage::disk('local')->download(
            $document->storage_path,
            $document->original_filename
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return $this->respondError('No employee profile linked', 404);
        }

        $document = Document::where('id', $id)
            ->where('documentable_type', Employee::class)
            ->where('documentable_id', $employee->id)
            ->first();

        if (! $document) {
            return $this->respondNotFound();
        }

        if (Storage::disk('local')->exists($document->storage_path)) {
            Storage::disk('local')->delete($document->storage_path);
        }

        $document->delete();

        return $this->respondSuccess('Document deleted');
    }

    public function categories(): JsonResponse
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return $this->respondError('No employee profile linked', 404);
        }

        $documents = Document::where('documentable_type', Employee::class)
            ->where('documentable_id', $employee->id)
            ->whereNotNull('tags')
            ->get();

        $counts = [];
        foreach ($documents as $doc) {
            foreach ($doc->tags ?? [] as $tag) {
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }

        $categories = collect($counts)->map(fn ($count, $tag) => ['category' => $tag, 'count' => $count])->values();

        return $this->respond(['success' => true, 'data' => $categories]);
    }
}
