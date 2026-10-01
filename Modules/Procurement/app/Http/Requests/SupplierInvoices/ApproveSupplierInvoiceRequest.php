<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests\SupplierInvoices;

use Illuminate\Foundation\Http\FormRequest;

class ApproveSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('supplierInvoice'));
    }

    public function rules(): array
    {
        return [
            'override_mismatch' => ['sometimes', 'boolean'],
        ];
    }
}
