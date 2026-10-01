<?php

namespace Modules\Procurement\Models;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_type',
        'payment_id',
        'payment_entry_id',
        'allocatable_type',
        'allocatable_id',
        'amount',
        'allocated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    // ─── Payment source (polymorphic) ───────────────────────────────

    public function payment(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'payment_type', 'payment_id');
    }

    public function paymentEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'payment_entry_id');
    }

    // ─── What's being settled ───────────────────────────────────────

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Actor ──────────────────────────────────────────────────────

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
