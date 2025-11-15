<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', Rule::unique('projects')->ignore($this?->route('project'))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date' ],
            'code' => ['required', 'string'],
            'estimated_budget' => ['required', 'numeric'],
            'spent_budget' => ['nullable', 'numeric'],
            'description' => 'nullable',
            'logo' => ['image', 'mimes:jpeg,png,jpg', 'max:1002', Rule::requiredIf(!$this?->route('project'))], // 10MB max, only certain file types
            'open_to_survey' => ['boolean'],
            'donor_id' => ['required', 'integer'],
            'project_status_id' => ['required', 'integer'],
            'manager_id' => ['nullable', 'integer'],
            'google_storage_folder' => ['nullable', 'string'],
            'kobo_project_id' => ['required', 'string'],
            
        ];
    }
}
