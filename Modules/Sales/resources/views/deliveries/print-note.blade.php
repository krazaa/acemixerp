<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Delivery Note {{ $delivery->number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-size: 11px; color: #000; }
        .doc { page-break-after: always; padding: 6mm; border: 1px solid #999; margin-bottom: 8mm; position: relative; }
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
            .doc { border: 1px solid #999; margin-bottom: 6mm; }
        }
    </style>
</head>
<body>

@php($bagsPerUnit = 25)

<div class="no-print text-end mb-2">
    <button class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="bi bi-printer"></i> Print
    </button>
    <a href="{{ route('sales.deliveries.show', $delivery) }}" class="btn btn-outline-secondary btn-sm">
        Back
    </a>
</div>

@foreach(['TRANSPORT COPY', 'CUSTOMER COPY','OFFICE COPY'] as $copyLabel)
<div class="doc">
    <div class="copy-label">{{ $copyLabel }}</div>

    <table class="header-table w-100 mb-2" style="border-bottom: 2px solid #000;">
        <tr>
            <td style="width: 70%;">
                <img src="{{ asset('assets/media/logos/acemix-logo.png') }}" width="300px">

                <div>

                </div>
            </td>
            <td style="width: 30%; text-align: right;">
                <div style="font-size: 14px; font-weight: 700; margin-top: 15px;">DELIVERY CHALLAN</div>
                <div><strong>No:</strong> {{ $delivery->number }}</div>
                <div><strong>Date:</strong> {{ $delivery->delivery_date->format('d/m/Y') }}</div>
                @if($delivery->expected_date)
                    <div><strong>Expected Delivery:</strong> {{ $delivery->expected_date->format('d/m/Y') }}</div>
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

    <table class="header-table w-100 mb-2">
        <tr>
            <td style="width: 50%;">
                <div style="font-weight: 700; margin-bottom: 4px;">DELIVER TO:</div>
                <div><strong>{{ $delivery->customer?->name }}</strong></div>
                @if($delivery->customer?->legal_name && $delivery->customer->legal_name !== $delivery->customer->name)
                    <div>{{ $delivery->customer->legal_name }}</div>
                @endif

                {{-- @if($delivery->shipping_address) --}}
                    {{-- <div>{{ $delivery->shipping_address }}</div> --}}
                {{-- @endif --}}
                <div>{{  $delivery->customer->shippingAddress()->address_line1 }},{{  $delivery->customer->shippingAddress()->city ?? '' }} {{  $delivery->customer->shippingAddress()->postal_code ?? '' }}</div>
                <div>{{  $delivery->customer->shippingAddress()->contact_name }}</div>
                <div>{{  $delivery->customer->shippingAddress()->contact_phone }}</div>
                {{-- @if($delivery->customer?->phone)
                    <div> {{ $delivery->customer->phone }}</div>
                @endif --}}

            </td>
            <td style="width: 50%;">
                <div style="font-weight: 700; margin-bottom: 4px;">SHIP FROM:</div>
                <div>{{ $delivery->warehouse?->name }}</div>
                @php($whAddr = $delivery->warehouse?->addresses?->first())
                @if($whAddr)
                    <div>{{ $whAddr->oneLine() }}</div>
                @endif
                    @if($organization->phone) Phone: {{ $organization->phone }}@endif
                @if($organization->email) · {{ $organization->email }}@endif
                      @if($organization->tax_number)
                    <div>Tax No: {{ $organization->tax_number }}</div>
                    @endif
                <div style="margin-top: 6px;">
                    <strong>Transport Vendor:</strong>
                    {{ $delivery->transportVendor?->name ?? 'Own Fleet' }}
                </div>
                @if($delivery->transporter_reference)
                    <div><strong>Transporter Ref:</strong> {{ $delivery->transporter_reference }}</div>
                @endif
            </td>
        </tr>
    </table>

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

    @php($totalBags = 0)
    @php($totalQuantity = 0)
    @php($totalValue = 0)

    <table class="table table-sm table-bordered mb-2" style="font-size: 11px;">
        <thead style="background: #e9ecef;">
            <tr>
                <th style="width: 30px;">#</th>
                <th>Item Description</th>
                <th style="width: 60px;">Batch No</th>
                <th style="width: 80px;" class="text-end">Quantity</th>
                <th style="width: 60px;">Unit</th>
                <th style="width: 70px;" class="text-center">Packing</th>
                <th style="width: 70px;" class="text-end">Total Bags</th>
            </tr>
        </thead>
        <tbody>
            @forelse($delivery->lines as $i => $line)
                @php($qty = (float) $line->quantity)
                @php($price = (float) $line->unit_price)
                @php($lineBags = $bagsPerUnit > 0 ? (int) ceil($qty / $bagsPerUnit) : 0)
                @php($lineValue = $qty * $price)
                @php($totalBags += $lineBags)
                @php($totalQuantity += $qty)
                @php($totalValue += $lineValue)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    {{-- <td>{{ $line->item?->code }}</td> --}}
                    <td>
                        {{ $line->item?->name }}
                        @if($line->notes)
                            <div style="font-size: 10px; color: #666;">{{ $line->notes }}</div>
                        @endif
                    </td>
                    <td>ACE109-169</td>
                    <td class="text-end">{{ number_format($qty, 0) }}</td>
                    <td>{{ $line->unit?->code ?? '—' }}</td>

                    <td class="text-center">{{ $bagsPerUnit }} KG</td>
                    <td class="text-end">{{ $lineBags }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No lines.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f4f4f4;">
                <td colspan="3" class="text-end"><strong>Totals</strong></td>
                <td class="text-end"><strong>{{ number_format($totalQuantity, 0) }}</strong></td>
                <td></td>
                <td></td>
                <td class="text-end"><strong>{{ $totalBags }}</strong></td>
            </tr>
        </tfoot>
    </table>

    @if($delivery->notes)
        <div style="margin-top: 6px; padding: 6px; border: 1px dashed #999;">
            <strong>Notes:</strong> {{ $delivery->notes }}
        </div>
    @endif

    <table class="w-100" style="margin-top: 220px; font-size: 11px;">
        <tr>
             <td style="width: 33%; padding-right: 8px;">
                <div class="sig-line"></div>
                <div style="margin-top: 4px;">Issued By</div>
                @if($delivery->creator)
                    <div style="font-size: 10px; color: #666;">{{ $delivery->creator->name }}</div>
                @endif
            </td>
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

        </tr>
    </table>
     <table class="w-100" style="margin-top: 20px; font-size: 11px;">
        <tr></tr>
            <td style="width: 33%; padding-left: 8px;">
                <div class="sig-line"></div>
                <div style="margin-top: 4px;">Received By (Customer)</div>
                <div style="font-size: 10px; color: #666;">Name</div>
                <div style="font-size: 10px; color: #666;">Number </div>
                <div style="font-size: 10px; color: #666;">Signature </div>
                <div style="font-size: 10px; color: #666;">Date </div>
            </td>
        </tr>
    </table>
     <div style="margin-top: 6px; padding: 6px; border: 1px dashed #999;">
            <strong>Terms & Conditions:</strong>
            <p>Above mentioned ACEMIX solutions are delivered as per your purchase order.
                <br>Product once delivered cannot be returned or exchanged.
                <br>In cas of any discrepancies please contact within 4 days of order dispatch/receiving.
            </p>
        </div>

    <div style="margin-top: 20px; font-size: 9px; color: #666; text-align: center; border-top: 1px solid #ccc; padding-top: 4px;">
        Printed {{ now()->format('d/mY H:i A') }}
    </div>
</div>
@endforeach

<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 200);
    });
</script>
</body>
</html>
