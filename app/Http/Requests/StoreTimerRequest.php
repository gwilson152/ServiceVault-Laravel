<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'billing_rate_id' => 'nullable|exists:billing_rates,id',
            'service_ticket_id' => 'nullable|exists:service_tickets,id',
            'description' => 'nullable|string|max:1000',
            'device_id' => 'nullable|string|max:255',
            'stop_others' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'project_id.exists' => 'The selected project does not exist.',
            'task_id.exists' => 'The selected task does not exist.',
            'billing_rate_id.exists' => 'The selected billing rate does not exist.',
            'service_ticket_id.exists' => 'The selected service ticket does not exist.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ];
    }
}