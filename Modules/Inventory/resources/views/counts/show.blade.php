<x-dynamic-component :component="request()->boolean('print') ? 'inventory-print-layout' : 'default-layout'">
@section('title', $count->number)
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h3 m-0">
            {{ $count->number }}
            <span class="badge text-bg-{{ $count->status->badgeClass() }} ms-2">{{ $count->status->label() }}</span>
        </h1>
        <div class="text-muted small">
            {{ $count->warehouse?->name }} · {{ $count->count_date->format('Y-m-d') }}
            @if($count->scope) · Scope: {{ $count->scope }}@endif
        </div>
    </div>
    @if(!request()->boolean('print'))
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('inventory.counts.show', ['count' => $count, 'print' => 1]) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Print / PDF</a>
        @can('start', $count)
            <form method="POST" action="{{ route('inventory.counts.start', $count) }}">
                @csrf @method('PATCH')
                <button class="btn btn-primary">Start Counting</button>
            </form>
        @endcan
        @can('submit', $count)
            <form method="POST" action="{{ route('inventory.counts.submit', $count) }}">
                @csrf @method('PATCH')
                <button class="btn btn-primary"
                        onclick="return confirm('Submit for review? All lines must be counted.')">
                    Submit for Review
                </button>
            </form>
        @endcan
        @can('approve', $count)
            <form method="POST" action="{{ route('inventory.counts.approve', $count) }}">
                @csrf @method('PATCH')
                <button class="btn btn-success">Approve</button>
            </form>
        @endcan
        @can('post', $count)
            <form method="POST" action="{{ route('inventory.counts.post', $count) }}">
                @csrf @method('PATCH')
                <button class="btn btn-success"
                        onclick="return confirm('Post this count? Variance adjustments will be applied to stock.')">
                    Post Count
                </button>
            </form>
        @endcan
        @can('cancel', $count)
            <form method="POST" action="{{ route('inventory.counts.cancel', $count) }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline-danger" onclick="return confirm('Cancel this count?')">Cancel</button>
            </form>
        @endcan
    </div>
    @endif
</div>

@if($count->status->value === 'counting' && !request()->boolean('print'))
    @if($count->lines->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard-data fs-2 text-muted"></i>
                <h2 class="h5 mt-3">No items have been loaded for this count.</h2>
                <p class="text-muted mb-3">Load the current warehouse stock, then enter the physical quantity for each item.</p>
                @can('update', $count)
                    <form method="POST" action="{{ route('inventory.counts.snapshot', $count) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-primary">Load Current Stock</button>
                    </form>
                @endcan
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('inventory.counts.record', $count) }}">
            @csrf
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <span>Counted Quantities</span>
                    <button class="btn btn-sm btn-primary" type="submit">Save Counted Quantities</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Item</th>
                                <th class="text-end">System Qty</th>
                                <th class="text-end">Counted Qty</th>
                                <th class="text-end">Variance Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($count->lines as $i => $line)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td><code>{{ $line->item?->code }}</code> {{ $line->item?->name }}</td>
                                    <td class="text-end system-qty">{{ number_format((float) $line->system_quantity, 2) }} KG</td>
                                    <td class="text-end" style="width: 200px;">
                                        <div class="input-group input-group-sm">
                                            <input name="counted[{{ $line->id }}]"
                                                type="number"
                                                step="0.0001"
                                                min="0"
                                                class="form-control text-end counted-input"
                                                value="{{ $line->counted_quantity ?? '' }}"
                                                required>

                                            <span class="input-group-text">KG</span>
                                        </div>
                                    </td>
                                    <td class="text-end variance-cell {{ bccomp((string) $line->variance, '0', 2) !== 0 ? 'fw-semibold' : '' }}
                                        {{ bccomp((string) $line->variance, '0', 2) > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format((float) $line->variance, 2) }} KG
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    @endif
@else
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between">
            <span>Counted Quantities</span>
            @php($totalVariance = $count->lines->sum(fn ($l) => (float) $l->variance))
            <span class="text-muted small">
                Total variance:
                <strong class="{{ $totalVariance > 0 ? 'text-success' : ($totalVariance < 0 ? 'text-danger' : '') }}">
                    {{ number_format($totalVariance, 2) }}
                </strong>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Item</th>
                        <th class="text-end">System</th>
                        <th class="text-end">Counted</th>
                        <th class="text-end">Variance</th>
                        <th class="text-end">Unit Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($count->lines as $i => $line)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td><code>{{ $line->item?->code }}</code> {{ $line->item?->name }}</td>
                            <td class="text-end">{{ number_format((float) $line->system_quantity, 2) }} {{ $line->item?->unit?->code }}</td>
                            <td class="text-end">{{ $line->counted_quantity !== null ? number_format((float) $line->counted_quantity, 2) : '—' }} {{ $line->item?->unit?->code }}</td>
                            <td class="text-end {{ bccomp((string) $line->variance, '0', 0) > 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) $line->variance, 2) }} {{ $line->item?->unit?->code }}
                            </td>
                            <td class="text-end">{{ number_format((float) $line->unit_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($count->stockAdjustment)
            <div class="card-footer bg-white">
                <span class="text-muted small">Adjustment created on post:</span>
                <a href="{{ route('inventory.adjustments.show', $count->stock_adjustment_id) }}">
                    {{ $count->stockAdjustment->number }}
                </a>
            </div>
        @endif
    </div>
@endif

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.counted-input').forEach(function (input) {
        input.addEventListener('input', function () {
            const row = this.closest('tr');
            const systemQty = parseFloat(row.querySelector('.system-qty').textContent.replace(/,/g, '')) || 0;
            const counted   = parseFloat(this.value || systemQty);
            const variance  = counted - systemQty;

            const cell = row.querySelector('.variance-cell');
            cell.textContent = variance.toFixed(2);
            cell.classList.remove('text-success', 'text-danger', 'text-muted', 'fw-semibold');

            if (variance > 0.00005) {
                cell.classList.add('text-success', 'fw-semibold');
            } else if (variance < -0.00005) {
                cell.classList.add('text-danger', 'fw-semibold');
            } else {
                cell.classList.add('text-muted');
                cell.textContent = '0';
            }
        });
    });
})();
</script>
@endpush
</x-dynamic-component>
