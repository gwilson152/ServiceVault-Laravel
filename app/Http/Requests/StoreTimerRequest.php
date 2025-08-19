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
            // Note: project_id validation removed - projects no longer used
            'task_id' => 'nullable|exists:tasks,id',
            'billing_rate_id' => 'nullable|exists:billing_rates,id',
            'ticket_id' => 'nullable|exists:tickets,id',
            'account_id' => 'nullable|exists:accounts,id',
            'user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string|max:1000',
            'device_id' => 'nullable|string|max:255',
            'stop_others' => 'boolean',
            'auto_start' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            // Note: project validation messages removed
            'task_id.exists' => 'The selected task does not exist.',
            'billing_rate_id.exists' => 'The selected billing rate does not exist.',
            'ticket_id.exists' => 'The selected ticket does not exist.',
            'account_id.exists' => 'The selected account does not exist.',
            'user_id.exists' => 'The selected user does not exist.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ];
    }
}
