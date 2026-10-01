<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #20242a; margin: 32px; }
        header { display: flex; justify-content: space-between; border-bottom: 2px solid #1f6feb; padding-bottom: 16px; }
        h1 { margin: 0; font-size: 26px; } h2 { font-size: 16px; margin: 24px 0 8px; }
        .muted { color: #666; } .right { text-align: right; } .num { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 12px; }
        th, td { border: 1px solid #d8dee4; padding: 8px; } th { background: #f3f5f7; text-align: left; }
        .totals { margin-left: auto; width: 300px; } .totals td { border: 0; padding: 5px; }
        .total td { border-top: 2px solid #20242a; font-size: 15px; font-weight: bold; }
        button { margin-bottom: 18px; padding: 8px 12px; }
        @media print { button { display: none; } body { margin: 16px; } }
    </style>
</head>
<body>
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
    <header><div><h1>Sales Invoice</h1><div class="muted">{{ config('app.name') }}</div></div><div class="right"><strong>{{ $invoice->number }}</strong><br><span class="muted">{{ $invoice->invoice_date->format('d M Y') }}</span></div></header>
    <table><tr><td><strong>Bill To</strong><br>{{ $invoice->customer?->name }}<br>{{ $invoice->customer?->email }}</td><td><strong>Invoice Details</strong><br>Due: {{ $invoice->due_date->format('d M Y') }}<br>Currency: {{ $invoice->currency_code }}<br>Status: {{ $invoice->status->label() }}</td></tr></table>
    <h2>Items</h2><table><thead><tr><th>#</th><th>Item</th><th>Unit</th><th class="num">Qty</th><th class="num">Price</th><th class="num">GST</th><th class="num">Total</th></tr></thead><tbody>@foreach($invoice->lines as $index => $line)<tr><td>{{ $index + 1 }}</td><td>{{ $line->item?->code }} — {{ $line->item?->name }}</td><td>{{ $line->unit?->code }}</td><td class="num">{{ number_format((float) $line->quantity, 4) }}</td><td class="num">{{ number_format((float) $line->unit_price, 4) }}</td><td class="num">{{ number_format((float) $line->tax_rate, 2) }}%</td><td class="num">{{ number_format((float) $line->line_total, 4) }}</td></tr>@endforeach</tbody></table>
    <table class="totals"><tr><td>Subtotal</td><td class="num">{{ number_format((float) $invoice->subtotal, 4) }}</td></tr><tr><td>GST</td><td class="num">{{ number_format((float) $invoice->tax_total, 4) }}</td></tr><tr class="total"><td>Total</td><td class="num">{{ number_format((float) $invoice->total, 4) }}</td></tr></table>
</body>
</html>
