@php($i = $index)
@php($a = is_array($allocation) ? $allocation : [])
@php($invoice = $a['invoice'] ?? null)
<div class="allocation-row border rounded p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small mb-1">Invoice</label>
            <div class="form-control form-control-sm bg-white" readonly>
                @if($invoice)
                    <code>{{ $invoice->number }}</code>
                    · {{ $invoice->vendor_invoice_number }}
                    · Due {{ $invoice->due_date->format('Y-m-d') }}
                @else
                    <span class="text-muted">— select an invoice —</span>
                @endif
            </div>
            <input type="hidden" name="allocations[{{ $i }}][supplier_invoice_id]"
                   value="{{ $a['supplier_invoice_id'] ?? ($invoice?->id ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Outstanding</label>
            <div class="form-control form-control-sm bg-white text-end" readonly>
                @if($invoice)
                    {{ number_format((float) $invoice->outstanding(), 4) }}
                @else
                    —
                @endif
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Allocate <span class="text-danger">*</span></label>
            <input name="allocations[{{ $i }}][amount]"
                   type="number" step="0.0001" min="0.0001"
                   class="form-control form-control-sm allocation-amount"
                   value="{{ $a['amount'] ?? '' }}"
                   @if($invoice) max="{{ $invoice->outstanding() }}" @endif
                   required>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="paymentRemoveAllocation(this)" title="Remove">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</div>
