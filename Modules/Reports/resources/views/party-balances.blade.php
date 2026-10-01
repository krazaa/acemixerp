@extends('layouts.app')
@section('title', $title)
@section('content')
@include('reports::filters')
<p class="text-muted">Balances include posted invoices, payments, and adjustments in the configured control accounts. Negative balances represent advances or credits. Fully settled balances are omitted.</p>
@forelse($groups as $currency => $group)
    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0">{{ $currency }}</h2></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Code</th><th>{{ $partyLabel }}</th><th class="text-end">Debits</th><th class="text-end">Credits</th><th class="text-end">Outstanding Balance</th></tr></thead>
                <tbody>
                @foreach($group['rows'] as $row)
                    <tr>
                        <td>{{ $row->code ?? '—' }}</td><td>{{ $row->name ?? 'Unassigned party' }}</td>
                        <td class="text-end">{{ number_format((float) $row->debit, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $row->credit, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $row->balance, 4) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr class="fw-bold"><th colspan="2">Total {{ $currency }}</th><td class="text-end">{{ number_format((float) $group['debit'], 4) }}</td><td class="text-end">{{ number_format((float) $group['credit'], 4) }}</td><td class="text-end">{{ number_format((float) $group['balance'], 4) }}</td></tr></tfoot>
            </table>
        </div>
    </div>
@empty
    <div class="alert alert-info">No outstanding balances match the selected filters.</div>
@endforelse
@endsection
