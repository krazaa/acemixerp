<?php

namespace Modules\FixedAssets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FixedAssets\Enums\AssetTransactionType;

class LifecycleAssetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(AssetTransactionType::class)], 'transaction_date' => ['required', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'], 'new_carrying_amount' => ['nullable', 'numeric', 'min:0'],
            'from_location' => ['nullable', 'string', 'max:192'], 'to_location' => ['nullable', 'string', 'max:192'],
            'offset_account_id' => ['nullable', 'integer', 'exists:accounts,id'], 'reference' => ['nullable', 'string', 'max:128'],
            'funding_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('assets.manage');
    }
}
