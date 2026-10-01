<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Inventory Valuation Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 28px; color: #212529; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #dee2e6; padding: 7px; }
        th { background: #f1f3f5; text-align: left; }
        .number { text-align: right; }
        .muted { color: #6c757d; }
        button { margin-bottom: 20px; }
        @media print { button { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
    <h1>Inventory Valuation Report</h1>
    <p class="muted">
        Valuation method: {{ ucwords(str_replace('_', ' ', $method)) }}
        @if($warehouse) · Warehouse: {{ $warehouse->name }} @endif
        · Generated: {{ now()->format('d M Y H:i') }}
    </p>
    <table>
        <thead>
            <tr>
                <th>Item</th><th>Unit</th><th class="number">On Hand</th><th class="number">Reserved</th><th class="number">Available</th><th class="number">Avg. Unit Cost</th><th class="number">Total Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->item?->code }} — {{ $row->item?->name }}</td>
                    <td>{{ $row->item?->unit?->code ?? '—' }}</td>
                    <td class="number">{{ number_format((float) $row->total_quantity, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->total_reserved, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->total_available, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->avg_unit_cost, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->total_value, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No stock to value.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
