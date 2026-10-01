<?php

declare(strict_types=1);

namespace Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseEvidence extends Model
{
    protected $table = 'expense_evidences';

    protected $fillable = ['expense_claim_id', 'path', 'original_name', 'mime_type', 'size', 'uploaded_by'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(ExpenseClaim::class, 'expense_claim_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
