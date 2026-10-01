<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Purchase Order - {{ $purchaseOrder->number }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }
        /* Remove browser print margin area so URL/date/page headers do not have space. */
        @page {
            size: A4;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #1f2937;
            background: #ffffff;
        }

        .page {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
        }

        /* HEADER */
        .header {
            display: table;
            width: 100%;
            padding-bottom: 7px;
            border-bottom: 3px solid #009846;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .header-left {
            width: 55%;
        }

        .header-right {
            width: 45%;
            text-align: right;
        }

        .company-logo {
            max-height: 70px;
            max-width: 240px;
            object-fit: contain;
        }

        .company-name {
            margin-top: 6px;
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .company-address {
            margin-top: 3px;
            color: #4b5563;
            line-height: 1.4;
        }

        .document-title {
            font-size: 27px;
            font-weight: 700;
            color: #009846;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .document-subtitle {
            margin-top: 4px;
            font-size: 11px;
            color: #6b7280;
        }

        /* PO META */
        .po-meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0px;
        }

        .po-meta td {
            width: 25%;
            border: 1px solid #d1d5db;
            padding: 2px 10px;
        }

        .meta-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        /* SECTIONS */
        .section {
            margin-top: 8px;
        }

        .section-title {
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            background: #888c94;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* ADDRESS BLOCKS */
        .two-column {
            width: 100%;
            border-collapse: collapse;
        }

        .two-column td {
            width: 50%;
            vertical-align: top;
            border: 1px solid #d1d5db;
            padding: 4px 12px;
        }

        .supplier-table {
            width: 100%;
            table-layout: fixed;
        }

        .supplier-table .supplier-main {
            width: 68%;
        }

        .supplier-table .supplier-tax {
            width: 32%;
        }

        .block-title {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #009846;
        }

        .strong {
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }

        /* TERMS */
        .terms-table {
            width: 100%;
            border-collapse: collapse;
        }

        .terms-table td {
            border: 1px solid #d1d5db;
            padding: 4px 10px;
        }

        .terms-label {
            width: 22%;
            font-weight: 700;
            background: #f3f4f6;
        }

        /* INSTRUCTIONS */
        .instructions {
            margin-top: 7px;
            padding: 4px 12px;
            border-left: 4px solid #009846;
            background: #f9fafb;
            line-height: 1.55;
        }

        .instructions strong {
            color: #111827;
        }

        /* ITEMS */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table th {
            background: #009846;
            color: #ffffff;
            padding: 4px 7px;
            border: 1px solid #00863d;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 4px 7px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .items-table tfoot td {
            padding: 6px 7px;
            border: 1px solid #d1d5db;
            background: #f3f4f6;
            font-weight: 700;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .item-name {
            font-weight: 700;
            color: #111827;
        }

        .item-description {
            margin-top: 3px;
            font-size: 11px;
            color: #6b7280;
        }

        /* TOTALS */
        .summary-wrapper {
            width: 100%;
            margin-top: 12px;
        }

        .summary-table {
            width: 42%;
            margin-left: auto;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 2px 9px;
            border: 1px solid #d1d5db;
        }

        .summary-label {
            font-weight: 600;
            background: #f9fafb;
        }

        .grand-total td {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            background: #374151;
        }

        /* AMOUNT WORDS */
        .amount-words {
            margin-top: 12px;
            padding: 9px 11px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .amount-words-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
        }

        .amount-words-value {
            margin-top: 0px;
            font-weight: 700;
            color: #111827;
        }

        /* NOTES */
        .notes {
            margin-top: 16px;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
        }

        /* SIGNATURES */
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 55px;
        }

        .signatures td {
            width: 33.333%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 15px;
        }

        .signature-line {
            border-top: 1px solid #374151;
            padding-top: 6px;
            font-weight: 700;
        }

        .signature-name {
            margin-top: 3px;
            font-size: 10px;
            color: #6b7280;
        }

        /* FOOTER */
        .footer {
            margin-top: 30px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }

        .print-actions {
            max-width: 190mm;
            margin: 15px auto;
            text-align: right;
        }

        .btn-print {
            border: none;
            border-radius: 5px;
            padding: 10px 18px;
            background: #009846;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        @media print {
            html,
            body {
                width: 210mm;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Keep professional A4 spacing inside the document itself. */
            .page {
                width: 210mm;
                max-width: none;
                margin: 0;
                padding: 10mm;
            }

            .print-actions {
                display: none !important;
            }

            tr,
            td,
            th {
                page-break-inside: avoid;
            }
        }
        @if($pdf ?? false)
            @page { margin: 10mm; }
            .page { width: 100%; max-width: none; margin: 0; padding: 0; }
            .company-logo { width: 200px; height: auto; }
            .amount-words { display: block !important; }
        @endif
    </style>
</head>

<body>

@unless($pdf ?? false)
<div class="print-actions">
    <a href="{{ route('procurement.purchase-orders.print', ['purchaseOrder' => $purchaseOrder, 'download' => 1]) }}" class="btn-print" style="text-decoration: none;">Download PDF</a>
    <button type="button"
            class="btn-print"
            onclick="window.print()">
        Print Purchase Order
    </button>
</div>
@endunless


<div class="page">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="header">
        <div class="header-left">
           @if($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="{{ $company->name ?? 'Company Logo' }}" class="company-logo">
        @else
            <div class="company-name">
                {{ $company?->name ?? config('app.name') }}
            </div>
        @endif

            {{-- <div class="company-address">

                @if($company?->address)
                    {{ $company->address }}<br>
                @endif

                @if($company?->phone)
                    Tel: {{ $company->phone }}
                @endif

                @if($company?->email)
                    &nbsp; | &nbsp;
                    {{ $company->email }}
                @endif

            </div> --}}

              {{-- <div class="company-address">

                @if($company?->strn)
                    <b>NTN & STRN:</b> {{ $company->strn }}
                @endif
            </div> --}}

        </div>


        <div class="header-right">

            <div class="document-title">
                Purchase Order
            </div>

            <div class="document-subtitle">
                <b>Issued by: </b>{{ strtoupper(strtolower($purchaseOrder->creator->name ?? '')) }}
            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- PURCHASE ORDER INFO --}}
    {{-- ========================================================= --}}

    <table class="po-meta">

        <tr>

            <td>
                <span class="meta-label">
                    Purchase Order No.
                </span>

                <span class="meta-value">
                    {{ $purchaseOrder->number }}
                </span>
            </td>

            <td>
                <span class="meta-label">
                    PO Date
                </span>

                <span class="meta-value">
                    {{ $purchaseOrder->order_date
                        ? \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d/m/Y')
                        : '-' }}
                </span>
            </td>

            <td>
                <span class="meta-label">
                    Currency
                </span>

                <span class="meta-value">
                    {{ $purchaseOrder->currency_code }}
                </span>
            </td>

            <td>
                <span class="meta-label">
                    Status
                </span>

                <span class="meta-value">
                    {{ ucfirst(
                        $purchaseOrder->status?->value
                        ?? $purchaseOrder->status
                        ?? 'Approved'
                    ) }}
                </span>
            </td>

        </tr>

    </table>


    {{-- ========================================================= --}}
    {{-- VENDOR --}}
    {{-- ========================================================= --}}

    <div class="section">

        <div class="section-title">Supplier Information</div>

        <table class="two-column supplier-table">
            <tr>
                <td class="supplier-main">
                    <div class="block-title">Vendor / Supplier</div>
                    <div class="strong">{{ $purchaseOrder->vendor?->name ?? 'N/A' }}</div>

                    @if($purchaseOrder->vendor?->address)
                        <div>{{ $purchaseOrder->vendor->address }}</div>
                    @endif

                    @if($purchaseOrder->vendor?->city)
                        <div>
                            {{ $purchaseOrder->vendor->city }}
                        </div>
                    @endif

                    @if($purchaseOrder->vendor?->contact_person)
                        <div>
                            <strong>Contact Person:</strong>
                            {{ $purchaseOrder->vendor->contact_person }}
                        </div>
                    @endif

                    @if($purchaseOrder->vendor?->phone)
                        <div>
                            <strong>Phone:</strong>
                            {{ $purchaseOrder->vendor->phone }}
                        </div>
                    @endif

                    @if($purchaseOrder->vendor?->email)
                        <div>
                            <strong>Email:</strong>
                            {{ $purchaseOrder->vendor->email }}
                        </div>
                    @endif

                    @php
                        $vendorAddress = $purchaseOrder->vendor?->addresses
                            ?->firstWhere('is_primary', 'office');
                    @endphp

                    <div>
                    @if($vendorAddress)
                        <strong>Address:</strong>
                            {{ $vendorAddress->address_line1 }},
                            {{ $vendorAddress->city }}
                    @endif
                    </div>
                </td>

                <td class="supplier-tax">
                    <div class="block-title">Tax / Registration Information</div>

                    @if($purchaseOrder->vendor?->tax_number)
                        <div>
                            <strong>NTN:</strong>
                            {{ $purchaseOrder->vendor->tax_number }}
                        </div>
                    @endif


                    @if($purchaseOrder->vendor?->strn)
                        <div>
                            <strong>STRN</strong> {{ $purchaseOrder->vendor->strn }}
                        </div>
                    @endif


                </td>

            </tr>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- DELIVER / INVOICE --}}
    {{-- ========================================================= --}}

    <div class="section">

        <div class="section-title">
            Delivery & Billing Information
        </div>
        <table class="two-column">
            <tr>
                <td>
                    <div class="block-title">Deliver To</div>

                    <div class="strong">
                        {{ $company?->name ?? config('app.name') }}
                    </div>

                   {{ $company?->addresses?->firstWhere('type', 'shipping')?->address_line1 ?? '-' }}
                   {{ $company?->addresses?->firstWhere('type', 'shipping')?->city ?? '-' }},
                   {{ $company?->addresses?->firstWhere('type', 'shipping')?->country ?? '-' }}
                    <div>
                        @if($company?->addresses?->firstWhere('type', 'shipping')?->contact_name)
                        {{  $company?->addresses?->firstWhere('type', 'shipping')?->contact_name ?? '-' }}  |
                        @endif

                       {{ $company?->addresses?->firstWhere('type', 'shipping')?->contact_phone ?? '-' }}

                    </div>
                    <div>
                       {{ $company->email ??  '-' }}
                    </div>

                </td>
                <td>
                    <div class="block-title">Invoice To</div>
                    <div class="strong">{{ $company?->name ?? config('app.name') }}</div>
                    {{ $company?->addresses?->first()?->address_line1 ?? '-' }}

                    @if($company?->strn)
                        <br>
                        <strong>NTN & STRN:</strong>
                        {{ $company->strn }}
                    @endif



                </td>

            </tr>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- TERMS --}}
    {{-- ========================================================= --}}

    <div class="section">

        <div class="section-title">
            Terms of Delivery
        </div>

        <table class="terms-table">

            <tr>
                <td class="terms-label">Delivery Terms</td>

                <td>
                    {{ $purchaseOrder->delivery_terms ?? 'Immediate' }}
                </td>

                <td class="terms-label">
                    Payment Terms
                </td>

                <td>
                    {{ $purchaseOrder->paymentTerm->name ?? '-' }}
                </td>
            </tr>

            @if($purchaseOrder->expected_date)
                <tr>

                    <td class="terms-label">
                        Expected Delivery Date
                    </td>

                    <td colspan="3">
                        {{ \Carbon\Carbon::parse(
                            $purchaseOrder->expected_date
                        )->format('d/m/Y') }}
                    </td>

                </tr>
            @endif

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- STANDARD INSTRUCTIONS --}}
    {{-- ========================================================= --}}

    <div class="instructions">

        <strong>Supplier Instructions:</strong>

        <br>

        Please quote the Purchase Order number on the
        Delivery Challan, Invoice and all related correspondence.
        Please supply in accordance with the Order given below.

        <br>

        Please dispatch the Delivery Challan, product details,
        batch information, quantities and applicable supporting
        documents with the delivery.
        <br>
        The product must have a remaining shelf life of more than one year at the time of delivery.

    </div>


    {{-- ========================================================= --}}
    {{-- ITEMS --}}
    {{-- ========================================================= --}}

    <table class="items-table">

        <thead>

        <tr>

            <th style="width:5%;">
                #
            </th>

            <th style="width:35%; text-align:left;">
                Item / Description
            </th>

            <th style="width:7%;">
                Unit
            </th>

            <th style="width:10%;">
                Quantity
            </th>

            <th style="width:12%;">
                Unit Price
            </th>
             <th style="width:10%;">
                GST
            </th>
             <th style="width:10%;">
                WHT
            </th>

            <th style="width:22%;">
                Total Amount
            </th>

        </tr>

        </thead>


        <tbody>

        @forelse($purchaseOrder->lines as $line)

            @php
                $qty = (float) ($line->quantity ?? 0);
                $rate = (float) ($line->unit_price ?? 0);
                $tax = (float) ($line->tax_rate ?? 0);
                $wht = (float) ($line->wht_tax_rate ?? 0);

                $lineTotal =
                    $line->line_total
                    ?? ($qty * $rate);
            @endphp

            <tr>

                <td class="text-center">
                    {{ $loop->iteration }}
                </td>

                <td>

                    <div class="item-name">
                        {{ $line->item?->name
                            ?? $line->description
                            ?? 'N/A' }} > <span class="d-block small text-muted">{{ $line->brand?->name ?? "—" }}</span>
                            > <span class="d-block small text-muted">{{ $line->origin?->name ?? "—" }}</span>
                    </div>


                    @if(
                        $line->description &&
                        $line->description !== $line->item?->name
                    )

                        <div class="item-description">
                            {{ $line->description }}
                        </div>

                    @endif


                    @if($line->batch_number)

                        <div class="item-description">
                            Batch:
                            {{ $line->batch_number }}
                        </div>

                    @endif


                    @if($line->expiry_date)

                        <div class="item-description">
                            Expiry:
                            {{ \Carbon\Carbon::parse(
                                $line->expiry_date
                            )->format('d/m/Y') }}
                        </div>

                    @endif

                </td>


                <td class="text-center">

                    {{ $line->item?->unit?->name
                        ?? $line->unit
                        ?? '-' }}

                </td>


                <td class="text-right">
                    {{ number_format($qty, 2) }}
                </td>


                <td class="text-right">

                    {{ number_format($rate,2) }}

                </td>

                 <td class="text-center">

                    {{ number_format($tax,2) ?? '' }} %

                </td>

                 <td class="text-center">

                    {{ number_format($wht,2) ?? '' }} %

                </td>


                <td class="text-right">

                    {{ number_format(
                        (float) $lineTotal,
                        2
                    ) }}

                </td>

            </tr>

        @empty

            <tr>

                <td colspan="8"
                    class="text-center">

                    No purchase order items found.

                </td>

            </tr>

        @endforelse

        </tbody>

        @php
            $totalQuantity = $purchaseOrder->lines->sum(
                fn ($line) => (float) ($line->quantity ?? 0)
            );
        @endphp

        @if($purchaseOrder->lines->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">
                        <strong>Total Quantity</strong>
                    </td>

                    <td class="text-right">
                        <strong>{{ number_format($totalQuantity, 2) }}</strong>
                    </td>

                    <td colspan="4">KG</td>
                </tr>
            </tfoot>
        @endif

    </table>


    {{-- ========================================================= --}}
    {{-- TOTAL CALCULATION --}}
    {{-- ========================================================= --}}

    @php

        $subtotal = $purchaseOrder->subtotal
            ?? $purchaseOrder->lines->sum(fn ($line) => ((float) $line->quantity) * ((float) $line->unit_price)
            );

        $discount =
            (float) ($purchaseOrder->discount_amount ?? 0);

        $tax =
            (float) ($purchaseOrder->tax_total ?? 0);

        $freight =
            (float) ($purchaseOrder->freight_amount ?? 0);

        $grandTotal =
            $purchaseOrder->total
            ?? ($subtotal - $discount + $tax + $freight);

    @endphp


    <div class="summary-wrapper">

        <table class="summary-table">

            <tr>
                <td class="summary-label">Subtotal</td>
                <td class="text-right">{{ number_format($subtotal, 2) }}</td>
            </tr>

            @if($discount > 0)

                <tr>
                    <td class="summary-label">Discount</td>
                    <td class="text-right">- {{ number_format($discount, 2) }}</td>
                </tr>

            @endif


            @if($tax > 0)
                <tr>
                    <td class="summary-label">GST</td>
                    <td class="text-right">{{ number_format($tax, 2) }}</td>
                </tr>
            @endif


            @if($freight > 0)

                <tr>
                    <td class="summary-label">
                        Freight
                    </td>

                    <td class="text-right">
                        {{ number_format($freight, 2) }}
                    </td>
                </tr>

            @endif


            <tr class="grand-total">

                <td>
                    Grand Total
                </td>

                <td class="text-right">

                    {{ $purchaseOrder->currency_code }}

                    {{ number_format($grandTotal,2) }}

                </td>

            </tr>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- AMOUNT IN WORDS --}}
    {{-- ========================================================= --}}

    @php
    $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
    @endphp
    @if($grandTotal)

    <div class="amount-words" style="display:flex; align-items:center; gap:8px;">

    <div class="amount-words-label" style="margin:0;">
        Amount in Words:
    </div>

    <div class="amount-words-value" style="margin:0;">
        {{ ucfirst($formatter->format((float) $grandTotal)) }} rupees only
    </div>

</div>

    @endif


    {{-- ========================================================= --}}
    {{-- NOTES --}}
    {{-- ========================================================= --}}

    {{-- @if($purchaseOrder->notes)

        <div class="notes">

            <strong>Notes / Terms & Condations</strong>

            <br>

            {!! nl2br(e($purchaseOrder->notes)) !!}

        </div>

    @endif --}}


    {{-- ========================================================= --}}
    {{-- APPROVALS --}}
    {{-- ========================================================= --}}

    <table class="signatures">

        <tr>

            <td>

                <div class="signature-line">
                    Prepared By
                </div>

            </td>


            <td>

                <div class="signature-line">
                    Procurement Manager
                </div>

            </td>


            <td>

                <div class="signature-line">
                    Finance Manager
                </div>

            </td>

        </tr>

    </table>

<div class="footer">
        This Purchase Order is system generated and subject to
        the company's approved procurement terms and conditions.
    </div>


</div>

</body>
</html>
