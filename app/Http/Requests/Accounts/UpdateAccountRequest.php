<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('account'));
    }

    public function rules(): array
    {
        $id = $this->route('account')->id;

        return [
            'code' => ['sometimes', 'string', 'max:32', Rule::unique('accounts', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:192'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['sometimes', Rule::enum(AccountType::class)],
            'normal_balance' => ['sometimes', Rule::enum(NormalBalance::class)],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'is_postable' => ['sometimes', 'boolean'],
            'is_cash' => ['sometimes', 'boolean'],
            'is_bank' => ['sometimes', 'boolean'],
            'requires_cost_center' => ['sometimes', 'boolean'],
            'requires_department' => ['sometimes', 'boolean'],
            'requires_party' => ['sometimes', 'boolean'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
        ];
    }
}
