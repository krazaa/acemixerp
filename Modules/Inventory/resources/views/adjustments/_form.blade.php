@php($existingLines = old('lines', $adjustment->lines?->toArray() ?? []))
@php($existingLines = count($existingLines) > 0 ? $existingLines : [['item_id' => null, 'quantity' => '', 'unit_cost' => '', 'notes' => '']])

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
                <h3 class="card-title">Adjustment Details</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="warehouse_id" class="form-label">Warehouse <span class="text-danger">*</span></label>
                <select id="warehouse_id" name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                    <option value="">— Select warehouse —</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((int) old('warehouse_id', $adjustment->warehouse_id) === $warehouse->id)>
                            {{ $warehouse->code }} — {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label for="adjustment_date" class="form-label">Adjustment Date <span class="text-danger">*</span></label>
                <input id="adjustment_date" name="adjustment_date" type="date" required
                       class="form-control @error('adjustment_date') is-invalid @enderror"
                       value="{{ old('adjustment_date', $adjustment->adjustment_date?->toDateString() ?? now()->toDateString()) }}">
                @error('adjustment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
                <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                <input id="reason" name="reason" maxlength="500" required
                       class="form-control @error('reason') is-invalid @enderror"
                       value="{{ old('reason', $adjustment->reason) }}" placeholder="e.g. Physical count variance">
                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" rows="2" maxlength="2000" class="form-control">{{ old('notes', $adjustment->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Adjustment Lines</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-adjustment-line"><i class="bi bi-plus-lg"></i> Add Line</button>
    </div>
    <div class="card-body">
        <div class="alert alert-info small py-2">Use a positive quantity to increase stock and a negative quantity to decrease it.</div>
        <div id="adjustment-lines">
            @foreach($existingLines as $index => $line)
                @include('inventory::adjustments._line-row', ['index' => $index, 'line' => $line, 'items' => $items])
            @endforeach
        </div>
    </div>
</div>

<template id="adjustment-line-template">
    @include('inventory::adjustments._line-row', ['index' => '__INDEX__', 'line' => [], 'items' => $items])
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const lines = document.getElementById('adjustment-lines');
    const template = document.getElementById('adjustment-line-template');
    let nextIndex = {{ count($existingLines) }};

    document.getElementById('add-adjustment-line').addEventListener('click', () => {
        const markup = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        lines.insertAdjacentHTML('beforeend', markup);
        $(lines.lastElementChild).find('[data-control="select2"]').select2({
            width: '100%',
            dir: document.body.getAttribute('direction') || 'ltr',
        }).attr('data-kt-initialized', '1');
    });

    lines.addEventListener('click', (event) => {
        if (event.target.closest('.remove-adjustment-line')) {
            const line = event.target.closest('.adjustment-line');
            $(line).find('.select2-hidden-accessible').select2('destroy');
            line.remove();
        }
    });
});
</script>
@endpush
