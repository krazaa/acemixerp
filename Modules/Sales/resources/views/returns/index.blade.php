<x-default-layout>
@section('title', 'Sales Returns')
@section('toolbar-button')
    @can('create', \Modules\Sales\Models\SalesReturn::class)<a class="btn btn-primary btn-sm" href="{{ route('sales.returns.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Return Request</a>@endcan
@endsection
<section class="bg-body p-6">
    <form method="GET" class="d-flex gap-3 mb-5"><label class="visually-hidden" for="return-search">Return number</label><input id="return-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Return number"><button class="btn btn-light-primary" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button></form>
    <div class="table-responsive"><table class="table table-row-dashed align-middle gy-4">
        <thead><tr><th>Return</th><th>Invoice</th><th>Customer</th><th>Date</th><th>Status</th><th>Credit Note</th></tr></thead>
        <tbody>@forelse($returns as $return)<tr><td><a href="{{ route('sales.returns.show',$return) }}">{{ $return->number }}</a></td><td>{{ $return->invoice->number }}</td><td>{{ $return->invoice->customer?->name }}</td><td>{{ $return->return_date->format('d M Y') }}</td><td><span class="badge badge-light-primary">{{ ucfirst($return->status) }}</span></td><td>{{ $return->creditNote?->number ?? '-' }}</td></tr>@empty<tr><td colspan="6" class="text-center py-8 text-muted">No sales returns found.</td></tr>@endforelse</tbody>
    </table></div>{{ $returns->links() }}
</section>
</x-default-layout>
