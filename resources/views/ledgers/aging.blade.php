<x-default-layout>
@section('title', $title)


 @section('toolbar-button')
 <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print / PDF</button><button type="button" class="btn btn-sm btn-outline-success" onclick="exportAgingCsv()">Export Excel</button>
        <a href="{{ route('journals.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2 no-print">
      </div>
</div>


<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h3 class="card-title"></h3>

    <div class="card-toolbar">
        <form method="GET" class="d-flex align-items-center">
            <input name="as_of" type="date" value="{{ $asOf->format('d/m/Y') }}" class="form-control form-control-sm">
            <button type="submit" class="btn btn-sm btn-primary text-nowrap ms-2">As of</button>
        </form>
    </div>
    <p class="text-muted">Outstanding balances as of {{ $asOf->format('d/mY') }}  including posted payments and adjustments. Buckets are by journal entry date; payments reduce their own date bucket. Negative balances indicate advances or credits.</p>
</div>
<div class="card-body">
    <div class="table-responsive">
        <table id="aging-table" class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Party</th>
                    <th>Currency</th>
                    <th class="text-end">Current</th>
                    <th class="text-end">1–30</th>
                    <th class="text-end">31–60</th>
                    <th class="text-end">61–90</th>
                    <th class="text-end">91+</th>
                    <th class="text-end">Outstanding Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>
                            @if($r->party_id === null)
                                {{ $r->party_name }}
                            @elseif($type === 'receivable')
                                <a href="{{ route('customers.ledger', $r->party_id) }}">{{ $r->party_name }}</a>
                            @else
                                <a href="{{ route('vendors.ledger', $r->party_id) }}">{{ $r->party_name }}</a>
                            @endif
                        </td>
                        <td>{{ $r->currency_code }}</td>
                        <td class="text-end">{{ number_format((float) $r->current, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $r->days_1_30, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $r->days_31_60, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $r->days_61_90, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $r->days_90_plus, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $r->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No outstanding balances.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-semibold">
                @foreach($rows->groupBy('currency_code') as $currency => $currencyRows)
                    <tr>
                        <th>Total Outstanding Balance</th>
                        <th>{{ $currency }}</th>
                        @foreach(['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_90_plus', 'total'] as $field)
                            <td class="text-end">{{ number_format((float) $currencyRows->reduce(fn ($total, $row) => bcadd($total, (string) $row->{$field}, 4), '0.0000'), 2) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tfoot>
        </table>
    </div>
</div>
</div>

@push('scripts')<script>function exportAgingCsv(){const rows=[...document.querySelectorAll('#aging-table tr')].map(r=>[...r.querySelectorAll('th,td')].map(c=>'"'+c.innerText.replaceAll('"','""').replaceAll('\n',' ')+'"').join(',')).join('\n');const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([rows],{type:'text/csv;charset=utf-8;'}));a.download='{{ $type }}-aging.csv';a.click();URL.revokeObjectURL(a.href);}</script><style>@media print{.no-print{display:none!important}}</style>@endpush
</x-default-layout>
