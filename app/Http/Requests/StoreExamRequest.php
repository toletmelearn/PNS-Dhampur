<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\SecurityHelper;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || 
               auth()->user()->hasRole('principal') || 
               auth()->user()->hasRole('teacher'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (SecurityHelper::containsMaliciousContent($value)) {
                        $fail('The exam name contains potentially malicious content.');
                    }
                }
            ],
            'class_id' => 'required|exists:class_models,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled',
            'duration' => 'nullable|integer|min:15|max:360',
            'total_marks' => 'nullable|integer|min:10|max:1000',
            'passing_marks' => 'nullable|integer|min:0|lte:total_marks',
            'instructions' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Exam name is required.',
            'name.max' => 'Exam name cannot exceed 255 characters.',
            'class_id.required' => 'Class selection is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'date.required' => 'Exam date is required.',
            'date.date' => 'Exam date must be a valid date.',
            'date.after_or_equal' => 'Exam date cannot be in the past.',
            'time.max' => 'Exam time cannot exceed 50 characters.',
            'description.max' => 'Exam description cannot exceed 1000 characters.',
            'status.in' => 'Invalid exam status.',
            'duration.min' => 'Exam duration must be at least 15 minutes.',
            'duration.max' => 'Exam duration cannot exceed 6 hours.',
            'total_marks.min' => 'Total marks must be at least 10.',
            'total_marks.max' => 'Total marks cannot exceed 1000.',
            'passing_marks.lte' => 'Passing marks cannot exceed total marks.',
            'instructions.max' => 'Exam instructions cannot exceed 2000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => SecurityHelper::sanitizeInput($this->name),
            'description' => SecurityHelper::sanitizeInput($this->description),
            'instructions' => SecurityHelper::sanitizeInput($this->instructions),
            'time' => SecurityHelper::sanitizeInput($this->time),
        ]);
    }
}