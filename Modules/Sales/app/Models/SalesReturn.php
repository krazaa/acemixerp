<?php

namespace Modules\Sales\Models;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesReturn extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['return_date' => 'date', 'approved_at' => 'datetime', 'received_at' => 'datetime', 'inspected_at' => 'datetime', 'accepted_at' => 'datetime', 'rejected_at' => 'datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesReturnLine::class);
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(SalesCreditNote::class);
    }
}
