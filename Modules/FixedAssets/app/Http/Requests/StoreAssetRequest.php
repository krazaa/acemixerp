<?php

namespace Modules\FixedAssets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\FixedAssets\Models\Asset;

class StoreAssetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:192'], 'description' => ['nullable', 'string'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'], 'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'supplier_invoice_id' => ['nullable', 'integer'], 'acquisition_date' => ['required', 'date'],
            'in_service_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'], 'cost' => ['required', 'numeric', 'gt:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0', 'lt:cost'], 'useful_life_months' => ['required', 'integer', 'min:1', 'max:1200'],
            'location' => ['nullable', 'string', 'max:192'], 'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'asset_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'accumulated_depreciation_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'depreciation_expense_account_id' => ['required', 'integer', 'exists:accounts,id'], 'offset_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Asset::class);
    }
}
