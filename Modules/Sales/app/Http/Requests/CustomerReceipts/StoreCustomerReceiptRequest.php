<?php

namespace Modules\Sales\Http\Requests\CustomerReceipts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sales\Models\CustomerReceipt;

class StoreCustomerReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CustomerReceipt::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'receipt_date' => ['required', 'date'],
            'currency_code' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in([
                'cash', 'bank_transfer', 'cheque', 'credit_card', 'online_gateway', 'other',
            ])],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'allocations' => ['nullable', 'array'],
            'allocations.*.sales_invoice_id' => ['required_with:allocations', 'integer', 'exists:sales_invoices,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'gt:0'],
        ];
    }
}
