<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Inventory Summary Report</title>
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
    <h1>Inventory Summary Report</h1>
    <p class="muted">
        @if($warehouse) Warehouse: {{ $warehouse->name }} · @endif Generated: {{ now()->format('d M Y H:i') }}
    </p>
    <table>
        <thead><tr><th>Warehouse</th><th class="number">Distinct Items</th><th class="number">On Hand</th><th class="number">Reserved</th><th class="number">Available</th><th class="number">Inventory Value</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->warehouse?->code }} — {{ $row->warehouse?->name }}</td>
                    <td class="number">{{ number_format((int) $row->item_count) }}</td>
                    <td class="number">{{ number_format((float) $row->total_quantity, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->total_reserved, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->total_available, 2) }}</td>
                    <td class="number">{{ number_format((float) $row->total_value, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No warehouse stock records.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
