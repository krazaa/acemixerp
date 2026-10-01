{{-- @extends('layouts.app') --}}
<x-default-layout>
@section('title', 'Record Vendor Quotation')
@section('sub-title')
 <div>
        <div class="text-muted small">
            For RFQ <code>{{ $rfq->number }}</code> — {{ $rfq->purpose }}
        </div>
    </div>
@endsection

@section('toolbar-button')
    <a href="{{ route('procurement.rfqs.show', $rfq) }}" class="btn btn-sm btn-light">
        <i class="fas fa-arrow-left fa-sm"></i> Back to RFQ</a>
@endsection



@if($rfq->vendors->where('status', \Modules\Procurement\Enums\RfqVendorStatus::Invited)->isEmpty()
    && $rfq->vendors->where('status', \Modules\Procurement\Enums\RfqVendorStatus::Submitted)->isEmpty())
    <div class="alert alert-warning">
        All invited vendors have already submitted or declined. To add a new vendor, edit the RFQ first.
    </div>
@endif

<form method="POST"
      action="{{ route('procurement.quotations.store', $rfq) }}"
      novalidate>
    @csrf

    {{-- Vendor + header --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
        <h3 class="card-title">Quotation Header</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="vendor_id">
                        Vendor <span class="text-danger">*</span>
                    </label>
                    <select id="vendor_id" name="vendor_id"
                            class="form-select @error('vendor_id') is-invalid @enderror" data-control="select2" required>
                        <option value="">— Select invited vendor —</option>
                        @foreach($rfq->vendors as $rv)
                            @if($rv->vendor)
                                <option value="{{ $rv->vendor->id }}"
                                        @selected((int) old('vendor_id') === $rv->vendor->id)>
                                    {{ $rv->vendor->code }} — {{ $rv->vendor->name }}
                                    ({{ $rv->status->label() }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Only vendors invited to this RFQ can submit quotations.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="reference">Vendor Reference</label>
                    <input id="reference" name="reference"
                           class="form-control @error('reference') is-invalid @enderror"
                           value="{{ old('reference', $quotation->reference) }}"
                           maxlength="64"
                           placeholder="Vendor's own quote #">
                    @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="currency_code">
                        Currency <span class="text-danger">*</span>
                    </label>
                    <input id="currency_code" name="currency_code" maxlength="3"
                           class="form-control @error('currency_code') is-invalid @enderror"
                           value="{{ old('currency_code', $quotation->currency_code ?? $rfq->currency_code) }}"
                           required>
                    @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Must match the RFQ currency ({{ $rfq->currency_code }}).</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="quoted_at">
                        Quoted On <span class="text-danger">*</span>
                    </label>
                    <input id="quoted_at" name="quoted_at" type="date"
                           class="form-control @error('quoted_at') is-invalid @enderror"
                           value="{{ old('quoted_at', $quotation->quoted_at?->toDateString() ?? now()->toDateString()) }}"
                           required>
                    @error('quoted_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="valid_until">Valid Until</label>
                    <input id="valid_until" name="valid_until" type="date"
                           class="form-control @error('valid_until') is-invalid @enderror"
                           value="{{ old('valid_until', $quotation->valid_until?->toDateString()) }}">
                    @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="lead_time_days">Overall Lead Time (days)</label>
                    <input id="lead_time_days" name="lead_time_days" type="number"
                           min="0" max="365"
                           class="form-control @error('lead_time_days') is-invalid @enderror"
                           value="{{ old('lead_time_days', $quotation->lead_time_days) }}">
                    @error('lead_time_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="2"
                              class="form-control @error('notes') is-invalid @enderror"
                              maxlength="2000">{{ old('notes', $quotation->notes) }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Lines --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
                <h3 class="card-title">Quotation Lines</h3>
            <div class="card-toolbar">
            <span class="text-muted small">Enter rates only for products this vendor quoted. Leave other rates blank.</span>
            </div>
        </div>
        <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="fw-bold fs-6 text-gray-800">
                        <th>Item</th>
                        <th class="text-end">Requested Qty</th>
                        <th style="width: 180px;">Unit Price</th>
                        <th style="width: 140px;">GST %</th>
                        <th style="width: 140px;">WHT %</th>
                        <th style="width: 140px;">Lead (days)</th>
                        <th style="width: 220px;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rfq->lines as $i => $line)
                        @php
                            $existing = old("lines.{$i}", []);
                            $lineItemId = $line->item_id;
                            $lineQty    = (string) $line->quantity;
                        @endphp
                        <tr>
                            <td>

                                {{ $line->item?->name }}
                                <div class="small text-muted">Brand: {{ $line->brand?->name ?? "—" }}</div>
                                <div class="small text-muted">Origin: {{ $line->origin?->name ?? "—" }}</div>
                                @if($line->specification)
                                    <div class="text-muted small">{{ $line->specification }}</div>
                                @endif
                                <input type="hidden" name="lines[{{ $i }}][rfq_line_id]" value="{{ $line->id }}">
                                <input type="hidden" name="lines[{{ $i }}][item_id]" value="{{ $lineItemId }}">
                                <input type="hidden" name="lines[{{ $i }}][quantity]" value="{{ $lineQty }}">
                            </td>
                            <td class="text-end">
                                {{ number_format((float) $line->quantity, 0) }}
                                {{ $line->unit?->name }}
                            </td>
                            <td>
                                <input name="lines[{{ $i }}][unit_price]"
                                       type="number" step="0.0001" min="0"
                                       class="form-control form-control-sm @error("lines.{$i}.unit_price") is-invalid @enderror"
                                       value="{{ $existing['unit_price'] ?? '' }}"
                                       placeholder="Not quoted">
                                @error("lines.{$i}.unit_price")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input name="lines[{{ $i }}][tax_rate]"
                                       type="number" step="0.000001" min="0" max="100"
                                       class="form-control form-control-sm"
                                       value="{{ $existing['tax_rate'] ?? '' }}"
                                       placeholder="0">
                            </td>
                            <td>
                                <input name="lines[{{ $i }}][wht_tax_rate]"
                                       type="number" step="0.000001" min="0" max="100"
                                       class="form-control form-control-sm"
                                       value="{{ $existing['wht_tax_rate'] ?? '' }}"
                                       placeholder="0">
                            </td>
                            <td>
                                <input name="lines[{{ $i }}][lead_time_days]"
                                       type="number" min="0" max="365"
                                       class="form-control form-control-sm"
                                       value="{{ $existing['lead_time_days'] ?? '' }}">
                            </td>
                            <td>
                                <input name="lines[{{ $i }}][notes]"
                                       class="form-control form-control-sm"
                                       maxlength="500"
                                       value="{{ $existing['notes'] ?? '' }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.rfqs.show', $rfq) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Quotation as Draft</button>
    </div>
</form>
{{-- @endsection --}}
</x-default-layout>
