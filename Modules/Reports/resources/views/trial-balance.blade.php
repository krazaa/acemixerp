<x-danger-button>
@section('title', $title)
@section('sub-title', $title)
@section('content')
@include('reports::filters')
<p class="text-muted">Cumulative posted balances through the selected date, including earlier periods. Original reversed entries and their posted reversals are included on their respective entry dates.</p>
@forelse($groups as $currency => $group)
    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0">{{ $currency }}</h2></div>
        <div class="card-body">
            @if(bccomp($group['debit'], $group['credit'], 4) === 0)
                <div class="alert alert-success mb-0">Debits and credits balance.</div>
            @else
                <div class="alert alert-danger mb-0">Out of balance by {{ number_format(abs((float) bcsub($group['debit'], $group['credit'], 4)), 4) }} {{ $currency }}.</div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Code</th><th>Account</th><th class="text-end">Debit Balance</th><th class="text-end">Credit Balance</th></tr></thead>
                <tbody>
                @foreach($group['rows'] as $row)
                    <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td class="text-end">{{ number_format((float) $row->debit, 4) }}</td><td class="text-end">{{ number_format((float) $row->credit, 4) }}</td></tr>
                @endforeach
                </tbody>
                <tfoot><tr class="fw-bold"><th colspan="2">Total {{ $currency }}</th><td class="text-end">{{ number_format((float) $group['debit'], 4) }}</td><td class="text-end">{{ number_format((float) $group['credit'], 4) }}</td></tr></tfoot>
            </table>
        </div>
    </div>
@empty
    <div class="alert alert-info">No posted entries match the selected filters.</div>
@endforelse

</x-danger-button>
