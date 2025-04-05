<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerRequest extends FormRequest
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
            'business_name' => ['required', 'string', 'max:255', Rule::unique('partners')->ignore($this?->route('partner'))],
            'address' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', Rule::unique('partners')->ignore($this?->route('partner'))],
            'representative_name' => ['required', 'string', 'max:255'],
            'representative_phone1' => ['required', 'string', 'max:255', Rule::unique('partners')->ignore($this?->route('partner'))],
            'representative_phone2' => ['nullable', 'string', 'max:255', Rule::unique('partners')->ignore($this?->route('partner'))],
            'representative_email' => ['nullable', 'string', 'max:255', Rule::unique('partners')->ignore($this?->route('partner'))],
            'description' => 'nullable',
        ];

    }
}
