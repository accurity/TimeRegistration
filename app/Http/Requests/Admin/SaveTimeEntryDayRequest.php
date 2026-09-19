<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTimeEntryDayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Accept Dutch decimal notation such as "1,5" from the calendar cells.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('hours'))) {
            $this->merge(['hours' => str_replace(',', '.', trim($this->input('hours')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'description' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn (): bool => $this->hasHours()),
            ],
        ];
    }

    /**
     * Whether the day should hold an entry; empty or zero hours clear it.
     */
    public function hasHours(): bool
    {
        return (float) $this->input('hours') > 0;
    }
}
