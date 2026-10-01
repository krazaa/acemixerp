<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_type', 'payment_id', 'payment_entry_id', 'allocatable_type', 'allocatable_id',
        'amount', 'allocated_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:4'];
    }

    public function paymentEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'payment_entry_id');
    }

    public function payment(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'payment_type', 'payment_id');
    }

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
