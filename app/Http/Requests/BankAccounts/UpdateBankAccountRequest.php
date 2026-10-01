<?php

declare(strict_types=1);

namespace App\Http\Requests\BankAccounts;

use App\Enums\BankAccountType;
use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bankAccount'));
    }

    public function rules(): array
    {
        $id = $this->route('bankAccount')->id;
        $bankId = $this->input('bank_id') ?? $this->route('bankAccount')->bank_id;

        return [
            'bank_id' => ['sometimes', 'integer', 'exists:banks,id'],
            'gl_account_id' => ['sometimes', 'integer', 'exists:accounts,id'],
            'code' => ['sometimes', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('bank_accounts', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:128'],
            'account_number' => [
                'sometimes', 'string', 'max:64',
                Rule::unique('bank_accounts', 'account_number')
                    ->ignore($id)
                    ->where('bank_id', $bankId),
            ],
            'iban' => ['nullable', 'string', 'max:64'],
            'swift_code' => ['nullable', 'string', 'max:16'],
            'account_type' => ['sometimes', Rule::enum(BankAccountType::class)],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
            'is_default' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
        if ($this->has('currency_code')) {
            $this->merge(['currency_code' => strtoupper(trim((string) $this->input('currency_code')))]);
        }
    }
}
