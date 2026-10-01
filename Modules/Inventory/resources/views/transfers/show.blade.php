<x-default-layout>
@section('title', $transfer->number)
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h3 m-0">
            {{ $transfer->number }}
            <span class="badge text-bg-{{ $transfer->status->badgeClass() }} ms-2">{{ $transfer->status->label() }}</span>
        </h1>
        <div class="text-muted small">
            {{ $transfer->transfer_date->format('Y-m-d') }}
            · {{ $transfer->fromWarehouse?->name }} → {{ $transfer->toWarehouse?->name }}
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('submit', $transfer)
            <form method="POST" action="{{ route('inventory.transfers.submit', $transfer) }}">
                @csrf @method('PATCH')
                <button class="btn btn-primary">Submit</button>
            </form>
        @endcan
        @can('approve', $transfer)
            <form method="POST" action="{{ route('inventory.transfers.approve', $transfer) }}">
                @csrf @method('PATCH')
                <button class="btn btn-success">Approve</button>
            </form>
        @endcan
        @can('dispatch', $transfer)
            <form method="POST" action="{{ route('inventory.transfers.dispatch', $transfer) }}">
                @csrf @method('PATCH')
                <button class="btn btn-primary"
                        onclick="return confirm('Dispatch this transfer? Stock will leave the source warehouse.')">
                    Dispatch
                </button>
            </form>
        @endcan
        @can('receive', $transfer)
            <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}">
                @csrf @method('PATCH')
                @foreach($transfer->lines as $line)
                    <input type="hidden" name="received[{{ $line->id }}]" value="{{ $line->quantity }}">
                @endforeach
                <button class="btn btn-success"
                        onclick="return confirm('Receive all quantities into the destination warehouse?')">
                    Receive All
                </button>
            </form>
        @endcan
        @can('cancel', $transfer)
            <form method="POST" action="{{ route('inventory.transfers.cancel', $transfer) }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline-danger"
                        onclick="return confirm('Cancel this transfer?')">Cancel</button>
            </form>
        @endcan
        @can('update', $transfer)
            <a href="{{ route('inventory.transfers.edit', $transfer) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">From</dt><dd class="col-sm-8">{{ $transfer->fromWarehouse?->name }}</dd>
                    <dt class="col-sm-4">To</dt><dd class="col-sm-8">{{ $transfer->toWarehouse?->name }}</dd>
                    <dt class="col-sm-4">Expected Arrival</dt><dd class="col-sm-8">{{ $transfer->expected_arrival_date?->format('Y-m-d') ?? '—' }}</dd>
                    <dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $transfer->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Timeline</div>
            <div class="card-body small text-muted">
                <div>Created: {{ $transfer->created_at?->format('Y-m-d H:i') }}</div>
                @if($transfer->submitted_at)<div>Submitted: {{ $transfer->submitted_at->format('Y-m-d H:i') }}</div>@endif
                @if($transfer->approved_at)<div>Approved: {{ $transfer->approved_at->format('Y-m-d H:i') }}</div>@endif
                @if($transfer->dispatched_at)<div>Dispatched: {{ $transfer->dispatched_at->format('Y-m-d H:i') }}</div>@endif
                @if($transfer->received_at)<div>Received: {{ $transfer->received_at->format('Y-m-d H:i') }}</div>@endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Lines</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th><th>Item</th><th>Unit</th>
                    <th class="text-end">Requested</th>
                    <th class="text-end">Dispatched</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Unit Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfer->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><code>{{ $line->item?->code }}</code> {{ $line->item?->name }}</td>
                        <td>{{ $line->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->dispatched_quantity, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->received_quantity, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->unit_cost, 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</x-default-layout>
