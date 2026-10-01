<x-default-layout>

@section('title', $adjustment->number)

@section('toolbar-button')
    @can('submit', $adjustment)
        <form method="POST" action="{{ route('inventory.adjustments.submit', $adjustment) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-primary">Submit</button></form>
    @endcan
    @can('approve', $adjustment)
        <form method="POST" action="{{ route('inventory.adjustments.approve', $adjustment) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Approve</button></form>
    @endcan
    @can('post', $adjustment)
        <form method="POST" action="{{ route('inventory.adjustments.post', $adjustment) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" onclick="return confirm('Post this adjustment? Inventory movements will be recorded.')">Post Adjustment</button></form>
    @endcan
    @can('cancel', $adjustment)
        <form method="POST" action="{{ route('inventory.adjustments.cancel', $adjustment) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this adjustment?')">Cancel</button></form>
    @endcan
        @can('update', $adjustment)<a href="{{ route('inventory.adjustments.edit', $adjustment) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    @endcan
@endsection



    <div class="row g-3 mb-3">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                        <h3 class="card-title">Details</h3>
                        <div class="card-toolbar">
                            <span class="badge badge-{{ $adjustment->status->badgeClass() }} ms-2">{{ $adjustment->status->label() }}</span>
                            <div class="text-muted small ms-2">{{ $adjustment->adjustment_date->format('d/m/Y') }} · {{ $adjustment->warehouse?->name }}</div>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0"><dt class="col-sm-4">Warehouse</dt><dd class="col-sm-8">{{ $adjustment->warehouse?->code }} — {{ $adjustment->warehouse?->name }}</dd><dt class="col-sm-4">Reason</dt><dd class="col-sm-8">{{ $adjustment->reason }}</dd><dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $adjustment->notes ?: '—' }}</dd>@if($adjustment->journalEntry)<dt class="col-sm-4">Journal Entry</dt><dd class="col-sm-8"><a href="{{ route('journals.show', $adjustment->journalEntry) }}">{{ $adjustment->journalEntry->number }}</a></dd>@endif</dl></div></div></div>
        <div class="col-md-5"><div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Audit</h3>
                </div>
                <div class="card-body small text-muted">
                    <div>Created: {{ $adjustment->created_at?->format('Y-m-d H:i') }} · {{ $adjustment->creator?->name }}</div>@if($adjustment->submitted_at)<div>Submitted: {{ $adjustment->submitted_at->format('Y-m-d H:i') }} · {{ $adjustment->submitter?->name }}</div>@endif @if($adjustment->approved_at)<div>Approved: {{ $adjustment->approved_at->format('Y-m-d H:i') }} · {{ $adjustment->approver?->name }}</div>@endif @if($adjustment->posted_at)<div>Posted: {{ $adjustment->posted_at->format('Y-m-d H:i') }} · {{ $adjustment->poster?->name }}</div>@endif</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header">
        <h3 class="card-title">DetailsLines</h3>
        </div>
        <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0"><thead class="table-light"><tr><th>#</th><th>Item</th><th>Batch</th><th class="text-end">Quantity</th><th class="text-end">Unit Cost</th><th class="text-end">Value</th><th>Notes</th></tr></thead><tbody>@forelse($adjustment->lines as $index => $line)<tr><td class="text-muted">{{ $index + 1 }}</td><td> {{ $line->item?->name }}</td><td>{{ $line->batch?->batch_number ?? '—' }}</td><td class="text-end {{ $line->isIncrease() ? 'text-success' : 'text-danger' }}">{{ number_format((float) $line->quantity, 4) }}</td><td class="text-end">{{ number_format((float) $line->unit_cost, 4) }}</td><td class="text-end">{{ number_format((float) $line->lineValue(), 4) }}</td><td>{{ $line->notes ?: '—' }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No adjustment lines found.</td></tr>@endforelse</tbody></table></div></div></div>
</x-default-layout>
