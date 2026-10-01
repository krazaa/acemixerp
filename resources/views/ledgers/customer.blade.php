<x-default-layout>
@section('title', "Customer Ledger — {$customer->name}")

@section('sub-title')
    <div>
        <div class="text-muted small"><code>{{ $customer->code }}</code> ·<a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">Customer Details</a></div>
    </div>
@endsection

 @section('toolbar-button')
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection


<div class="alert alert-info d-flex justify-content-between">
    <span>Outstanding balance</span>
    <strong>{{ number_format((float) $balance, 4) }}</strong>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input name="from" type="date" value="{{ $from?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
    <div class="col-md-3"><input name="to"   type="date" value="{{ $to?->format('Y-m-d')   }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-info w-100">Filter</button></div>
</form>

<div class="card border-0 shadow-sm">
        <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr class="fw-bold fs-6 text-gray-800">
                    <th>Date</th><th>Entry</th><th>Description</th><th>Account</th>
                    <th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $l)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($l->entry_date)->format('d/m/Y') }}</td>
                        <td><a href="{{ route('journals.show', $l->journal_entry_id) }}"><code>{{ $l->number }}</code></a></td>
                        <td>{{ $l->description }}</td>
                        <td class="text-muted">{{ $l->account_code }} — {{ $l->account_name }}</td>
                        <td class="text-end">{{ bccomp($l->debit, '0', 4) > 0 ? number_format((float) $l->debit, 4) : '' }}</td>
                        <td class="text-end">{{ bccomp($l->credit, '0', 4) > 0 ? number_format((float) $l->credit, 4) : '' }}</td>
                        <td class="text-end">{{ number_format((float) $l->running_balance, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No transactions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
</x-default-layout>
