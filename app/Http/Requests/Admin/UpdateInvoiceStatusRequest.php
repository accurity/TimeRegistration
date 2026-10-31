<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedStatuses = $this->route('invoice')->isDraft()
            ? ['draft', 'open', 'paid']
            : ['draft', 'open', 'paid', 'cancelled'];

        return [
            'status' => ['required', Rule::in($allowedStatuses)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Deze status kan niet worden ingesteld. Een concept annuleer je door het te verwijderen.',
        ];
    }
}
