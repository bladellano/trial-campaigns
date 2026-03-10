<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'contact_list_id' => ['required', 'integer', 'exists:contact_lists,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'status' => ['nullable', 'string', 'in:draft,sending,sent'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contact_list_id.exists' => 'The specified contact list does not exist.',
            'scheduled_at.after' => 'The scheduled date must be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default status to 'draft'
        $this->merge(['status' => 'draft']);
    }
}
