<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Awarded RFQ {{ $rfq->number }}</title>
    <style>
        body { color: #111827; font: 14px/1.45 Arial, sans-serif; margin: 28px; }
        h1 { font-size: 24px; margin: 0 0 5px; }
        h2 { font-size: 16px; margin: 26px 0 8px; }
        .header { display: flex; justify-content: space-between; gap: 24px; }
        .muted { color: #6b7280; }
        .status { color: #047857; font-weight: 700; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #9ca3af; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .amount { text-align: right; white-space: nowrap; }
        .signatures { display: flex; gap: 72px; margin-top: 65px; }
        .signature { border-top: 1px solid #374151; min-width: 210px; padding-top: 7px; text-align: center; }
        @media print { body { margin: 14mm; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 18px; text-align: right;">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="header">
        <div>
            <h1>AWARDED REQUEST FOR QUOTATION</h1>
            <div class="muted">{{ config('app.name') }}</div>
        </div>
        <div>
            <strong>{{ $rfq->number }}</strong><br>
            <span class="status">AWARDED</span><br>
            {{ $rfq->awarded_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}
        </div>
    </div>

    <h2>Award Details</h2>
    <table>
        <tbody>
            <tr>
                <th>Award</th><td>Vendor selected separately for each product below</td>
                <th>Currency</th><td>{{ $rfq->currency_code }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>{{ $rfq->department?->name ?? '—' }}</td>
                <th>Cost Center</th>
                <td>{{ $rfq->costCenter?->name ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <h2>RFQ Items</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Specification</th>
                <th>Awarded Vendor</th>
                <th class="amount">Requested Qty</th>
                <th>Unit</th>
                <th class="amount">Awarded Unit Price</th>
                <th class="amount">Subtotal before tax</th>
            </tr>
        </thead>
        <tbody>
            @php($awardedLines = $rfq->awardedQuotation?->lines->keyBy('rfq_line_id') ?? collect())
            @foreach($rfq->lines as $index => $line)
                @php($awardedLine = $line->awardedQuotationLine ?? $awardedLines->get($line->id))
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->item?->code }} — {{ $line->item?->name }} <span class="d-block small text-muted">Brand: {{ $line->brand?->name ?? "—" }}</span></td>
                    <td>{{ $line->specification ?: '—' }}</td>
                    <td>{{ $awardedLine?->quotation?->vendor?->name ?? '—' }}</td>
                    <td class="amount">{{ number_format((float) $line->quantity, 4) }}</td>
                    <td>{{ $line->unit?->code ?? '—' }}</td>
                    <td class="amount">{{ $awardedLine ? number_format((float) $awardedLine->unit_price, 4) : '—' }}</td>
                    <td class="amount">{{ $awardedLine ? number_format((float) $awardedLine->line_total, 2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($rfq->purpose)
        <h2>Purpose</h2>
        <div style="white-space: pre-wrap;">{{ $rfq->purpose }}</div>
    @endif

    <div class="signatures">
        <div class="signature">Prepared By</div>
        <div class="signature">Approved / Awarded By</div>
        <div class="signature">Vendor Acceptance</div>
    </div>
</body>
</html>
