@php($isEdit = $rfq->exists)
@php($existingLines = old('lines', $rfq->lines?->toArray() ?? []))
@if(empty($existingLines))
    @php($existingLines = [['item_id' => null, 'quantity' => '']])
@endif

@php($selectedVendors = old('vendor_ids', $rfq->vendors?->pluck('vendor_id')->all() ?? []))
@if(empty($selectedVendors))
    @php($selectedVendors = [null])
@endif

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Header</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="issue_date">
                    Issue Date <span class="text-danger">*</span>
                </label>
                <input id="issue_date" name="issue_date" type="date"
                       class="form-control @error('issue_date') is-invalid @enderror"
                       value="{{ old('issue_date', $rfq->issue_date?->toDateString() ?? now()->toDateString()) }}"
                       required>
                @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="due_date">
                    Response Deadline <span class="text-danger">*</span>
                </label>
                <input id="due_date" name="due_date" type="date"
                       class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date', $rfq->due_date?->toDateString() ?? now()->addDays(14)->toDateString()) }}"
                       required>
                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="currency_code">
                    Currency <span class="text-danger">*</span>
                </label>
                <input id="currency_code" name="currency_code" maxlength="3"
                       class="form-control @error('currency_code') is-invalid @enderror"
                       value="{{ old('currency_code', $rfq->currency_code) }}"
                       required placeholder="USD">
                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="department_id">Department</label>
                <select id="department_id" name="department_id"
                        class="form-select @error('department_id') is-invalid @enderror">
                    <option value="">—</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}"
                            @selected((int) old('department_id', $rfq->department_id) === $d->id)>
                            {{ $d->code }} — {{ $d->name }}
                        </option>
                    @endforeach
                </select>
                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="cost_center_id">Cost Center</label>
                <select id="cost_center_id" name="cost_center_id"
                        class="form-select @error('cost_center_id') is-invalid @enderror">
                    <option value="">—</option>
                    @foreach($costCenters as $c)
                        <option value="{{ $c->id }}"
                            @selected((int) old('cost_center_id', $rfq->cost_center_id) === $c->id)>
                            {{ $c->code }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
                @error('cost_center_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-6">
                <label class="form-label" for="purpose">
                    Purpose <span class="text-danger">*</span>
                </label>
                <input id="purpose" name="purpose"
                       class="form-control @error('purpose') is-invalid @enderror"
                       value="{{ old('purpose', $rfq->purpose) }}"
                       required maxlength="500">
                @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Lines --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Lines</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rfqAddLine()">
            <i class="bi bi-plus-lg"></i> Add Line
        </button>
    </div>
    <div class="card-body" id="rfq-lines-container">
        @foreach($existingLines as $i => $line)
            @include('procurement::rfqs._line-row', [
                'index' => $i,
                'line'  => $line,
                'items' => $items,
                'units' => $units,
            ])
        @endforeach
    </div>
</div>

{{-- Vendors --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Invited Vendors</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rfqAddVendor()">
            <i class="bi bi-plus-lg"></i> Add Vendor
        </button>
    </div>
    <div class="card-body" id="rfq-vendors-container">
        @foreach($selectedVendors as $i => $vendorId)
            @include('procurement::rfqs._vendor-row', [
                'index'   => $i,
                'vendor'  => ['id' => $vendorId],
                'vendors' => $vendors,
            ])
        @endforeach
    </div>
</div>

{{-- Terms --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Terms &amp; Conditions</h3>
    </div>
    <div class="card-body">
        <textarea id="terms" name="terms" rows="4"
                  class="form-control @error('terms') is-invalid @enderror"
                  maxlength="5000"
                  placeholder="Delivery terms, payment terms, quality requirements, warranty…">{{ old('terms', $rfq->terms) }}</textarea>
        @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
