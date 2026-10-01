<?php

declare(strict_types=1);

namespace Modules\Expense\Models;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaimLine extends Model
{
    protected $fillable = [
        'expense_claim_id',
        'line_number',
        'expense_date',
        'expense_account_id',
        'sub_account_id',
        'category',
        'reference',
        'amount',
        'manager_decision',
        'manager_deduction_amount',
        'manager_deduction_reason',
        'manager_rejection_reason',
        'manager_reviewed_by',
        'manager_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:4',
            'manager_reviewed_at' => 'datetime',
            'manager_deduction_amount' => 'decimal:4',
        ];
    }

    public function expenseClaim(): BelongsTo
    {
        return $this->belongsTo(ExpenseClaim::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function subAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sub_account_id');
    }

    public function managerReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_reviewed_by');
    }
}
