<?php

namespace App\Http\Requests\Accounts;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Enums\RecordStatus;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Account::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('accounts', 'code')],
            'name' => ['required', 'string', 'max:192'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'normal_balance' => ['nullable', Rule::enum(NormalBalance::class)],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'is_postable' => ['sometimes', 'boolean'],
            'is_cash' => ['sometimes', 'boolean'],
            'is_bank' => ['sometimes', 'boolean'],
            'requires_cost_center' => ['sometimes', 'boolean'],
            'requires_department' => ['sometimes', 'boolean'],
            'requires_party' => ['sometimes', 'boolean'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaults = [
            'is_postable' => true,
            'is_cash' => false,
            'is_bank' => false,
            'requires_cost_center' => false,
            'requires_department' => false,
            'requires_party' => false,
            'status' => RecordStatus::Active->value,
        ];
        $merge = [];
        foreach ($defaults as $k => $v) {
            if (! $this->has($k)) {
                $merge[$k] = $v;
            }
        }
        if ($merge) {
            $this->merge($merge);
        }
    }
}
