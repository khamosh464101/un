<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'personal_email' => ['nullable', 'email', Rule::unique('staff')->ignore(auth()->user()->staff->id)],
            'photo' => ['image', 'mimes:jpeg,png,jpg', 'max:1002'],
            'phone2' => ['nullable', 'string', Rule::unique('staff')->ignore(auth()->user()->staff->id)],
            'about' => 'nullable',
            'password' => ['nullable', 'string'],
        ];
    }
}
