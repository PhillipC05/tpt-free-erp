<?php

namespace App\Http\Requests\Lms;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:lms_courses,code,'.$courseId,
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'type' => 'required|in:online,classroom,blended,workshop',
            'duration_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Course code is required.',
            'code.unique' => 'This course code is already in use.',
            'title.required' => 'Course title is required.',
            'type.required' => 'Course type is required.',
            'type.in' => 'Type must be one of: online, classroom, blended, workshop.',
        ];
    }
}
