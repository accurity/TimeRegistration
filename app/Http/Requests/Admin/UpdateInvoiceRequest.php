<?php

namespace App\Http\Requests\Admin;

use App\Models\Invoice;
use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInvoiceRequest extends FormRequest
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
            'invoice_number' => ['required', 'string', 'max:255', Rule::unique('invoices', 'invoice_number')->ignore($this->route('invoice'))],
            'line_description' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice_number.unique' => 'Dit factuurnummer is al in gebruik voor een andere factuur.',
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Invoice $invoice */
                $invoice = $this->route('invoice');

                if ($this->filled('line_description')) {
                    return;
                }

                if (Setting::current()->spansMultipleVatPeriods($invoice->period_start, $invoice->period_end)) {
                    $validator->errors()->add('line_description', 'De periode loopt over meer dan één btw-periode. Vul in wat er op de factuurregel moet staan.');
                }
            },
        ];
    }
}
