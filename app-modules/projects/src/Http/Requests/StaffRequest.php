<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
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
            'position_title' => ['required', 'string', 'max:255'],
            'personal_email' => ['nullable', 'email', Rule::unique('staff')->ignore($this?->route('staff'))],
            'official_email' => ['required', 'email', Rule::unique('staff')->ignore($this?->route('staff'))],
            'photo' => ['image', 'mimes:jpeg,png,jpg', 'max:1002', 'nullable'],
            'phone1' => ['required', 'string', Rule::unique('staff')->ignore($this?->route('staff'))],
            'phone2' => ['nullable', 'string', Rule::unique('staff')->ignore($this?->route('staff'))],
            'duty_station' => ['required', 'string'],
            'date_of_joining' => 'nullable|date',
            'end_of_contract' => 'nullable|date',
            'gender' => 'required|string',
            'about' => 'nullable',
            'staff_status_id' => 'required',
            'staff_contract_type_id' => 'required'

        ];
    }
}
