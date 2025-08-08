<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubprojectRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'budget' => ['required', 'numeric' ],
            'announcement_date' => ['required', 'date'],
            'date_of_contract' => ['nullable', 'date'],
            'number_of_months' => ['required', 'numeric'],
            'description' => ['nullable'],
            'partner_id' => ['required', 'integer'],
            'project_id' => ['required', 'integer'],
            'subproject_type_id' => ['required', 'integer'],
        ];

    }
}
