<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'kvk_number' => ['nullable', 'string', 'max:50'],
            'btw_number' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'default_payment_term_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_vat_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
