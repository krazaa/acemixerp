<x-default-layout>
@section('title', 'Trial Balance')
@section('toolbar-button')
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print / PDF</button>
    <button type="button" class="btn btn-outline-success btn-sm" onclick="exportReportCsv('trial-balance-table', 'trial-balance.csv')">Export Excel</button>
    <a href="{{ route('gl.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back to GL
        </a>
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <label class="form-label small">From</label>
        <input name="from" type="date" value="{{ $query->from?->format('Y-m-d') }}" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
        <label class="form-label small">To</label>
        <input name="to" type="date" value="{{ $query->to?->format('Y-m-d') }}" class="form-control form-control-sm">
    </div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100">Run</button></div>
</form>

@if($balanced)
    <div class="alert alert-success">Trial balance is balanced.</div>
@else
    <div class="alert alert-danger">
        <strong>Trial balance is NOT balanced.</strong>
        Debit {{ number_format((float) $totalDebit, 2) }} ≠ Credit {{ number_format((float) $totalCredit, 2) }}.
        This should never happen in production; investigate immediately.
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table id="trial-balance-table" class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Code</th><th>Account</th><th>Type</th>
                    <th class="text-end">Debit Total</th>
                    <th class="text-end">Credit Total</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td><code>{{ $r->code }}</code></td>
                        <td>{{ $r->name }}</td>
                        <td class="text-muted">{{ ucfirst($r->type) }}</td>
                        <td class="text-end">{{ number_format((float) $r->debit_total, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $r->credit_total, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $r->balance, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="3" class="text-end">Totals</td>
                    <td class="text-end">{{ number_format((float) $totalDebit, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $totalCredit, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</div>
@push('scripts')<script>function exportReportCsv(id,name){const rows=[...document.querySelectorAll('#'+id+' tr')].map(r=>[...r.querySelectorAll('th,td')].map(c=>'"'+c.innerText.replaceAll('"','""').replaceAll('\n',' ')+'"').join(',')).join('\n');const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([rows],{type:'text/csv;charset=utf-8;'}));a.download=name;a.click();URL.revokeObjectURL(a.href);}</script><style>@media print{.no-print{display:none!important}}</style>@endpush
</x-default-layout>
