<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Goods Received Note - {{ $goodsReceipt->number }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #1f2937;
            background: #ffffff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 10mm 12mm;
        }

        /* ===========================
           HEADER
        =========================== */

        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #009846;
            padding-bottom: 12px;
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
            display: block;
            max-height: 65px;
            max-width: 220px;
            width: auto;
            height: auto;
        }

        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .company-info {
            margin-top: 5px;
            font-size: 10px;
            color: #4b5563;
            line-height: 1.5;
        }

        .document-title {
            font-size: 24px;
            font-weight: 700;
            color: #009846;
            text-transform: uppercase;
        }

        .document-subtitle {
            margin-top: 3px;
            font-size: 10px;
            color: #6b7280;
        }

        /* ===========================
           META
        =========================== */

        .meta-table {
            width: 100%;
            margin-top: 0px;
            border-collapse: collapse;
        }

        .meta-table td {
            width: 25%;
            border: 1px solid #d1d5db;
            padding: 4px 9px;
            vertical-align: top;
        }

        .meta-label {
            display: block;
            font-size: 9px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .meta-value {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        /* ===========================
           SECTIONS
        =========================== */

        .section {
            margin-top: 7px;
        }

        .section-title {
            padding: 6px 9px;
            background: #374151;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .two-column {
            width: 100%;
            border-collapse: collapse;
        }

        .two-column td {
            width: 50%;
            vertical-align: top;
            padding: 9px 11px;
            border: 1px solid #d1d5db;
        }

        .block-title {
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #009846;
        }

        .strong {
            font-weight: 700;
            color: #111827;
        }

        /* ===========================
           DETAIL TABLE
        =========================== */

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table td {
            padding: 7px 9px;
            border: 1px solid #d1d5db;
        }

        .details-label {
            width: 20%;
            font-weight: 700;
            background: #f3f4f6;
        }

        /* ===========================
           ITEMS TABLE
        =========================== */

        .items-table {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table th {
            padding: 7px 5px;
            border: 1px solid #00863d;
            background: #009846;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .items-table td {
            padding: 7px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .item-name {
            font-weight: 700;
            color: #111827;
        }

        .item-note {
            margin-top: 2px;
            font-size: 9px;
            color: #6b7280;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* ===========================
           SUMMARY
        =========================== */

        .quantity-summary {
            width: 55%;
            margin-top: 12px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .quantity-summary td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
        }

        .summary-label {
            font-weight: 700;
            background: #f3f4f6;
        }

        .accepted-total {
            font-weight: 700;
        }

        .rejected-total {
            font-weight: 700;
        }

        /* ===========================
           NOTES
        =========================== */

        .notes {
            margin-top: 14px;
            padding: 9px 11px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .notes-title {
            margin-bottom: 4px;
            font-weight: 700;
            color: #111827;
        }

        /* ===========================
           STATUS
        =========================== */

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border: 1px solid #9ca3af;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* ===========================
           SIGNATURES
        =========================== */

        .signatures {
            width: 100%;
            margin-top: 55px;
            border-collapse: collapse;
        }

        .signatures td {
            width: 33.333%;
            padding: 0 15px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            padding-top: 5px;
            border-top: 1px solid #374151;
            font-weight: 700;
        }

        .signature-name {
            margin-top: 3px;
            font-size: 9px;
            color: #6b7280;
        }

        /* ===========================
           PRINT BUTTON
        =========================== */

        .print-actions {
            width: 210mm;
            margin: 12px auto;
            padding: 0 12mm;
            text-align: right;
        }

        .btn-print {
            border: 0;
            border-radius: 5px;
            padding: 9px 16px;
            background: #009846;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        @media print {
            html,
            body {
                width: 210mm;
                margin: 0 !important;
                padding: 0 !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-actions {
                display: none !important;
            }

            .page {
                margin: 0;
            }

            tr,
            td,
            th {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

<div class="print-actions">
    <button
        type="button"
        class="btn-print"
        onclick="window.print()"
    >
        Print Goods Received Note
    </button>
</div>


<div class="page">

    {{-- ===================================================== --}}
    {{-- HEADER --}}
    {{-- ===================================================== --}}

    <div class="header">

        <div class="header-left">

            @if($logoDataUri)
                <img
                    src="{{ $logoDataUri }}"
                    alt="{{ $company?->name ?? 'Company Logo' }}"
                    class="company-logo"
                >
            @else
                <div class="company-name">
                    {{ $company?->name ?? config('app.name') }}
                </div>
            @endif


        </div>


        <div class="header-right">

            <div class="document-title">
                Goods Received Note
            </div>
            <div class="document-subtitle">
            <strong>Received By:</strong> {{ $goodsReceipt->poster?->name ?? '-' }}
            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- DOCUMENT INFORMATION --}}
    {{-- ===================================================== --}}

    <table class="meta-table">

        <tr>

            <td>
                <span class="meta-label">
                    GRN Number
                </span>

                <span class="meta-value">
                    {{ $goodsReceipt->number }}
                </span>
            </td>


            <td>
                <span class="meta-label">
                    Received Date
                </span>

                <span class="meta-value">
                    {{ $goodsReceipt->received_date
                        ? \Carbon\Carbon::parse(
                            $goodsReceipt->received_date
                        )->format('d/m/Y')
                        : '-' }}
                </span>
            </td>


            <td>
                <span class="meta-label">
                    Purchase Order
                </span>

                <span class="meta-value">
                    {{ $goodsReceipt->purchaseOrder?->number ?? '-' }}
                </span>
            </td>


            <td>
                <span class="meta-label">
                    Status
                </span>

                <span class="meta-value">
                    {{ strtoupper($goodsReceipt->status->label()) }}
                </span>
            </td>

        </tr>

    </table>


    {{-- ===================================================== --}}
    {{-- SUPPLIER / WAREHOUSE --}}
    {{-- ===================================================== --}}

    <div class="section">

        <div class="section-title">
            Supplier & Receiving Information
        </div>
        <table class="two-column">
            <tr>
                <td>
                    <div class="block-title">Supplier</div>

                    <div class="strong">
                        {{ $goodsReceipt->vendor?->name ?? '-' }}
                    </div>
                    @php
                        $vendorAddres = $goodsReceipt?->vendor?->addresses?->firstWhere('type', 'billing');
                    @endphp
                    @if($vendorAddres)
                        <div>
                            {{ $vendorAddres->address_line1 }}  {{ $vendorAddres->city }}
                        </div>
                    @endif

                    @if($goodsReceipt->vendor?->phone)
                        <div>
                            <strong>Phone:</strong>
                            {{ $goodsReceipt->vendor->phone }}
                        </div>
                    @endif

                    @if($goodsReceipt->vendor?->email)
                        <div>
                            <strong>Email:</strong>
                            {{ $goodsReceipt->vendor->email }}
                        </div>
                    @endif

                </td>


                <td>

                    <div class="block-title">
                        Receiving Warehouse
                    </div>

                    <div class="strong">
                        {{ $goodsReceipt->warehouse?->name ?? '-' }}
                    </div>

                    {{-- @if($goodsReceipt->warehouse?->code)
                        <div>
                            <strong>Code:</strong>
                            {{ $goodsReceipt->warehouse->code }}
                        </div>
                    @endif --}}
                        @php
                            $warehouseAddress = $goodsReceipt->warehouse?->addresses?->firstWhere('type', 'shipping');
                        @endphp

                    @if($warehouseAddress)
                        <div>
                            {{ $warehouseAddress->address_line1 }}
                        </div>
                    @endif

                </td>

            </tr>

        </table>

    </div>


    {{-- ===================================================== --}}
    {{-- DELIVERY DETAILS --}}
    {{-- ===================================================== --}}

    <div class="section">

        <div class="section-title">
            Delivery Information
        </div>

        <table class="details-table">
            <tr>
                {{-- <td class="details-label">
                    Supplier Delivery Note
                </td>

                <td>
                    {{ $goodsReceipt->supplier_delivery_note ?: '-' }}
                </td> --}}


                <td class="details-label">
                    Driver Details
                </td>

                <td colspan="3">
                    {{ $goodsReceipt->carrier ?: '-' }}
                </td>

            </tr>


            <tr>

                <td class="details-label">
                    Purchase Order
                </td>

                <td>
                    {{ $goodsReceipt->purchaseOrder?->number ?? '-' }}
                </td>


                <td class="details-label">
                    Receipt Status
                </td>

                <td>
                    <span class="status-badge">
                        {{ strtoupper($goodsReceipt->status->label()) }}
                    </span>
                </td>

            </tr>

        </table>

    </div>


    {{-- ===================================================== --}}
    {{-- RECEIVED ITEMS --}}
    {{-- ===================================================== --}}

    <table class="items-table">

        <thead>

        <tr>

            <th style="width:4%;">
                #
            </th>

            <th style="width:26%; text-align:left;">
                Item / Description
            </th>

            <th style="width:9%;">
                Unit
            </th>

            <th style="width:13%;">
                Received
            </th>

            <th style="width:13%;">
                Accepted
            </th>

            <th style="width:13%;">
                Rejected
            </th>

            <th style="width:22%; text-align:left;">
                Rejection Reason / Remarks
            </th>

        </tr>

        </thead>


        <tbody>

        @forelse($goodsReceipt->lines as $line)

            <tr>

                <td class="text-center">
                    {{ $loop->iteration }}
                </td>


                <td>

                    <div class="item-name">
                        {{ $line->item?->name ?? 'N/A' }} <span class="d-block small text-muted">Brand: {{ $line->brand?->name ?? "—" }}</span>
                    </div>

                    @if($line?->batch_number)
                        <div class="item-note">
                            Batch No:
                            {{ $line->batch_number}}
                            <br>
                            Expiry: {{ $line->expiry_date}}
                        </div>
                    @endif

                    @if($line->notes)
                        <div class="item-note">
                            {{ $line->notes }}
                        </div>
                    @endif

                </td>


                <td class="text-center">

                    {{ $line->item?->unit?->name
                        ?? $line->item?->unit_name
                        ?? '-' }}

                </td>


                <td class="text-right">

                    {{ number_format(
                        (float) $line->received_quantity,
                        4
                    ) }}

                </td>


                <td class="text-right">

                    {{ number_format(
                        (float) $line->accepted_quantity,
                        4
                    ) }}

                </td>


                <td class="text-right">

                    {{ number_format(
                        (float) $line->rejected_quantity,
                        4
                    ) }}

                </td>


                <td>

                    {{ $line->rejection_reason ?: '-' }}

                </td>

            </tr>

        @empty

            <tr>

                <td
                    colspan="7"
                    class="text-center"
                >
                    No goods receipt items found.
                </td>

            </tr>

        @endforelse

        </tbody>

    </table>


    {{-- ===================================================== --}}
    {{-- QUANTITY TOTALS --}}
    {{-- ===================================================== --}}

    @php

        $receivedTotal = $goodsReceipt->lines->sum(
            fn ($line) =>
                (float) $line->received_quantity
        );

        $acceptedTotal = $goodsReceipt->lines->sum(
            fn ($line) =>
                (float) $line->accepted_quantity
        );

        $rejectedTotal = $goodsReceipt->lines->sum(
            fn ($line) =>
                (float) $line->rejected_quantity
        );

    @endphp


    <table class="quantity-summary">

        <tr>

            <td class="summary-label">
                Total Received
            </td>

            <td class="text-right">
                {{ number_format($receivedTotal, 4) }}
            </td>

        </tr>


        <tr>

            <td class="summary-label">
                Total Accepted
            </td>

            <td class="text-right accepted-total">
                {{ number_format($acceptedTotal, 4) }}
            </td>

        </tr>


        <tr>

            <td class="summary-label">
                Total Rejected
            </td>

            <td class="text-right rejected-total">
                {{ number_format($rejectedTotal, 4) }}
            </td>

        </tr>

    </table>


    {{-- ===================================================== --}}
    {{-- NOTES --}}
    {{-- ===================================================== --}}

    @if($goodsReceipt->notes)

        <div class="notes">

            <div class="notes-title">
                Supplier Delivery Note
             </div>

            {!! nl2br(e($goodsReceipt->supplier_delivery_note)) !!}

        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- POSTING INFORMATION --}}
    {{-- ===================================================== --}}

    @if($goodsReceipt->status->label() === 'Posted')

        <div class="section">

            <div class="section-title">
                Posting Information
            </div>

            <table class="details-table">

                <tr>

                    <td class="details-label">
                        Posted By
                    </td>

                    <td>
                        {{ $goodsReceipt->poster?->name ?? '-' }}
                    </td>


                    <td class="details-label">
                        Posted At
                    </td>

                    <td>

                        {{ $goodsReceipt->posted_at? \Carbon\Carbon::parse($goodsReceipt->posted_at)->format('d/m/Y h:i A')
                            : '-' }}

                    </td>

                </tr>

            </table>

        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- SIGNATURES --}}
    {{-- ===================================================== --}}

    <table class="signatures">

        <tr>

            <td>

                <div class="signature-line">
                    Received By
                </div>
            </td>


            <td>

                <div class="signature-line">
                    Checked / Inspected By
                </div>

            </td>


            <td>

                <div class="signature-line">
                    Store / Warehouse Manager
                </div>

            </td>

        </tr>

    </table>

</div>

</body>
</html>
