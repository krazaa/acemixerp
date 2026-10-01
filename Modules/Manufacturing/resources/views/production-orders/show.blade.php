<x-default-layout>
@section('title', $order->number)
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h3 m-0">
            {{ $order->number }}
            <span class="badge text-bg-{{ $order->status->badgeClass() }} ms-2">{{ $order->status->label() }}</span>
            <span class="badge text-bg-light border ms-1">{{ $order->type->label() }}</span>
        </h1>
        <div class="text-muted small">
            {{ $order->product?->name }}
            · BOM <code>{{ $order->bom?->code }}</code>
            @if($order->scheduled_start_date)
                · Scheduled {{ $order->scheduled_start_date->format('Y-m-d') }}
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('manufacturing.production-orders.print', $order) }}" target="_blank" class="btn btn-outline-secondary">Print</a>
        @can('plan', $order)
            <form method="POST" action="{{ route('manufacturing.production-orders.plan', $order) }}">
                @csrf @method('PATCH')
                <button class="btn btn-primary">Plan</button>
            </form>
        @endcan
        @can('release', $order)
            <form method="POST" action="{{ route('manufacturing.production-orders.release', $order) }}">
                @csrf @method('PATCH')
                <button class="btn btn-primary"
                        onclick="return confirm('Release this order? Component stock will be verified.')">
                    Release
                </button>
            </form>
        @endcan
        @can('start', $order)
            <form method="POST" action="{{ route('manufacturing.production-orders.start', $order) }}">
                @csrf @method('PATCH')
                <button class="btn btn-warning"
                        onclick="return confirm('Start production? Components will be issued from the source warehouse.')">
                    Start Production
                </button>
            </form>
        @endcan
        @can('complete', $order)
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeModal">
                Complete Production
            </button>
        @endcan
        @can('close', $order)
            <form method="POST" action="{{ route('manufacturing.production-orders.close', $order) }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline-dark"
                        onclick="return confirm('Close this order?')">Close</button>
            </form>
        @endcan
        @can('cancel', $order)
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel</button>
        @endcan
        @can('update', $order)
            <a href="{{ route('manufacturing.production-orders.edit', $order) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
    </div>
</div>

