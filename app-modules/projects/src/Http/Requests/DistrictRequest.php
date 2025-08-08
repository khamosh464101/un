<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class DistrictRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('districts')->where(function ($query) {
                    return $query->where('province_id', $this->province_id);
                })->ignore($this?->route('district')),
            ],
            'is_urban' => ['required', 'boolean', 'max:255'],
            'province_id' => 'required|integer'
        ];
    }
}
