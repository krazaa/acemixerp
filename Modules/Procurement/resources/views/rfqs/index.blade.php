<x-default-layout>
@section('title', 'Requests for Quotation')


    @section('toolbar-button')
     @can('create', \Modules\Procurement\Models\RequestForQuotation::class)
        <a href="{{ route('procurement.rfqs.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-2"></i>  New RFQ
        </a>
        @endcan
    @endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control"
               placeholder="Number or purpose">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\Modules\Procurement\Enums\RfqStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="department_id" class="form-select">
            <option value="">All departments</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-info w-100">Filter</button></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th>
                    <th>Issue</th>
                    <th>Due</th>
                    <th>Purpose</th>
                    <th class="text-center">Lines</th>
                    <th class="text-center">Vendors</th>
                    <th class="text-center">Quotes</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rfqs as $r)
                    <tr>
                        <td><code>{{ $r->number }}</code></td>
                        <td>{{ $r->issue_date->format('d/m/Y') }}</td>
                        <td>{{ $r->due_date->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('procurement.rfqs.show', $r) }}" class="text-decoration-none">
                                {{ \Illuminate\Support\Str::limit($r->purpose, 50) }}
                            </a>
                        </td>
                        <td class="text-center">{{ $r->lines_count }}</td>
                        <td class="text-center">{{ $r->vendors_count }}</td>
                        <td class="text-center">{{ $r->quotations_count }}</td>
                        <td><span class="badge badge-{{ $r->status->badgeClass() }}">{{ $r->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('procurement.rfqs.show', $r) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $r)
                                <a href="{{ route('procurement.rfqs.edit', $r) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('view', $r)
                                <a href="{{ route('procurement.rfqs.compare', $r) }}" class="btn btn-sm btn-outline-secondary">Compare</a>
                            @endcan

                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No RFQs.</td></tr>
                @endforelse
            </tbody>

        </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $rfqs->links() }}</div>

</x-default-layout>
