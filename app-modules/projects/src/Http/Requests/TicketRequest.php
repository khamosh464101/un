<?php

namespace Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', Rule::unique('tickets')->ignore($this?->route('ticket'))],
            'ticket_number' => ['nullable', 'string'],
            'description' => ['nullable'],
            'start_date' => 'required|date',
            'deadline' => 'required|date',
            'order' => ['nullable', 'integer'],
            'owner_id' => ['required', 'integer'],
            'responsible_id' => ['required', 'integer'],
            'ticket_status_id' => ['required', 'integer'],
            'ticket_priority_id' => ['required', 'integer'],
            'activity_id' => 'required|integer',
        ];


    }

    public function prepareForValidation()
    {
        $this->merge([
            'owner_id' => auth()->id()
        ]);
    }

}
