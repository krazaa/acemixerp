<?php

namespace Modules\Procurement\Http\Requests\VendorPayments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Procurement\Models\VendorPayment;

class StoreVendorPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', VendorPayment::class);
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'payment_date' => ['required', 'date'],
            'billing_month' => ['nullable', 'date_format:Y-m'],
            'currency_code' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in([
                'cash', 'bank_transfer', 'cheque', 'credit_card', 'online_gateway', 'other',
            ])],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'allocations' => ['nullable', 'array'],
            'allocations.*.supplier_invoice_id' => ['required_with:allocations', 'integer', 'exists:supplier_invoices,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'gt:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $allocTotal = collect($this->input('allocations', []))
                ->sum(fn ($a) => (float) ($a['amount'] ?? 0));
            $amount = (float) $this->input('amount', 0);

            if ($allocTotal > $amount + 0.00005) {
                $v->errors()->add(
                    'amount',
                    sprintf(
                        'Allocations (%.4f) exceed the payment amount (%.4f).',
                        $allocTotal,
                        $amount,
                    ),
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency_code')) {
            $this->merge([
                'currency_code' => strtoupper(trim((string) $this->input('currency_code'))),
            ]);
        }
    }
}
