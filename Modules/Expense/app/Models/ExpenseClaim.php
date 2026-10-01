<?php

declare(strict_types=1);

namespace Modules\Expense\Models;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Department;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Expense\Enums\ExpenseClaimStatus;
use Modules\Hr\Models\Employee;

class ExpenseClaim extends Model
{
    protected $fillable = [
        'number', 'employee_id', 'department_id', 'expense_account_id', 'expense_date',
        'amount', 'manager_deduction_amount', 'manager_deduction_reason', 'approved_amount',
        'currency_code', 'description', 'status', 'rejection_reason', 'manager_approved_by', 'manager_approved_at',
        'ceo_approved_by', 'ceo_approved_at', 'reimbursement_bank_account_id',
        'payment_reference', 'reimbursed_at', 'reimbursed_by', 'journal_entry_id',
        'created_by', 'updated_by', 'evidence_text',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:4',
            'manager_deduction_amount' => 'decimal:4',
            'approved_amount' => 'decimal:4',

            'status' => ExpenseClaimStatus::class,
            'manager_approved_at' => 'datetime',
            'ceo_approved_at' => 'datetime',
            'reimbursed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ExpenseEvidence::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseClaimLine::class)->orderBy('line_number');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reimbursementBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'reimbursement_bank_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasManagerApproval(): bool
    {
        return $this->manager_approved_by !== null
            && $this->manager_approved_at !== null
            && $this->approved_amount !== null;
    }

    public function approvedAmount(): string
    {
        return $this->approved_amount ?? $this->amount;
    }
}
