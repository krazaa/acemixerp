@php($isEdit = $count->exists)
@php($existingLines = old('lines', $count->lines?->toArray() ?? []))

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Count Header</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Warehouse</label>
                @if($isEdit || $count->warehouse_id)
                    @php($warehouse = $count->warehouse ?? \App\Models\Warehouse::find($count->warehouse_id))
                    <div class="form-control bg-light" readonly>
                        {{ $warehouse?->code }} — {{ $warehouse?->name }}
                    </div>
                    <input type="hidden" name="warehouse_id" value="{{ $count->warehouse_id }}">
                @else
                    <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                        <option value="">— Select warehouse —</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" @selected((int) old('warehouse_id', $count->warehouse_id) === $w->id)>
                                {{ $w->code }} — {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="col-md-3">
                <label class="form-label" for="count_date">Count Date <span class="text-danger">*</span></label>
                <input id="count_date" name="count_date" type="date" class="form-control"
                       value="{{ old('count_date', $count->count_date?->toDateString() ?? now()->toDateString()) }}"
                       required>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="scope">Scope</label>
                <input id="scope" name="scope" class="form-control"
                       value="{{ old('scope', $count->scope) }}" maxlength="500"
                       placeholder="e.g. All items, Category: Electronics">
            </div>

            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control"
                          maxlength="2000">{{ old('notes', $count->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- Lines (read-only preview on the form; editing happens on the show page while counting) --}}
@if($isEdit && ! empty($existingLines))
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between">
            <span>Snapshot Lines</span>
            <span class="text-muted small">{{ count($existingLines) }} item(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th class="text-end">System Qty</th>
                        <th class="text-end">Counted Qty</th>
                        <th class="text-end">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($existingLines as $i => $l)
                        @php($counted = $l['counted_quantity'] ?? null)
                        @php($system  = (string) ($l['system_quantity'] ?? '0'))
                        @php($variance = (string) ($l['variance'] ?? '0'))
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                <code>{{ $l['item']['code'] ?? '' }}</code>
                                {{ $l['item']['name'] ?? '—' }}
                            </td>
                            <td class="text-end">{{ number_format((float) $system, 4) }}</td>
                            <td class="text-end">
                                {{ $counted !== null ? number_format((float) $counted, 4) : '—' }}
                            </td>
                            <td class="text-end
                                {{ bccomp($variance, '0', 4) > 0 ? 'text-success fw-semibold' : '' }}
                                {{ bccomp($variance, '0', 4) < 0 ? 'text-danger fw-semibold' : '' }}">
                                {{ number_format((float) $variance, 4) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white text-muted small">
            Counted quantities are recorded from the <a href="{{ route('inventory.counts.show', $count) }}">Count Detail</a> page after starting the count.
        </div>
    </div>
@endif

{{-- Workflow hint --}}
@if($isEdit)
    @php($status = $count->status)
    <div class="alert alert-{{ $status->badgeClass() === 'secondary' ? 'secondary' : 'info' }}">
        Current status: <strong>{{ $status->label() }}</strong>.
        @if($status->value === 'draft')
            Click <em>Start Counting</em> on the detail page to open the counting interface.
        @elseif($status->value === 'counting')
            Enter counted quantities on the detail page, then click <em>Save Counted Quantities</em>.
        @else
            This count has progressed past the editing stage.
        @endif
    </div>
@endif
