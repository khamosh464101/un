<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', Rule::unique('activities')->ignore($this?->route('activity'))],
            'activity_number' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date' ],
            'description' => 'nullable',
            'project_id' => ['required', 'integer'],
            'responsibles_id' => ['required', 'array'],
            'activity_status_id' => ['required', 'integer'],
            'activity_type_id' => ['nullable', 'integer'],
        ];
    }
}
