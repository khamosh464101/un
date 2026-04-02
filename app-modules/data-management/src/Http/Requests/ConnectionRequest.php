<?php

namespace Modules\DataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConnectionRequest extends FormRequest
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
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'kobo_form_field_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dm_import_format_maps', 'kobo_form_field_name')
                    ->where(fn ($query) => $query->where('project_id', $this->input('project_id')))
                    ->ignore($this->route('map')),
            ],
            'excel_file_column_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dm_import_format_maps', 'excel_file_column_name')
                    ->where(fn ($query) => $query->where('project_id', $this->input('project_id')))
                    ->ignore($this->route('map')),
            ],
        ];

    }
}
