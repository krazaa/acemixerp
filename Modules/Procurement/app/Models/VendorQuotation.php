<?php

namespace Modules\Procurement\Models;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Procurement\Enums\QuotationStatus;

class VendorQuotation extends Model
{
    protected $fillable = [
        'request_for_quotation_id', 'vendor_id',
        'reference', 'quoted_at', 'valid_until', 'currency_code',
        'subtotal', 'tax_total', 'wht_tax_total', 'total', 'lead_time_days',
        'status', 'notes',
        'submitted_at', 'submitted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'quoted_at' => 'date',
            'valid_until' => 'date',
            'submitted_at' => 'datetime',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'wht_tax_total' => 'decimal:4',
            'total' => 'decimal:4',
            'lead_time_days' => 'integer',
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

    public function lines(): HasMany
    {
        return $this->hasMany(VendorQuotationLine::class)
            ->orderBy('position')->orderBy('id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isAwarded(): bool
    {
        return $this->status === QuotationStatus::Awarded;
    }

    public function recalculateTotals(): void
    {
        $subtotal = '0.0000';
        $tax = '0.0000';
        $withholdingTax = '0.0000';

        foreach ($this->lines()->get() as $line) {
            $subtotal = bcadd($subtotal, (string) $line->line_total, 4);
            $tax = bcadd($tax, (string) $line->tax_amount, 4);
            $withholdingTax = bcadd($withholdingTax, (string) $line->wht_tax_amount, 4);
        }

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'wht_tax_total' => $withholdingTax,
            'total' => bcsub(bcadd($subtotal, $tax, 4), $withholdingTax, 4),
        ])->save();
    }
}
