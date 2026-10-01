<x-default-layout>
    @section('title', 'Vendor Statement — '.$vendor->name)
    @section('toolbar-button')
        <span id="vendor-statement-exports" class="d-inline-flex gap-2"></span>
        <a href="{{ route('vendors.ledger', $vendor) }}" class="btn btn-outline-secondary btn-sm">Ledger</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print</button>
        <a href="{{ route('vendors.index') }}" class="btn btn-secondary btn-sm">Back</a>
    @endsection

    <form method="GET" action="{{ route('vendors.statement', $vendor) }}" class="row g-2 mb-4 no-print">
        <div class="col-md-3">
            <label for="from" class="form-label">From</label>
            <input id="from" name="from" type="date" value="{{ $from->format('Y-m-d') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="to" class="form-label">To</label>
            <input id="to" name="to" type="date" value="{{ $to->format('Y-m-d') }}" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
            <button class="btn btn-primary">Apply</button>
            <a href="{{ route('vendors.statement', $vendor) }}" class="btn btn-light">Reset</a>
        </div>
        @error('to')<div class="text-danger">{{ $message }}</div>@enderror
        @error('from')<div class="text-danger">{{ $message }}</div>@enderror
    </form>
    <div id="statement-export-error" class="alert alert-danger no-print" role="alert" hidden></div>

    <section class="statement-paper">
        <header class="statement-heading">
            <div>
                <div class="statement-company">{{ $organization?->legal_name ?: $organization?->name }}</div>
                @if($organization?->email)<div>{{ $organization->email }}</div>@endif
                @if($organization?->phone)<div>{{ $organization->phone }}</div>@endif
                @if($organization?->tax_number)<div>Tax ID: {{ $organization->tax_number }}</div>@endif
            </div>
            <div class="statement-heading-right">
                <h1>VENDOR STATEMENT</h1>
                <div>{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
                <small>Generated {{ now()->format('d M Y H:i') }}</small>
            </div>
        </header>
        <div class="statement-party">
            <div><strong>Vendor</strong><h2>{{ $vendor->name }}</h2><div>{{ $vendor->code }}</div>
                @if($vendor->email)<div>{{ $vendor->email }}</div>@endif
                @if($vendor->phone)<div>{{ $vendor->phone }}</div>@endif
            </div>
            <div class="statement-heading-right">
                @if($organization?->currency_code)<div>Currency: <strong>{{ $organization->currency_code }}</strong></div>@endif
                <div>Balance as of {{ $to->format('d M Y') }}</div>
                <div class="statement-closing">{{ number_format((float) $closingBalance, 4) }}</div>
            </div>
        </div>
        <div class="statement-summary">
            @foreach(['Opening balance' => $openingBalance, 'Invoice amount' => $totalInvoices, 'Payment' => $totalPayments, 'WHT' => $totalWht, 'Adjustment' => $totalAdjustments, 'Closing balance' => $closingBalance] as $label => $amount)
                <div><span>{{ $label }}</span><strong>{{ number_format((float) $amount, 4) }}</strong></div>
            @endforeach
        </div>
        <div class="table-responsive">
            <table id="vendor-statement-table" class="table table-row-bordered align-middle mb-0"
                   data-export-title="Vendor Statement — {{ $vendor->name }}"
                   data-export-period="{{ $vendor->code }} | {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}"
                   data-export-company="{{ $organization?->legal_name ?: $organization?->name }}"
                   data-export-currency="{{ $organization?->currency_code }}"
                   data-export-contact="{{ collect([$vendor->email, $vendor->phone])->filter()->implode(' | ') }}"
                   data-export-filename="vendor-statement-{{ $vendor->id }}-{{ $from->format('Y-m-d') }}-{{ $to->format('Y-m-d') }}">
                <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th class="text-end">Invoice Amount</th><th class="text-end">Payment</th><th class="text-end">WHT</th><th class="text-end">Adjustment</th><th class="text-end">Outstanding</th></tr></thead>
                <tbody>
                    <tr class="statement-opening">
                        <td>{{ $from->format('d/m/Y') }}</td><td>—</td><td>Opening balance brought forward</td>
                        @foreach(range(1, 4) as $column)
                            <td class="text-end" data-export-number="0.0000">—</td>
                        @endforeach
                        <td class="text-end" data-export-number="{{ $openingBalance }}">{{ number_format((float) $openingBalance, 4) }}</td>
                    </tr>
                    @foreach($lines as $line)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($line->entry_date)->format('d/m/Y') }}</td>
                            <td>
                                @if(filled($line->reference))
                                    <div>{{ $line->reference }}</div>
                                    <div class="small text-muted">{{ $line->number }}</div>
                                @else
                                    {{ $line->number }}
                                @endif
                            </td>
                            <td>{{ $line->description }} @if($line->line_memo)<div class="small text-muted">{{ $line->line_memo }}</div>@endif</td>
                            @foreach(['invoice_amount', 'payment', 'wht', 'adjustment', 'outstanding'] as $field)
                                <td class="text-end" data-export-number="{{ $line->{$field} }}">{{ number_format((float) $line->{$field}, 4) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr>
                    <td colspan="3">Period totals / closing balance</td>
                    @foreach([$totalInvoices, $totalPayments, $totalWht, $totalAdjustments, $closingBalance] as $amount)
                        <td class="text-end" data-export-number="{{ $amount }}">{{ number_format((float) $amount, 4) }}</td>
                    @endforeach
                </tr></tfoot>
            </table>
        </div>
        @if($lines->isEmpty())<p class="statement-note">No transactions in the selected period. The opening balance is carried forward.</p>@endif
        <p class="statement-note" id="statement-balance-note">Positive balances are payable to the vendor; negative balances represent advances or credits. Invoice Amount includes tax before withholding. Outstanding = Opening + Invoice Amount − Payment − WHT + Adjustment. Only posted entries are included.</p>
    </section>
    <style>
        .statement-paper { background: #fff; color: #182230; padding: 32px; border: 1px solid #dce2e8; border-radius: 8px; }
        .statement-heading, .statement-party { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 22px; margin-bottom: 22px; border-bottom: 1px solid #dce2e8; }
        .statement-company { font-size: 20px; font-weight: 700; }
        .statement-heading h1 { font-size: 22px; letter-spacing: 1px; margin: 0 0 8px; color: #182230; }
        .statement-heading-right { text-align: right; }
        .statement-party h2 { font-size: 18px; margin: 6px 0; color: #182230; }
        .statement-closing { font-size: 24px; font-weight: 700; margin-top: 8px; }
        .statement-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .statement-summary > div { background: #f4f6f8; padding: 14px; border-radius: 4px; }
        .statement-summary span, .statement-summary strong { display: block; }
        .statement-summary span { font-size: 12px; margin-bottom: 6px; }
        .statement-paper th, .statement-paper td { padding: 12px 8px !important; color: #182230 !important; }
        .statement-paper thead, .statement-paper tfoot, .statement-opening { background: #f4f6f8; }
        .statement-paper tfoot { font-weight: bold; }
        .statement-note { color: #556070; font-size: 12px; margin-top: 20px; }
        @media (max-width: 600px) { .statement-paper { padding: 16px; } .statement-summary { grid-template-columns: repeat(2, 1fr); } .statement-heading, .statement-party { flex-direction: column; } .statement-heading-right { text-align: left; } }
        @media print {
            @page { size: A4 landscape; margin: 12mm; }
            #kt_app_header, #kt_app_sidebar, #kt_app_toolbar, #kt_app_footer, #kt_scrolltop, .no-print, .drawer, [data-kt-drawer="true"], .drawer-overlay, .modal, .modal-backdrop, .page-loader, #toastr-container { display: none !important; }
            html, body { background: #fff !important; height: auto !important; overflow: visible !important; }
            #kt_app_root, #kt_app_page, #kt_app_wrapper, #kt_app_main, #kt_app_main > .d-flex, #kt_app_content, #kt_app_content_container { display: block !important; position: static !important; width: 100% !important; max-width: none !important; min-height: 0 !important; margin: 0 !important; padding: 0 !important; transform: none !important; overflow: visible !important; }
            .statement-paper { border: 0; padding: 0; }
            .table-responsive { overflow: visible !important; }
            thead { display: table-header-group; } tfoot { display: table-row-group; }
            tr, .statement-heading, .statement-party, .statement-summary { break-inside: avoid; }
        }
    </style>
    @push('scripts')
        <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/custom/vendor-statement-exports.js') }}?v=4"></script>
    @endpush
</x-default-layout>
