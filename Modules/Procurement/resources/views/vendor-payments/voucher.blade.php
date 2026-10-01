<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <title>Payment Voucher {{ $payment->number }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1f2937;
            background: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .voucher {
            width: 100%;
        }

        /* =========================================================
           PRINT BUTTON
        ========================================================= */

        .no-print {
            text-align: right;
            margin-bottom: 18px;
        }

        .print-button {
            border: 1px solid #9ca3af;
            background: #ffffff;
            color: #1f2937;
            padding: 7px 14px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }

        .print-button:hover {
            background: #f3f4f6;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .voucher-header {
            display: table;
            width: 100%;
            table-layout: fixed;
            padding-bottom: 18px;
            border-bottom: 2px solid #1f4e79;
            margin-bottom: 25px;
        }

        .header-company,
        .header-title,
        .header-meta {
            display: table-cell;
            vertical-align: middle;
        }

        .header-company {
            width: 32%;
            text-align: left;
        }

        .header-title {
            width: 36%;
            text-align: center;
        }

        .header-meta {
            width: 32%;
            text-align: right;
        }

        .company-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .company-logo {
            display: block;
            max-width: 190px;
            max-height: 64px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .company-name {
            margin-top: 4px;
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .voucher-title {
            margin: 0;
            color: #17365d;
            font-size: 25px;
            line-height: 1.15;
            font-weight: 700;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .voucher-subtitle {
            margin-top: 5px;
            color: #6b7280;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .voucher-number {
            color: #17365d;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .voucher-meta {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.7;
        }

        .status {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 20px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
        }

        /* =========================================================
           SECTION
        ========================================================= */

        .section {
            margin-top: 22px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 9px;
        }

        .section-bar {
            width: 4px;
            height: 20px;
            background: #047857;
            border-radius: 2px;
        }

        .section-title {
            margin: 0;
            color: #17365d;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .section-line {
            flex: 1;
            height: 1px;
            background: #dbe3ea;
        }

        /* =========================================================
           GENERAL TABLE
        ========================================================= */

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .details-table {
            border: 1px solid #d5dde5;
            border-radius: 6px;
            overflow: hidden;
        }

        .details-table th,
        .details-table td {
            padding: 10px 12px;
            border-right: 1px solid #d5dde5;
            border-bottom: 1px solid #d5dde5;
            vertical-align: middle;
        }

        .details-table tr:last-child th,
        .details-table tr:last-child td {
            border-bottom: 0;
        }

        .details-table th:last-child,
        .details-table td:last-child {
            border-right: 0;
        }

        .details-table th {
            width: 18%;
            background: #f5f7fa;
            color: #26384a;
            font-weight: 700;
            text-align: left;
        }

        .details-table td {
            background: #ffffff;
            color: #374151;
        }

        /* =========================================================
           ALLOCATION TABLE
        ========================================================= */

        .allocation-table {
            border: 1px solid #d5dde5;
            border-radius: 6px;
            overflow: hidden;
        }

        .allocation-table th,
        .allocation-table td {
            padding: 10px 12px;
            border-right: 1px solid #d5dde5;
            border-bottom: 1px solid #d5dde5;
        }

        .allocation-table th:last-child,
        .allocation-table td:last-child {
            border-right: 0;
        }

        .allocation-table thead th {
            background: #17365d;
            color: #ffffff;
            border-color: #17365d;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .allocation-table tbody td {
            background: #ffffff;
        }

        .allocation-table tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .allocation-table tbody tr:last-child td {
            border-bottom: 1px solid #d5dde5;
        }

        .allocation-table tfoot th {
            background: #f0f4f8;
            color: #17365d;
            border-bottom: 0;
            font-size: 13px;
        }

        .allocation-table tfoot th:last-child {
            font-size: 15px;
        }

        .amount {
            text-align: right !important;
            white-space: nowrap;
        }

        .empty-row {
            text-align: center;
            color: #9ca3af;
            padding: 16px !important;
        }

        /* =========================================================
           TOTAL SUMMARY
        ========================================================= */

        .total-summary {
            margin-top: 12px;
            display: table;
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }

        .total-label,
        .total-value {
            display: table-cell;
            padding: 12px 14px;
        }

        .total-label {
            width: 70%;
            text-align: right;
            background: #f8fafc;
            color: #17365d;
            font-weight: 700;
        }

        .total-value {
            width: 30%;
            text-align: right;
            background: #17365d;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            white-space: nowrap;
        }

        /* =========================================================
           SIGNATURES
        ========================================================= */

        .signatures {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-top: 72px;
        }

        .signature-cell {
            display: table-cell;
            width: 33.333%;
            padding: 0 18px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #6b7280;
            margin-bottom: 7px;
        }

        .signature-title {
            color: #374151;
            font-size: 12px;
            font-weight: 700;
        }

        .signature-space {
            height: 35px;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .voucher-footer {
            margin-top: 45px;
            padding-top: 9px;
            border-top: 1px solid #dbe3ea;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }

        /* =========================================================
           PRINT
        ========================================================= */

        @media print {

            body {
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .voucher {
                width: 100%;
            }

            .voucher-header {
                margin-bottom: 22px;
            }

            .section {
                break-inside: avoid;
            }

            .allocation-table {
                break-inside: auto;
            }

            .allocation-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .signatures {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        /* =========================================================
           MOBILE
        ========================================================= */

        @media screen and (max-width: 768px) {

            body {
                padding: 15px;
            }

            .voucher-header {
                display: block;
            }

            .header-company,
            .header-title,
            .header-meta {
                display: block;
                width: 100%;
                text-align: center;
                margin-bottom: 15px;
            }

            .header-meta {
                text-align: center;
            }

            .company-brand {
                justify-content: center;
            }

            .voucher-title {
                font-size: 21px;
            }

            .details-table,
            .allocation-table {
                font-size: 12px;
            }

            .details-table th,
            .details-table td,
            .allocation-table th,
            .allocation-table td {
                padding: 8px;
            }

            .signatures {
                display: block;
                margin-top: 50px;
            }

            .signature-cell {
                display: block;
                width: 100%;
                margin-bottom: 40px;
            }
        }
    </style>
</head>

<body>

<div class="voucher">

    {{-- =========================================================
         PRINT BUTTON
    ========================================================= --}}
    <div class="no-print">
        <button
            type="button"
            class="print-button"
            onclick="window.print()">
            Print
        </button>
    </div>


    {{-- =========================================================
         PROFESSIONAL HEADER
         Company | Title | Voucher Information
    ========================================================= --}}
    <div class="voucher-header">

        {{-- COMPANY --}}
        <div class="header-company">

            <div class="company-brand">

                @if($organization->logo_path)

                    <img
                        src="{{ route('organization.logo') }}"
                        alt="{{ $organization->name }} logo"
                        class="company-logo">

                @else

                    <img
                        src="{{ asset('assets/img/brand/acemix_logo.png') }}"
                        alt="{{ config('app.name') }} logo"
                        class="company-logo">

                @endif

            </div>


        </div>


        {{-- PAYMENT VOUCHER TITLE --}}
        <div class="header-title">

            <h1 class="voucher-title">
                PAYMENT VOUCHER
            </h1>

        </div>


        {{-- VOUCHER META --}}
        <div class="header-meta">

            <div class="voucher-number">
                {{ $payment->number }}
            </div>

            <div class="voucher-meta">

                <span class="status">
                    {{ $payment->status->label() }}
                </span>

                <br>

                {{ $payment->payment_date?->format('d M Y') }}

                <br>

                Prepared by:
                <strong>
                    {{ $payment->creator->name }}
                </strong>

            </div>

        </div>

    </div>


    {{-- =========================================================
         PAYMENT DETAILS
    ========================================================= --}}
    <div class="section">

        <div class="section-header">

            <div class="section-bar"></div>

            <h2 class="section-title">
                Payment Details
            </h2>

            <div class="section-line"></div>

        </div>


        <table class="details-table">

            <tbody>

                <tr>

                    <th>
                        Vendor
                    </th>

                    <td>
                        {{ $payment->vendor?->name ?? '—' }}
                    </td>

                    <th>
                        Payment Method
                    </th>

                    <td>
                        {{ $payment->payment_method->label() }}
                    </td>

                </tr>


                <tr>

                    <th>
                        Bank / Cash
                    </th>

                    <td>
                        {{ $payment->bankAccount?->name ?? 'Cash on Hand' }}
                    </td>

                    <th>
                        Reference
                    </th>

                    <td>
                        {{ $payment->reference ?: '—' }}
                    </td>

                </tr>


                <tr>

                    <th>
                        GL Journal
                    </th>

                    <td colspan="3">
                        {{ $payment->journalEntry?->number ?? '—' }}
                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    {{-- =========================================================
         INVOICE ALLOCATION
    ========================================================= --}}
    <div class="section">

        <div class="section-header">

            <div class="section-bar"></div>

            <h2 class="section-title">
                Invoice Allocation
            </h2>

            <div class="section-line"></div>

        </div>


        <table class="allocation-table">

            <thead>

                <tr>

                    <th>
                        Invoice
                    </th>

                    <th>
                        Vendor Invoice #
                    </th>

                    <th>
                        Date
                    </th>

                    <th class="amount">
                        Allocated
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($payment->allocations as $allocation)

                    @php
                        $invoice = $allocation->allocatable;
                    @endphp

                    <tr>

                        <td>
                            {{ $invoice?->number ?? '—' }}
                        </td>

                        <td>
                            {{ $invoice?->vendor_invoice_number ?? '—' }}
                        </td>

                        <td>
                            {{ $invoice?->invoice_date?->format('d M Y') ?? '—' }}
                        </td>

                        <td class="amount">
                            {{ number_format((float) $allocation->amount, 2) }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="4"
                            class="empty-row">
                            No invoice allocation.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>


        {{-- =====================================================
             TOTAL
        ===================================================== --}}
        <div class="total-summary">

            <div class="total-label">
                TOTAL PAID
            </div>

            <div class="total-value">

                {{ $payment->currency_code }}

                {{ number_format((float) $payment->amount, 2) }}

            </div>

        </div>

    </div>


    {{-- =========================================================
         SIGNATURES
    ========================================================= --}}
    <div class="signatures">

        <div class="signature-cell">

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            <div class="signature-title">
                Prepared By
            </div>

        </div>


        <div class="signature-cell">

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            <div class="signature-title">
                Approved By
            </div>

        </div>


        <div class="signature-cell">

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            <div class="signature-title">
                Received By
            </div>

        </div>

    </div>


    {{-- =========================================================
         FOOTER
    ========================================================= --}}
    <div class="voucher-footer">

        This payment voucher is system generated and does not require a signature unless required by company policy.

    </div>

</div>

</body>
</html>
