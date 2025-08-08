<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', Rule::unique('programs')->ignore($this?->route('program'))],
            'description' => 'nullable',
            'logo' => ['image', 'mimes:jpeg,png,jpg', 'max:1002', Rule::requiredIf(!$this?->route('program'))], // 10MB max, only certain file types
            'program_status_id' => 'required',
        ];
    }
}
