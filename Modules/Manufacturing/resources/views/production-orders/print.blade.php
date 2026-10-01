<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Production {{ $order->number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 20px; }
        h1 { margin: 0 0 6px; font-size: 24px; }
        h2 { margin: 24px 0 8px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px; text-align: left; }
        th { background: #eee; }
        .text-end { text-align: right; }
        .summary td { width: 25%; }
        .ingredient-table, .calcium-table { table-layout: fixed; }
        .ingredient-table .number-column { width: 4%; }
        .ingredient-table .ingredient-column { width: 24%; }
        .ingredient-table .batch-column { width: 20%; }
        .ingredient-table .unit-column { width: 7%; }
        .ingredient-table .quantity-column { width: 12%; }
        .ingredient-table .actual-column { width: 17%; }
        .ingredient-table .mix-column { width: 17%; }
        .calcium-table { width: 100%; }
        .calcium-table .item-column { width: 70%; }
        .calcium-table .calcium-quantity-column { width: 15%; }
        .calcium-table .calcium-unit-column { width: 15%; }
        .signatures { margin-top: 65px; display: flex; gap: 72px; }
        .signature { width: 260px; border-top: 1px solid #111; padding-top: 7px; }
        .dated { width: 100px; border-top: 1px solid #111; padding-top: 7px; }
        .printed { width: 160px;  }
        @media print { .no-print { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:right; margin-bottom:16px"><button onclick="window.print()">Print</button></div>
    <h1>PRODUCTION ORDER</h1>
    <table class="summary">
        <tr>
            <th>Production No.</th>
            <td>{{ $order->number }}</td>
            <th>Date</th>
            <td>{{ now()->format('d M Y') }}</td>
        </tr>
        <tr
        ><th>Product Name</th><td>{{ $order->product?->name ?? '—' }}</td><th>Warehouse</th><td>{{ $order->sourceWarehouse?->name ?? '—' }}</td></tr>
        <tr>
            <th>Batch Size</th>
            <td>{{ number_format((float) $order->planned_quantity, 2) }} KG</td>
            <th>Total Production</th>
            <td>
                {{-- {{ number_format((float) $order->produced_quantity, 2) }} KG --}}
            </td>
        </tr>
        @if(filled($order->notes))
            <tr>
                <td colspan="4">
                    <b>{{ $order->notes }}</b>
                </td>
            </tr>
        @endif
    </table>

    @php
        $calciumCarbonateLines = $order->lines->filter(
            fn ($line) => str_contains(mb_strtolower((string) $line->component?->name), 'calcium carbonate'),
        );
        $ingredientLines = $order->lines->reject(
            fn ($line) => $calciumCarbonateLines->contains('id', $line->id),
        );
        $ingredientQuantity = $ingredientLines->sum(fn ($line) => (float) $line->required_quantity);
        $calciumCarbonateQuantity = $calciumCarbonateLines->sum(fn ($line) => (float) $line->required_quantity);
    @endphp

    <h2>Ingredients</h2>
    <table class="ingredient-table">
        <colgroup>
            <col class="number-column">
            <col class="ingredient-column">
            <col class="batch-column">
            <col class="unit-column">
            <col class="quantity-column">
            <col class="actual-column">
            <col class="mix-column">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Ingredient</th>
                <th>Batch Number</th>
                <th>Unit</th>
                <th class="text-end">Quantity</th>
                <th class="text-end">AW 1 (KG)</th>
                <th class="text-end">W2 (KG)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ingredientLines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->component?->name }}</td>
                    <td>{{ $line->batch?->number ?? '—' }}</td>
                    <td>{{ $line->unit?->code ?? 'KG' }}</td>
                    <td class="text-end">{{ number_format((float) $line->required_quantity, 3) }}</td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Total Micro</th>
                <th class="text-end">{{ number_format($ingredientQuantity, 3) }}</th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>

    @if($calciumCarbonateLines->isNotEmpty())
        <table class="ingredient-table calcium-table">
            <colgroup>
                <col class="number-column">
                <col class="ingredient-column">
                <col class="batch-column">
                <col class="unit-column">
                <col class="quantity-column">
                <col class="actual-column">
                <col class="mix-column">
            </colgroup>

           @php
            $materialTotal = 0;
            $waste = 0;
        @endphp

        <tbody>
            @foreach($calciumCarbonateLines as $index => $line)
                @php
                    $requiredQty = (float) $line->required_quantity - (float) $line->waste_quantity;
                    $materialTotal += $requiredQty;
                    $waste +=  (float) $line->waste_quantity;
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->component?->name ?? '—' }}</td>
                    <td>{{ $line->batch?->number ?? '—' }}</td>
                    <td>{{ $line->unit?->code ?? 'KG' }}</td>

                    <td class="text-end">
                        {{ number_format($requiredQty , 2 ) }}
                    </td>

                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>

        @php
            $carrierTotal = $materialTotal;
            $grandTotal = $ingredientQuantity + $carrierTotal;
        @endphp

        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Total Carrier</th>
                <th class="text-end">
                    {{ number_format($carrierTotal, 3) }}
                </th>
                <th colspan="2"></th>
            </tr>

            <tr>
                <th colspan="4" class="text-end">Grand Total</th>
                <th class="text-end">
                    {{ number_format($grandTotal, 3) }}
                </th>
                <th colspan="2"></th>
            </tr>

        </tfoot>
        </table>
    @endif

    <div class="signatures">
        <div class="signature">Total Materials use in Production (KG)</div>
        <div class="signature">Production Manager Signature</div>
        <div class="dated">Date</div>
        <div class="printed">Printed by: {{ auth()->user()->name ?? '' }}</div>
    </div>
</body>
</html>
