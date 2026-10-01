@php($existingLines = old('lines', $transfer->lines?->toArray() ?? []))
@if(empty($existingLines))
    @php($existingLines = [['item_id' => null, 'quantity' => '', 'unit_cost' => '']])
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Header</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="from_warehouse_id">From Warehouse <span class="text-danger">*</span></label>
                <select id="from_warehouse_id" name="from_warehouse_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected((int) old('from_warehouse_id', $transfer->from_warehouse_id) === $w->id)>
                            {{ $w->code }} — {{ $w->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="to_warehouse_id">To Warehouse <span class="text-danger">*</span></label>
                <select id="to_warehouse_id" name="to_warehouse_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected((int) old('to_warehouse_id', $transfer->to_warehouse_id) === $w->id)>
                            {{ $w->code }} — {{ $w->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="transfer_date">Transfer Date <span class="text-danger">*</span></label>
                <input id="transfer_date" name="transfer_date" type="date" class="form-control"
                       value="{{ old('transfer_date', $transfer->transfer_date?->toDateString() ?? now()->toDateString()) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="expected_arrival_date">Expected Arrival</label>
                <input id="expected_arrival_date" name="expected_arrival_date" type="date" class="form-control"
                       value="{{ old('expected_arrival_date', $transfer->expected_arrival_date?->toDateString()) }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $transfer->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Lines</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="invAddLine()">
            <i class="bi bi-plus-lg"></i> Add Line
        </button>
    </div>
    <div class="card-body" id="inv-lines-container">
        @foreach($existingLines as $i => $line)
            @include('inventory::transfers._line-row', [
                'index' => $i, 'line' => $line, 'items' => $items, 'units' => $units,
            ])
        @endforeach
    </div>
</div>
