<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProvinceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('provinces')->ignore($this?->route('province'))],
            'name_fa' => ['required', 'string', 'max:255', Rule::unique('provinces')->ignore($this?->route('province'))],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('provinces')->ignore($this?->route('province'))],
        ];
    }
}
