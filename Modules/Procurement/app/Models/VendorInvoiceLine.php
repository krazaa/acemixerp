<?php

namespace Modules\Procurement\Models;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Procurement\Database\Factories\VendorInvoiceLineFactory;

class VendorInvoiceLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['vendor_invoice_id', 'position', 'tax_rate', 'line_subtotal', 'line_tax', 'line_total', 'line_wht_tax', 'line_total_wht_tax', 'debit_account_id', 'input_tax_account_id', 'description'];

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function inputTaxAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'input_tax_account_id');
    }

    // protected static function newFactory(): VendorInvoiceLineFactory
    // {
    //     // return VendorInvoiceLineFactory::new();
    // }
}
