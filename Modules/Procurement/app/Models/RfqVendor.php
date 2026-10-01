<?php

namespace Modules\Procurement\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Procurement\Enums\RfqVendorStatus;

class RfqVendor extends Model
{
    protected $fillable = [
        'request_for_quotation_id', 'vendor_id', 'status',
        'invited_at', 'submitted_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => RfqVendorStatus::class,
            'invited_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'request_for_quotation_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
