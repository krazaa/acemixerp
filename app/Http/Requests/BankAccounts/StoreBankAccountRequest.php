<?php

declare(strict_types=1);

namespace App\Http\Requests\BankAccounts;

use App\Enums\BankAccountType;
use App\Enums\RecordStatus;
use App\Models\BankAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BankAccount::class);
    }

    public function rules(): array
    {
        return [
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'gl_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('bank_accounts', 'code')],
            'name' => ['required', 'string', 'max:128'],
            'account_number' => ['required', 'string', 'max:64'],
            'iban' => ['nullable', 'string', 'max:64'],
            'swift_code' => ['nullable', 'string', 'max:16'],
            'account_type' => ['required', Rule::enum(BankAccountType::class)],
            'currency_code' => ['required', 'string', 'size:3'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
            'is_default' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
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
        if (! $this->has('account_type')) {
            $this->merge(['account_type' => BankAccountType::Current->value]);
        }
        if (! $this->has('status')) {
            $this->merge(['status' => RecordStatus::Active->value]);
        }
    }
}
