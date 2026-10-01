@php($existingLines = old('lines', $delivery->lines?->toArray() ?? []))

@if(! $delivery->exists && $salesOrder && empty($existingLines))
    @php($existingLines = $salesOrder->lines->map(fn ($l) => [
        'sales_order_line_id' => $l->id,
        'item_id'             => $l->item_id,
        'unit_id'             => $l->unit_id,
        'quantity'            => $l->openQuantity(),
        'unit_price'          => (string) $l->unit_price,
        'notes'               => null,
    ])->filter(fn ($r) => bccomp($r['quantity'], '0', 4) > 0)->values()->all())
@endif

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Delivery Header</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @if($salesOrder)
                <div class="col-md-6">
                    <label class="form-label">Sales Order</label>
                    <div class="form-control bg-light" readonly>
                        <code>{{ $salesOrder->number }}</code> — {{ $salesOrder->customer?->name }}
                    </div>
                    <input type="hidden" name="sales_order_id" value="{{ $salesOrder->id }}">
                </div>
            @else
                <div class="col-md-6">
                    <label class="form-label" for="sales_order_id">Sales Order <span class="text-danger">*</span></label>
                    <select id="sales_order_id" name="sales_order_id" class="form-select" required>
                        <option value="">— Select order —</option>
                        @foreach($openOrders as $o)
                            <option value="{{ $o->id }}" @selected((int) old('sales_order_id') === $o->id)>
                                {{ $o->number }} — {{ $o->customer?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-2">
                <label class="form-label" for="delivery_date">Delivery Date <span class="text-danger">*</span></label>
                <input id="delivery_date" name="delivery_date" type="date" class="form-control"
                       value="{{ old('delivery_date', $delivery->delivery_date?->toDateString() ?? now()->toDateString()) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="expected_date">Expected</label>
                <input id="expected_date" name="expected_date" type="date" class="form-control"
                       value="{{ old('expected_date', $delivery->expected_date?->toDateString()) }}">
            </div>
            {{-- <div class="col-md-2">
                <label class="form-label" for="reference">Reference</label>
                <input id="reference" name="reference" class="form-control"
                       value="{{ old('reference', $delivery->reference) }}" maxlength="64">
            </div> --}}
            {{-- <div class="col-md-4">
                <label class="form-label" for="carrier">Carrier</label>
                <input id="carrier" name="carrier" class="form-control"
                       value="{{ old('carrier', $delivery->carrier) }}" maxlength="128">
            </div> --}}

            <div class="col-md-4">
                <label class="form-label" for="vendor_id">Transport Vendor</label>
                <select id="vendor_id" name="vendor_id"
                        class="form-select @error('vendor_id') is-invalid @enderror">
                    <option value="">— Own fleet / not applicable —</option>
                    @foreach($transportVendors as $tv)
                        <option value="{{ $tv->id }}"
                            @selected((int) old('vendor_id', $delivery->vendor_id) === $tv->id)>
                            {{ $tv->code }} — {{ $tv->name }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Leave blank for own-fleet deliveries.</div>
            </div>
            {{-- <div class="col-md-4">
                <label class="form-label" for="tracking_number">Tracking #</label>
                <input id="tracking_number" name="tracking_number" class="form-control"
                       value="{{ old('tracking_number', $delivery->tracking_number) }}" maxlength="128">
            </div> --}}
            <div class="col-md-4">
                <label class="form-label" for="shipping_address">Shipping Address</label>
                <input id="shipping_address" name="shipping_address" class="form-control"
                       value="{{ old('shipping_address', $delivery->shipping_address) }}" maxlength="500">
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control"
                          maxlength="2000">{{ old('notes', $delivery->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- Lines --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
            <h3 class="card-title">Lines</h3>
        <div class="card-toolbar">
            <span class="text-muted small">{{ count($existingLines) }} line(s)</span>
        </div>
    </div>
    <div class="card-body" id="delivery-lines-container">
        @forelse($existingLines as $i => $l)
            @php($soLineId = (int) ($l['sales_order_line_id'] ?? 0))
            @php($soLine = $salesOrder?->lines?->firstWhere('id', $soLineId)
                          ?? $delivery->salesOrder?->lines?->firstWhere('id', $soLineId))
            @php($l['sales_order_line'] = $soLine)
            @include('sales::deliveries._line-row', ['index' => $i, 'line' => $l])
        @empty
            <div class="text-muted">
                @if($salesOrder)
                    This order has no open lines to deliver.
                @else
                    Select a sales order above to load its lines.
                @endif
            </div>
        @endforelse
    </div>
</div>
