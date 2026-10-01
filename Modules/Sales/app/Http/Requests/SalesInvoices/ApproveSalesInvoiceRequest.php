<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests\SalesInvoices;

use Illuminate\Foundation\Http\FormRequest;

class ApproveSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('salesInvoice'));
    }

    public function rules(): array
    {
        return ['override_mismatch' => ['sometimes', 'boolean']];
    }
}
