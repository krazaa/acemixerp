<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Delivery Note {{ $delivery->number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-size: 11px; color: #000; }
        .doc { page-break-after: always; padding: 6mm; border: 1px solid #999; margin-bottom: 8mm; }
        .doc:last-child { page-break-after: auto; }
        .header-table td { vertical-align: top; padding: 4px; }
        .sig-line { border-bottom: 1px solid #000; height: 30px; }
        .table-sm th, .table-sm td { padding: 4px; }
        .copy-label {
            position: absolute;
            top: 6mm; right: 6mm;
            font-size: 10px; font-weight: 700; letter-spacing: 1px;
            color: #666;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print text-end mb-2">
    <button class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="bi bi-printer"></i> Print
    </button>
    <a href="{{ route('sales.deliveries.show', $delivery) }}" class="btn btn-outline-secondary btn-sm">
        Back
    </a>
</div>

@foreach(['TRANSPORT COPY', 'CUSTOMER COPY'] as $copyLabel)
    <div class="doc position-relative">
        <div class="copy-label">{{ $copyLabel }}</div>

        {{-- Header --}}
        <table class="header-table w-100 mb-2" style="border-bottom: 2px solid #000;">
            <tr>
                <td style="width: 70%;">
                    <div style="font-size: 16px; font-weight: 700;">{{ $organization->name }}</div>
                    @if($organization->address_line1)
                        <div>{{ $organization->address_line1 }}</div>
                    @endif
                    @if($organization->address_line2)
                        <div>{{ $organization->address_line2 }}</div>
                    @endif
                    <div>
                        {{ $organization->city }}@if($organization->state), {{ $organization->state }}@endif
                        @if($organization->postal_code) - {{ $organization->postal_code }}@endif
                    </div>
                    <div>
                        @if($organization->phone) Phone: {{ $organization->phone }}@endif
                        @if($organization->email) · {{ $organization->email }}@endif
                    </div>
                    @if($organization->tax_number)
                        <div>Tax No: {{ $organization->tax_number }}</div>
                    @endif
                </td>
                <td style="width: 30%; text-align: right;">
                    <div style="font-size: 14px; font-weight: 700; margin-top: 15px;">DELIVERY CHALLAN</div>
                    <div><strong>No:</strong> {{ $delivery->number }}</div>
                    <div><strong>Date:</strong> {{ $delivery->delivery_date->format('Y-m-d') }}</div>
                    @if($delivery->expected_date)
                        <div><strong>Expected:</strong> {{ $delivery->expected_date->format('Y-m-d') }}</div>
                    @endif
                    @if($delivery->salesOrder)
                        <div><strong>Sales Order:</strong> {{ $delivery->salesOrder->number }}</div>
                    @endif
                    @if($delivery->reference)
                        <div><strong>Ref:</strong> {{ $delivery->reference }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Parties --}}
        <table class="header-table w-100 mb-2">
            <tr>
                <td style="width: 50%;">
                    <div style="font-weight: 700; margin-bottom: 4px;">DELIVER TO:</div>
                    <div><strong>{{ $delivery->customer?->name }}</strong></div>
                    @if($delivery->customer?->legal_name && $delivery->customer->legal_name !== $delivery->customer->name)
                        <div>{{ $delivery->customer->legal_name }}</div>
                    @endif
                    @if($delivery->shipping_address)
                        <div>{{ $delivery->shipping_address }}</div>
                    @elseif($delivery->customer?->shippingAddress())
                        <div>{{ $delivery->customer->shippingAddress()->oneLine() }}</div>
                    @endif
                    @if($delivery->customer?->phone)
                        <div>Phone: {{ $delivery->customer->phone }}</div>
                    @endif
                    @if($delivery->customer?->tax_number)
                        <div>Tax No: {{ $delivery->customer->tax_number }}</div>
                    @endif
                </td>
                <td style="width: 50%;">
                    <div style="font-weight: 700; margin-bottom: 4px;">SHIP FROM:</div>
                    <div>{{ $delivery->warehouse?->name }}</div>
                    @php($whAddr = $delivery->warehouse?->addresses?->first())
                    @if($whAddr)
                        <div>{{ $whAddr->oneLine() }}</div>
                    @endif
                    <div style="margin-top: 6px;"><strong>Transport Vendor:</strong>
                        {{ $delivery->vendor?->name ?? 'Own Fleet' }}
                    </div>
                    @if($delivery->transporter_reference)
                        <div><strong>Transporter Ref:</strong> {{ $delivery->transporter_reference }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Transport details --}}
        @if($delivery->vehicle_number || $delivery->driver_name || $delivery->bilty_number)
            <table class="header-table w-100 mb-2" style="background: #f4f4f4; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc;">
                <tr>
                    @if($delivery->vehicle_number)
                        <td><strong>Vehicle No:</strong> {{ $delivery->vehicle_number }}</td>
                    @endif
                    @if($delivery->bilty_number)
                        <td><strong>Bilty No:</strong> {{ $delivery->bilty_number }}</td>
                    @endif
                    @if($delivery->driver_name)
                        <td><strong>Driver:</strong> {{ $delivery->driver_name }}</td>
                    @endif
                    @if($delivery->driver_contact)
                        <td><strong>Contact:</strong> {{ $delivery->driver_contact }}</td>
                    @endif
                    @if($delivery->driver_cnic)
                        <td><strong>CNIC:</strong> {{ $delivery->driver_cnic }}</td>
                    @endif
                </tr>
            </table>
        @endif

        {{-- Lines --}}
        <table class="table table-sm table-bordered mb-2" style="font-size: 11px;">
            <thead style="background: #e9ecef;">
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 90px;">Code</th>
                    <th>Item Description</th>
                    <th style="width: 80px;" class="text-end">Quantity</th>
                    <th style="width: 60px;">Unit</th>
                    <th style="width: 60px;">Packing</th>
                    <th style="width: 60px;">Total Bags</th>
                    {{-- <th style="width: 100px;" class="text-end">Unit Price</th>
                    <th style="width: 110px;" class="text-end">Line Total</th> --}}
                </tr>
            </thead>
            <tbody>
                  @php
                    $totalbags = 0;
                    $totalQuantity = 0;
                    $totalValue = 0;
                @endphp

                @foreach($delivery->lines as $i => $line)
                    @php
                        $qty = (float) $line->quantity;
                        $price = (float) $line->unit_price;
                        $lineBags = ceil($qty / 25);
                        $totalbags += $lineBags;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $line->item?->code }}</td>
                        <td>
                            {{ $line->item?->name }}
                            @if($line->notes)
                                <div style="font-size: 10px; color: #666;">{{ $line->notes }}</div>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($qty, 0) }}</td>
                        <td>{{ $line->unit?->name ?? '—' }}</td>
                        <td>25 KG </td>

                        <td class="text-end">{{ $lineBags }}</td>
                        {{-- <td class="text-end">{{ number_format($price, 2) }}</td>
                        <td class="text-end">{{ number_format($qty * $price, 2) }}</td> --}}
                    </tr>


                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end"><strong>Total Quantity / Value</strong></td>
                    <td class="text-end">
                        <strong>{{ $totalbags }}</strong>
                        <div>{{ number_format((float) $delivery->lines->sum(fn ($l) => (float) $l->quantity * (float) $l->unit_price), 2) }}</div>
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- Notes --}}
        @if($delivery->notes)
            <div style="margin-top: 6px; padding: 6px; border: 1px dashed #999;">
                <strong>Notes:</strong> {{ $delivery->notes }}
            </div>
        @endif

        {{-- Signatures --}}
        <table class="w-100" style="margin-top: 20px; font-size: 11px;">
            <tr>
                <td style="width: 33%; padding-right: 8px;">
                    <div class="sig-line"></div>
                    <div style="margin-top: 4px;">Dispatched By</div>
                    @if($delivery->dispatcher)
                        <div style="font-size: 10px; color: #666;">{{ $delivery->dispatcher->name }}</div>
                    @endif
                </td>
                <td style="width: 33%; padding: 0 8px;">
                    <div class="sig-line"></div>
                    <div style="margin-top: 4px;">Driver / Transporter</div>
                    @if($delivery->driver_name)
                        <div style="font-size: 10px; color: #666;">{{ $delivery->driver_name }}</div>
                    @endif
                </td>
                <td style="width: 33%; padding-left: 8px;">
                    <div class="sig-line"></div>
                    <div style="margin-top: 4px;">Received By (Customer)</div>
                    <div style="font-size: 10px; color: #666;">Name / Signature / Date</div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 12px; font-size: 9px; color: #666; text-align: center; border-top: 1px solid #ccc; padding-top: 4px;">
            This delivery note is a formal record of goods dispatched. Any discrepancy must be reported within 24 hours.
            · Printed {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
@endforeach

<script>
    // Auto-open print dialog once assets settle
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 200);
    });
</script>
</body>
</html>