{{-- Progress bar --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-1">
            <span class="small text-muted">
                Produced {{ number_format((float) $order->produced_quantity, 4) }}
                of {{ number_format((float) $order->planned_quantity, 4) }}
            </span>
            <span class="small fw-semibold">{{ $order->progressPercent() }}%</span>
        </div>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar"
                 style="width: {{ $order->progressPercent() }}%"
                 aria-valuenow="{{ $order->progressPercent() }}"
                 aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Details</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-5">Product</dt><dd class="col-sm-7">{{ $order->product?->name }}</dd>
                    <dt class="col-sm-5">Source WH</dt><dd class="col-sm-7">{{ $order->sourceWarehouse?->name }}</dd>
                    @if($order->salesOrder)
                        <dt class="col-sm-5">Sales Order</dt>
                        <dd class="col-sm-7"><a href="{{ route('sales.sales-orders.show', $order->sales_order_id) }}">{{ $order->salesOrder->number }}</a></dd>
                    @endif
                    <dt class="col-sm-5">Notes</dt><dd class="col-sm-7">{{ $order->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Costing</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Materials</span><span>{{ number_format((float) $order->materials_cost, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Labour</span><span>{{ number_format((float) $order->labour_cost, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Overhead</span><span>{{ number_format((float) $order->overhead_cost, 2) }}</span></div>
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-semibold fs-5"><span>Total</span><span>{{ number_format((float) $order->total_cost, 2) }}</span></div>
                <div class="d-flex justify-content-between text-muted small"><span>Unit Cost</span><span>{{ number_format((float) $order->unit_cost, 2) }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Timeline</div>
            <div class="card-body small text-muted">
                @if($order->planned_at)<div>Planned: {{ $order->planned_at->format('Y-m-d H:i') }} @if($order->planner) · {{ $order->planner->name }}@endif</div>@endif
                @if($order->released_at)<div>Released: {{ $order->released_at->format('Y-m-d H:i') }} @if($order->releaser) · {{ $order->releaser->name }}@endif</div>@endif
                @if($order->started_at)<div>Started: {{ $order->started_at->format('Y-m-d H:i') }} @if($order->starter) · {{ $order->starter->name }}@endif</div>@endif
                @if($order->completed_at)<div>Completed: {{ $order->completed_at->format('Y-m-d H:i') }} @if($order->completer) · {{ $order->completer->name }}@endif</div>@endif
                @if($order->closed_at)<div>Closed: {{ $order->closed_at->format('Y-m-d H:i') }} @if($order->closer) · {{ $order->closer->name }}@endif</div>@endif
            </div>
        </div>
    </div>
</div>

{{-- Component lines --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between">
        <span>Component Requirements</span>
        <span class="text-muted small">{{ $order->lines->count() }} component(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Component</th>

                    <th class="text-end">Required</th>
                    <th>Unit</th>
                    <th class="text-end">Issued</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Line Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><code>{{ $line->component?->code }}</code> {{ $line->component?->name }}</td>

                        <td class="text-end">{{ number_format((float) $line->required_quantity, 2) }}</td>
                        <td>{{ $line->unit?->code ?? '—' }}</td>
                        <td class="text-end {{ bccomp((string) $line->issued_quantity, (string) $line->required_quantity, 2) >= 0 ? 'text-success' : 'text-warning' }}">
                            {{ number_format((float) $line->issued_quantity, 2) }}
                        </td>
                        <td class="text-end">{{ number_format((float) $line->unit_cost, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $line->line_cost,2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Outputs --}}
@if($order->outputs->isNotEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Produced Outputs</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Line Cost</th>
                        <th>Journal Entry</th>
                        <th>Produced At</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->outputs as $output)
                        <tr>
                            <td><code>{{ $output->product?->code }}</code> {{ $output->product?->name }}</td>
                            <td class="text-end">{{ number_format((float) $output->quantity, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $output->unit_cost, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $output->line_cost, 2) }}</td>
                            <td>
                                @if($output->journalEntry)
                                    <a href="{{ route('journals.show', $output->journalEntry) }}">
                                        {{ $output->journalEntry->number }}
                                    </a>
                                    <span class="text-muted small">
                                        ({{ number_format((float) $output->journalEntry->total_debit, 2) }})
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $output->produced_at?->format('Y-m-d H:i') }}</td>
                            <td class="small text-muted">{{ $output->producer?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Complete modal --}}
@can('complete', $order)
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('manufacturing.production-orders.complete', $order) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Complete Production — {{ $order->number }}</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Enter the actual quantity produced. Components issued will be consumed; finished goods
                        will be added to the source warehouse.
                    </p>
                    <label class="form-label" for="produced_quantity">
                        Produced Quantity <span class="text-danger">*</span>
                    </label>
                    <input id="produced_quantity" name="produced_quantity" type="number" step="0.0001" min="0.0001"
                           class="form-control @error('produced_quantity') is-invalid @enderror"
                           value="{{ old('produced_quantity', $order->remainingQuantity()) }}" required>
                    @error('produced_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Remaining to produce: {{ number_format((float) $order->remainingQuantity(), 4) }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"
                            onclick="return confirm('Confirm completion? This posts stock and accounting entries.')">
                        Confirm Completion
                    </button>
                </div>
            </form>
        </div>
    </div>
@endcan

{{-- Cancel modal --}}
@can('cancel', $order)
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('manufacturing.production-orders.cancel', $order) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Production Order</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        If components have been issued, they will be returned to the source warehouse.
                    </p>
                    <label class="form-label">Reason</label>
                    <textarea name="reason" rows="3" class="form-control" maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Order</button>
                    <button type="submit" class="btn btn-danger">Cancel Order</button>
                </div>
            </form>
        </div>
    </div>
@endcan

@push('scripts')
@if($errors->any() && old('produced_quantity'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('completeModal')).show();
        });
    </script>
@endif
@endpush
</x-default-layout>
