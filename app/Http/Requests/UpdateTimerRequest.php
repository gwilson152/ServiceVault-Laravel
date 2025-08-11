<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimerRequest extends FormRequest
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
            'description' => 'nullable|string|max:1000',
            'status' => ['nullable', Rule::in(['running', 'paused', 'stopped'])],
            'device_id' => 'nullable|string|max:255',
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
            'description.max' => 'The description must not exceed 1000 characters.',
            'status.in' => 'The timer status must be running, paused, or stopped.',
        ];
    }
}