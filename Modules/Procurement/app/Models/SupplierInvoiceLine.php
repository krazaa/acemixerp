<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Models\Account;
use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\Brand;

class SupplierInvoiceLine extends Model
{
    protected $fillable = [
        'brand_id',
        'supplier_invoice_id', 'purchase_order_line_id', 'item_id', 'expense_account_id', 'position',
        'quantity', 'unit_price', 'tax_rate', 'wht_rate',
        'line_subtotal', 'line_tax', 'line_total', 'line_wht_tax', 'line_total_wht_tax',
        'debit_account_id', 'input_tax_account_id',
        'match_result', 'match_note',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'tax_rate' => 'decimal:6',
            'wht_rate' => 'decimal:6',
            'line_subtotal' => 'decimal:4',
            'line_tax' => 'decimal:4',
            'line_total' => 'decimal:4',
            'line_wht_tax' => 'decimal:4',
            'line_total_wht_tax' => 'decimal:4',
            'position' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class)->withTrashed();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function inputTaxAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'input_tax_account_id');
    }

    public function recalculate(): void
    {
        $subtotal = bcmul((string) $this->quantity, (string) $this->unit_price, 4);
        $rate = bcdiv((string) $this->tax_rate, '100', 8);
        $tax = bcmul($subtotal, $rate, 4);
        $lineTotal = bcadd($subtotal, $tax, 4);
        $whtRate = bcdiv((string) $this->wht_rate, '100', 8);
        $withholdingTax = bcmul($lineTotal, $whtRate, 4);

        $this->forceFill([
            'line_subtotal' => $subtotal,
            'line_tax' => $tax,
            'line_total' => $lineTotal,
            'line_wht_tax' => $withholdingTax,
            'line_total_wht_tax' => bcsub($lineTotal, $withholdingTax, 4),
        ])->save();
    }
}
