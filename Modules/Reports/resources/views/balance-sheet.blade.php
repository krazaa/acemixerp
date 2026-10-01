<x-default-layout>
@section('title', 'Balance Sheet')
@section('sub-title')
    <div class="report-label text-muted fw-semibold mb-2">Financial statements</div>
    <p class="text-muted mb-0">Financial position as of {{ $filters['as_of'] }}</p>
@endsection

@section('toolbar-button')
    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light">All Reports</a>
            <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">Print / PDF</button>
@endsection

<style>
    .balance-sheet .card { border: 1px solid var(--bs-border-color, #e4e6ef); border-radius: 1rem; overflow: hidden; }
    .balance-sheet .report-label { font-size: .8rem; text-transform: uppercase; letter-spacing: .07em; }
    .balance-sheet .amount { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .balance-sheet .statement-table { min-width: 360px; }
    .balance-sheet .statement-table > :not(caption) > * > * { padding: 1rem 1.5rem; }
    @media print {
        #kt_app_header, #kt_app_sidebar, #kt_app_toolbar, #kt_app_footer, #kt_scrolltop { display: none !important; }
        .app-wrapper, .app-main, .app-content, .app-container { margin: 0 !important; padding: 0 !important; }
        .balance-sheet .card { break-inside: avoid; box-shadow: none !important; }
        .balance-sheet .table-responsive { overflow: visible; }
    }
</style>

    <div class="card mb-6 d-print-none">
        <div class="card-body p-5">
            <form method="GET" action="{{ route('reports.balance-sheet') }}" class="row g-4 align-items-end">
                <div class="col-md-4">
                    <label for="as_of" class="form-label fw-semibold">As of date</label>
                    <input id="as_of" name="as_of" type="date" value="{{ old('as_of', $filters['as_of']) }}" class="form-control @error('as_of') is-invalid @enderror" required>
                    @error('as_of')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="currency" class="form-label fw-semibold">Currency code <span class="text-muted fw-normal">(optional)</span></label>
                    <input id="currency" name="currency" value="{{ old('currency', $filters['currency'] ?? '') }}" class="form-control @error('currency') is-invalid @enderror" maxlength="3" pattern="[A-Z]{3}" placeholder="All currencies" aria-describedby="currency-help">
                    @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Run Report</button>
                    <a href="{{ route('reports.balance-sheet') }}" class="btn btn-light">Reset</a>
                </div>
                <div id="currency-help" class="col-12 text-muted fs-7">Enter a three-letter code such as PKR. Currencies are shown separately; no conversion is applied.</div>
            </form>
        </div>
    </div>

    @forelse($groups as $currency => $group)
        <section class="mb-8" aria-label="Balance sheet in {{ $currency }}">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <h2 class="fs-3 fw-bold mb-0">{{ $currency }} <span class="fs-7 fw-normal text-muted">Statement of financial position</span></h2>
                @if(bccomp($group['difference'], '0', 4) === 0)
                    <span class="badge badge-light-success px-4 py-3">Balanced · Assets = Liabilities + Equity</span>
                @else
                    <span class="badge badge-light-danger px-4 py-3">Out of balance: {{ number_format((float) $group['difference'], 4) }} {{ $currency }}</span>
                @endif
            </div>
            <div class="row g-4 mb-5">
                @foreach(['asset' => 'Total assets', 'liability' => 'Total liabilities', 'equity' => 'Total equity'] as $key => $label)
                    <div class="col-md-4">
                        <div class="card h-100"><div class="card-body p-5">
                            <div class="report-label text-muted fw-semibold mb-3">{{ $label }}</div>
                            <div class="fs-2 fw-bold amount">{{ number_format((float) $group[$key], 4) }}</div>
                            <div class="text-muted fs-7 mt-1">{{ $currency }}</div>
                        </div></div>
                    </div>
                @endforeach
            </div>
            <div class="row g-5">
                @foreach(['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity'] as $key => $label)
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header py-4 px-5">
                                <h3 class="card-title fw-bold">{{ $label }}</h3>
                            </div>
                            <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-row-bordered align-middle mb-0 statement-table">
                                    <thead>
                                        <tr class="fw-bold fs-6 text-gray-700">
                                            <th scope="col">Account</th><th scope="col" class="text-end">Balance ({{ $currency }})</th></tr></thead>
                                    <tbody>
                                        @forelse($group['sections'][$key] as $row)
                                            <tr><td><span class="text-muted font-monospace me-3">{{ $row->code }}</span><span class="fw-semibold">{{ $row->name }}</span></td><td class="text-end amount">{{ number_format((float) $row->balance, 4) }}</td></tr>
                                        @empty
                                            <tr><td colspan="2" class="text-muted">No non-zero {{ strtolower($label) }} account balances.</td></tr>
                                        @endforelse
                                        @if($key === 'equity')
                                            <tr><td><div class="fw-semibold">Unclosed earnings / (loss)</div><div class="text-muted fs-7 mt-1">Cumulative revenue less expenses and cost of goods sold, net of posted closing entries.</div></td><td class="text-end amount">{{ number_format((float) $group['earnings'], 4) }}</td></tr>
                                        @endif
                                    </tbody>
                                    <tfoot class="fw-bold"><tr><th scope="row">Total {{ strtolower($label) }}</th><td class="text-end amount">{{ number_format((float) $group[$key], 4) }}</td></tr></tfoot>
                                </table>
                            </div>
                        </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="card mt-5"><div class="card-body p-5 d-flex flex-wrap justify-content-between gap-3">
                <span class="fw-bold fs-4">Total liabilities and equity</span><span class="amount fw-bold fs-4">{{ number_format((float) $group['liabilities_and_equity'], 4) }} {{ $currency }}</span>
            </div></div>
        </section>
    @empty
        <div class="card"><div class="card-body text-center py-10">
            <h2 class="fs-4 mb-3">No balances to display</h2>
            <p class="text-muted mb-0">No posted entries match the selected filters.</p>
        </div></div>
    @endforelse
    <p class="text-muted fs-7 mt-5">Balances include posted entries through the selected date, including earlier periods and archived accounts. Reversed journals remain included until their reversing entries take effect. Negative balances are shown with a minus sign.</p>
</div>
</x-default-layout>
