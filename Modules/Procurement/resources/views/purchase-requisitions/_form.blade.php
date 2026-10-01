@php($isEdit = $requisition->exists)
@php($existingLines = old('lines', $requisition->lines?->toArray() ?? []))
@if(empty($existingLines))
    @php($existingLines = [['item_id' => null, 'quantity' => '', 'estimated_unit_price' => '']])
@endif

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="requested_date">Requested Date <span class="text-danger">*</span></label>
                <input id="requested_date" name="requested_date" type="date"
                       class="form-control form-control-sm" required
                       value="{{ old('requested_date', $requisition->requested_date?->toDateString() ?? now()->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="required_date">Required By</label>
                <input id="required_date" name="required_date" type="date"
                       class="form-control form-control-sm"
                       value="{{ old('required_date', $requisition->required_date?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected((int) old('department_id', $requisition->department_id) === $d->id)>
                            {{ $d->code }} — {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cost_center_id">Cost Center</label>
                <select id="cost_center_id" name="cost_center_id" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach($costCenters as $c)
                        <option value="{{ $c->id }}" @selected((int) old('cost_center_id', $requisition->cost_center_id) === $c->id)>
                            {{ $c->code }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="warehouse_id">Destination Warehouse</label>
                <select id="warehouse_id" name="warehouse_id" class="form-select form-select-sm @error('warehouse_id') is-invalid @enderror" data-control="select2" data-placeholder="Select warehouse" data-allow-clear="true">
                    <option value=""></option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((int) old('warehouse_id', $requisition->warehouse_id) === $warehouse->id)>{{ $warehouse->code }} - {{ $warehouse->name }}</option>
                    @endforeach
                </select>
                @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="purpose">Purpose <span class="text-danger">*</span></label>
                <input id="purpose" name="purpose" class="form-control @error('purpose') is-invalid @enderror"
                       value="{{ old('purpose', $requisition->purpose) }}" required maxlength="500">
                @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Lines --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Lines</h3>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="prAddLine()">
            <i class="bi bi-plus-lg"></i> Add Line
        </button>
    </div>
    <div class="card-body" id="pr-lines-container">
        @foreach($existingLines as $i => $line)
            @include('procurement::purchase-requisitions._line-row', ['index' => $i, 'line' => $line])
        @endforeach
    </div>
    <div class="card-footer bg-white d-flex justify-content-end">
        <div>Estimated Total: <strong id="pr-total">0.00</strong></div>
    </div>
</div>

{{-- Notes --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Notes</h3></div>
    <div class="card-body">
        <textarea name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $requisition->notes) }}</textarea>
    </div>
</div>
