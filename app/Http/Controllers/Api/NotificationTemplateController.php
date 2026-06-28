<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController extends BaseApiController
{
    protected string $cacheTag = 'notification_templates';

    protected array $validationRules = [
        'code' => 'required|string|max:100|unique:notification_templates,code',
        'name' => 'required|string|max:200',
        'subject' => 'nullable|string|max:500',
        'body' => 'required|string',
        'html_body' => 'nullable|string',
        'default_channels' => 'required|array',
        'default_channels.*' => 'in:email,in_app,webhook',
        'variables' => 'nullable|array',
        'category' => 'nullable|string|max:100',
        'is_active' => 'nullable|boolean',
    ];

    protected array $validationMessages = [
        'code.required' => 'Template code is required.',
        'code.unique' => 'This template code already exists.',
        'name.required' => 'Template name is required.',
        'body.required' => 'Template body is required.',
    ];

    public function __construct()
    {
        parent::__construct(new NotificationTemplate);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['is_active'] = $data['is_active'] ?? true;

        $template = NotificationTemplate::create($data);

        return $this->respondCreated($template, 'Template created');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::find($id);
        if (! $template) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:100|unique:notification_templates,code,'.$id,
            'name' => 'required|string|max:200',
            'subject' => 'nullable|string|max:500',
            'body' => 'required|string',
            'html_body' => 'nullable|string',
            'default_channels' => 'required|array',
            'default_channels.*' => 'in:email,in_app,webhook',
            'variables' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);
        if ($error) {
            return $error;
        }

        $template->update($request->all());

        return $this->respondSuccess('Template updated', $template->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = NotificationTemplate::query();

        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('category')->orderBy('name')->get();

        return $this->respond(['success' => true, 'data' => $items]);
    }

    public function show(int $id): JsonResponse
    {
        $template = NotificationTemplate::find($id);
        if (! $template) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $template]);
    }

    public function destroy(int $id): JsonResponse
    {
        $template = NotificationTemplate::find($id);
        if (! $template) {
            return $this->respondNotFound();
        }

        $template->delete();

        return $this->respondSuccess('Template deleted');
    }

    public function preview(int $id, Request $request): JsonResponse
    {
        $template = NotificationTemplate::find($id);
        if (! $template) {
            return $this->respondNotFound();
        }

        $rendered = $template->render($request->all());

        return $this->respond(['success' => true, 'data' => $rendered]);
    }
}
