<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Lms\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends BaseApiController
{
    protected string $cacheTag = 'lms_courses';
    protected int $cacheTtl = 3600;

    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:lms_courses,code',
        'title' => 'required|string|max:200',
        'description' => 'nullable|string',
        'type' => 'required|in:online,classroom,blended',
        'duration_hours' => 'nullable|numeric|min:0',
        'cost' => 'nullable|numeric|min:0',
        'is_active' => 'boolean',
    ];

    protected array $validationMessages = [
        'code.required' => 'Course code is required.',
        'code.unique' => 'This course code is already in use.',
        'title.required' => 'Course title is required.',
        'type.required' => 'Course type is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Course());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:lms_courses,code',
        ]));
        if ($error) return $error;

        $course = Course::create($request->all());
        return $this->respondCreated($course, 'Course created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $course = Course::find($id);
        if (!$course) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:lms_courses,code,' . $id,
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'type' => 'required|in:online,classroom,blended',
            'duration_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        if ($error) return $error;

        $course->update($request->all());
        return $this->respondSuccess('Course updated', $course->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Course::query();

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

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

    public function enrollments(int $id): JsonResponse
    {
        $course = Course::with('enrollments')->find($id);
        if (!$course) return $this->respondNotFound();

        return $this->respond([
            'success' => true,
            'data' => $course->enrollments,
        ]);
    }
}