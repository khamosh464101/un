<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GozarRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'name_fa' => ['required', 'string', 'max:255'],
            'name_pa' => ['required', 'string', 'max:255'],
            'latitude' => 'required',
            'longitude' => 'required',
            'district_id' => 'required|integer'
        ];
    }
}
