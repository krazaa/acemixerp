<x-default-layout>
@section('title', 'Purchase Requisitions')

    @section('toolbar-button')
        @can('create', \Modules\Procurement\Models\PurchaseRequisition::class)
            <a href="{{ route('procurement.purchase-requisitions.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i>New Requisition
            </a>
        @endcan
    @endsection

@section('content')
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm"
               placeholder="Number or purpose">
    </div>
    <div class="col-md-1">
        <select name="status" class="form-select form-select-sm">
            <option value="">Statuses</option>
            @foreach(\Modules\Procurement\Enums\PurchaseRequisitionStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="department_id" class="form-select form-select-sm">
            <option value="">All departments</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('procurement.purchase-requisitions.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th>
                    <th>Requested</th>
                    <th>Required</th>
                    <th>Purpose</th>
                    <th>Requester</th>
                    <th>Dept</th>
                    <th class="text-center">Lines</th>
                    <th class="text-end">Est. Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requisitions as $pr)
                    <tr>
                        <td>
                             <a href="{{ route('procurement.purchase-requisitions.show', $pr) }}"><code>{{ $pr->number }}</code></td>
                        <td>{{ $pr->requested_date->format('d/mY') }}</td>
                        <td>{{ $pr->required_date?->format('d/mY') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('procurement.purchase-requisitions.show', $pr) }}" class="text-decoration-none">
                                {{ \Illuminate\Support\Str::limit($pr->purpose, 50) }}
                            </a>
                        </td>
                        <td>{{ $pr->requester?->name ?? '—' }}</td>
                        <td>{{ $pr->department?->name ?? '—' }}</td>
                        <td class="text-center">{{ $pr->lines_count }}</td>
                        <td class="text-end">{{ number_format((float) $pr->total_estimated, 2) }}</td>
                        <td><span class="badge badge-{{ $pr->status->badgeClass() }}">{{ $pr->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('procurement.purchase-requisitions.show', $pr) }}" class="btn btn-sm btn-light-secondary">View</a>
                            @if($pr->status->canConvert())
                                @can('convertToRfq', $pr)
                                    <a href="{{ route('procurement.purchase-requisitions.convert-to-rfq', $pr) }}" class="btn btn-sm btn-light-success">Convert to RFQ</a>
                                @endcan
                            @endif
                            @can('update', $pr)
                                <a href="{{ route('procurement.purchase-requisitions.edit', $pr) }}" class="btn btn-sm btn-light-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No purchase requisitions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
<div class="mt-3">{{ $requisitions->links() }}</div>

</x-default-layout>
