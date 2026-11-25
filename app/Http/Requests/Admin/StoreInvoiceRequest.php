<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends FormRequest
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
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'period_start' => ['required', 'date_format:Y-m'],
            'period_end' => ['required', 'date_format:Y-m', 'after_or_equal:period_start'],
            'line_description' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['required', 'string', 'max:255', 'unique:invoices,invoice_number'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['period_start', 'period_end']) || $this->filled('line_description')) {
                    return;
                }

                if (Setting::current()->spansMultipleVatPeriods($this->periodStart(), $this->periodEnd())) {
                    $validator->errors()->add('line_description', 'De periode loopt over meer dan één btw-periode. Vul in wat er op de factuurregel moet staan.');
                }
            },
        ];
    }

    public function periodStart(): Carbon
    {
        return Carbon::createFromFormat('!Y-m', $this->input('period_start'));
    }

    public function periodEnd(): Carbon
    {
        return Carbon::createFromFormat('!Y-m', $this->input('period_end'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice_number.unique' => 'Dit factuurnummer is al in gebruik voor een andere factuur.',
            'period_end.after_or_equal' => 'De eindmaand mag niet voor de beginmaand liggen.',
        ];
    }
}
