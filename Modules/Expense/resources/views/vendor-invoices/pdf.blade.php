<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body { font: 10px DejaVu Sans, sans-serif; color: #222; }
        h1 { font-size: 20px; margin: 20px 0 8px; }
        h2 { font-size: 13px; margin-top: 22px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; table-layout: fixed; }
        th, td { border: 1px solid #bbb; padding: 7px; vertical-align: top; overflow-wrap: break-word; }
        th { background: #eee; text-align: left; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .right { text-align: right; }
        .logo { width: 190px; }
        .totals { width: 60%; margin-left: auto; }
    </style>
</head>
<body>
    <img class="logo" src="{{ public_path('assets/media/logos/acemix-logo.png') }}" alt="ACEMIX">
    <h1>Vendor Invoice {{ $invoice->number }}</h1>
    <p>Status: {{ str($invoice->status)->replace('_', ' ')->title() }}@if($invoice->status === 'approved') ({{ $invoice->owner_approved_at ? 'Final approval complete' : 'Awaiting owner review' }})@endif</p>
    <table>
        <tr><th>Vendor</th><td>{{ $invoice->vendor?->name }}</td><th>Vendor Invoice #</th><td>{{ $invoice->vendor_invoice_number }}</td></tr>
        <tr><th>Invoice Date</th><td>{{ $invoice->invoice_date?->format('d M Y') }}</td><th>Due Date</th><td>{{ $invoice->due_date?->format('d M Y') }}</td></tr>
        <tr><th>Billing Month</th><td>{{ \Carbon\Carbon::createFromFormat('Y-m', $invoice->billing_month)->format('F Y') }}</td><th>Currency</th><td>PKR</td></tr>
    </table>
    @if($invoice->vendor?->email)<p>Email: {{ $invoice->vendor->email }}</p>@endif
    @if($invoice->vendor?->phone)<p>Phone: {{ $invoice->vendor->phone }}</p>@endif
    <h2>Invoice Entries</h2>
    <table>
        <thead><tr><th style="width: 40%">Account / Description</th><th>Billing Amount</th><th>GST</th><th>WHT</th></tr></thead>
        <tbody>
            @foreach($invoice->lines as $line)
                <tr><td>{{ $line->debitAccount?->code }} {{ $line->debitAccount?->name }}<br>{{ $line->description }}</td><td class="right">{{ number_format((float) $line->line_subtotal, 2) }}</td><td class="right">{{ number_format((float) $line->line_tax, 2) }}</td><td class="right">{{ number_format((float) $line->line_wht_tax, 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals">
        @foreach(['Billing Amount' => $invoice->subtotal, 'GST (Input Tax)' => $invoice->tax_total, 'Withholding Tax' => $invoice->whttax_total, 'Payable' => $invoice->total, 'Paid Amount' => $invoice->paid_amount, 'Outstanding' => bcsub((string) $invoice->total, (string) $invoice->paid_amount, 4)] as $label => $amount)
            <tr><th>{{ $label }}</th><td class="right">PKR {{ number_format((float) $amount, 2) }}</td></tr>
        @endforeach
    </table>
    @if($notesHtml)<h2>Details</h2>{!! $notesHtml !!}@endif
    <h2>Approval Details</h2>
    <p>Operations approval: {{ $invoice->approved_at?->format('d M Y H:i') ?? 'Pending' }}<br>
        Owner / CEO approval: {{ $invoice->owner_approved_at?->format('d M Y H:i') ?? 'Pending' }}<br>
        Posted: {{ $invoice->posted_at?->format('d M Y H:i') ?? 'Pending' }}</p>
    @if($invoice->rejection_reason)<p>Rejection reason: {{ $invoice->rejection_reason }}</p>@endif
</body>
</html>
