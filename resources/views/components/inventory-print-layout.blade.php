<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Print</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { margin: 24px; font: 12px Arial, sans-serif; color: #111; background: #fff; }
        h1 { font-size: 22px; margin: 16px 0 8px; }
        a { color: inherit; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { border: 1px solid #aaa; padding: 7px; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #eee; text-align: left; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-muted, .small { color: #555; }
        .card-header, .card-footer, .alert { margin-top: 12px; }
        .print-meta { margin: 8px 0; }
        .print-filters { display: flex; flex-wrap: wrap; gap: 8px 20px; }
        .print-filters div { display: flex; gap: 5px; }
        .print-filters dt { font-weight: bold; }
        .print-filters dd { margin: 0; }
        @media print {
            .print-controls { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>
    <h1>@yield('title')</h1>
    <p class="print-meta">Generated: {{ now()->format('Y-m-d H:i') }}</p>
    <dl class="print-filters">
        @foreach(['search' => 'Search', 'warehouse_id' => 'Warehouse ID', 'item_id' => 'Item ID', 'low_stock' => 'Low stock only', 'type' => 'Movement type', 'reference' => 'Reference', 'from' => 'From', 'to' => 'To', 'status' => 'Status'] as $key => $label)
            @if(is_scalar(request($key)) && request($key) !== '')
                <div><dt>{{ $label }}:</dt><dd>{{ request($key) }}</dd></div>
            @endif
        @endforeach
    </dl>
    {{ $slot }}
</body>
</html>
