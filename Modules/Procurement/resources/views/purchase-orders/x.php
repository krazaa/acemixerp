<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Purchase Order {{ $purchaseOrder->po_number }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            font-size: 12px;
            background: #fff;
        }

        .page {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header-table,
        .info-table,
        .items-table,
        .totals-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-name {
            font-size: 22px;
            font-weight: 700;
        }

        .document-title {
            font-size: 26px;
            font-weight: 700;
            text-align: right;
            text-transform: uppercase;
        }

        .document-number {
            text-align: right;
            margin-top: 5px;
            font-size: 14px;
        }

        .section {
            margin-top: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            background: #eee;
            padding: 7px 10px;
            border: 1px solid #bbb;
        }

        .info-table td {
            padding: 5px 8px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 130px;
        }

        .items-table {
            margin-top: 20px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #999;
            padding: 7px;
        }

        .items-table th {
            background: #eee;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .totals-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .totals-table {
            width: 400px;
        }

        .totals-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }

        .grand-total {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #333 !important;
            border-bottom: 2px solid #333 !important;
        }

        .terms {
            margin-top: 25px;
            line-height: 1.6;
        }

        .signature-table {
            margin-top: 60px;
        }

        .signature-table td {
            width: 33.333%;
            text-align: center;
            padding: 0 25px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 6px;
            margin-top: 50px;
        }

        .print-actions {
            max-width: 1000px;
            margin: 0 auto 15px;
            text-align: right;
        }

        .print-btn {
            border: 0;
            background: #222;
            color: #fff;
            padding: 10px 18px;
            border-radius: 4px;
            cursor: pointer;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-actions {
                display: none;
            }

            @page {
                size: A4;
                margin: 12mm;
            }

            thead {
                display: table-header-group;
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
    <button type="button"
            class="print-btn"
            onclick="window.print()">
        Print Purchase Order
    </button>
</div>

<div class="page">

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td width="60%">

                {{-- Company Logo --}}
                @if(!empty($company?->logo))
                    <img
                        src="{{ asset('storage/' . $company->logo) }}"
                        alt="Logo"
                        style="max-height:75px;margin-bottom:10px;"
                    >
                @endif

                <div class="company-name">
                    {{ $company->name ?? config('app.name') }}
                </div>

                @if(!empty($company?->address))
                    <div>
                        {{ $company->address }}
                    </div>
                @endif

                @if(!empty($company?->phone))
                    <div>
                        Phone: {{ $company->phone }}
                    </div>
                @endif

                @if(!empty($company?->email))
                    <div>
                        Email: {{ $company->email }}
                    </div>
                @endif
            </td>

            <td width="40%">
                <div class="document-title">
                    Purchase Order
                </div>

                <div class="document-number">
                    <strong>PO #:</strong>
                    {{ $purchaseOrder->number }}
                </div>

                <div class="document-number">
                    <strong>Date:</strong>

                    {{ optional($purchaseOrder->order_date)->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>


    {{-- VENDOR / PO DETAILS --}}
    <div class="section">

        <table class="info-table">
            <tr>

                <td width="50%">
                    <div class="section-title">
                        Vendor
                    </div>

                    <table class="info-table">
                        <tr>
                            <td class="label">Name:</td>

                            <td>
                                {{ $purchaseOrder->vendor?->name ?? 'N/A' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Contact:</td>

                            <td>
                                {{ $purchaseOrder->vendor?->contact_person ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Phone:</td>

                            <td>
                                {{ $purchaseOrder->vendor?->phone ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Email:</td>

                            <td>
                                {{ $purchaseOrder->vendor?->email ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Address:</td>

                            <td>
                                {{ $purchaseOrder->vendor?->address ?? '-' }}
                            </td>
                        </tr>
                    </table>
                </td>


                <td width="50%">

                    <div class="section-title">
                        Purchase Order Information
                    </div>

                    <table class="info-table">

                        <tr>
                            <td class="label">
                                PO Number:
                            </td>

                            <td>
                                {{ $purchaseOrder->number }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                PO Date:
                            </td>

                            <td>
                                {{ optional($purchaseOrder->order_date)->format('d/m/Y') }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                PR Reference:
                            </td>

                            <td>
                                {{ $purchaseOrder->purchaseRequisition?->number ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                RFQ Reference:
                            </td>

                            <td>
                                {{ $purchaseOrder->rfq?->number ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                Delivery Date:
                            </td>

                            <td>
                                {{ optional($purchaseOrder->expected_delivery_date)->format('d/m/Y') ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                Status:
                            </td>

                            <td>
                                {{ ucfirst($purchaseOrder->status->value ?? $purchaseOrder->status ?? '-') }}
                            </td>
                        </tr>

                    </table>

                </td>
            </tr>
        </table>

          <table class="info-table">
            <tr>

                <td width="50%">
                    <div class="section-title">
                        Deliver To
                    </div>

                    <table class="info-table">
                        <tr>
                            <td>{{ $organization->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>{{ $organization->address_line1?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>{{ $organization->phone?? '-' }}</td>
                        </tr>

                        <tr>
                            <td>{{ $organization?->email ?? '-' }}</td>
                        </tr>


                    </table>
                </td>


                <td width="50%">

                    <div class="section-title">
                        Invoice to
                    </div>

                    <table class="info-table">

                        <tr>
                            <td class="label">
                                PO Number:
                            </td>

                            <td>
                                {{ $purchaseOrder->number }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                PO Date:
                            </td>

                            <td>
                                {{ optional($purchaseOrder->order_date)->format('d/m/Y') }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                PR Reference:
                            </td>

                            <td>
                                {{ $purchaseOrder->purchaseRequisition?->number ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                RFQ Reference:
                            </td>

                            <td>
                                {{ $purchaseOrder->rfq?->number ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                Delivery Date:
                            </td>

                            <td>
                                {{ optional($purchaseOrder->expected_delivery_date)->format('d/m/Y') ?? '-' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                Status:
                            </td>

                            <td>
                                {{ ucfirst($purchaseOrder->status->value ?? $purchaseOrder->status ?? '-') }}
                            </td>
                        </tr>

                    </table>

                </td>
            </tr>
        </table>
    </div>


    {{-- ITEMS --}}

    <table class="items-table">

        <thead>
        <tr>
            <th width="5%">#</th>
            <th width="31%">Item / Description</th>
            <th width="10%">Unit</th>
            <th width="10%">Qty</th>
            <th width="14%">Unit Price</th>
            <th width="10%">Tax</th>
            <th width="20%">Total</th>
        </tr>
        </thead>

        <tbody>

        @forelse($purchaseOrder->lines as $index => $line)

            @php

                $quantity = (float) $line->quantity;
                $unitPrice = (float) $line->unit_price;

                $subtotal = $quantity * $unitPrice;

                $discount = (float) ($line->discount_amount ?? 0);

                $tax = (float) ($line->tax_amount ?? 0);

                $lineTotal =
                    $line->total_amount
                    ?? (($subtotal - $discount) + $tax);

            @endphp

            <tr>

                <td class="text-center">
                    {{ $loop->iteration }}
                </td>

                <td>
                    <strong>
                        {{ $line->item?->name ?? $line->description ?? 'N/A' }}
                    </strong>

                    @if(!empty($line->description))
                        <br>
                        <small>
                            {{ $line->description }}
                        </small>
                    @endif
                </td>

                <td class="text-center">
                    {{ $line->item?->unit?->name ?? $line->unit ?? '-' }}
                </td>

                <td class="text-right">
                    {{ number_format($quantity, 2) }}
                </td>

                <td class="text-right">
                    {{ number_format($unitPrice, 2) }}
                </td>

                <td class="text-right">
                    {{ number_format($tax, 2) }}
                </td>

                <td class="text-right">
                    {{ number_format($lineTotal, 2) }}
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="7"
                    class="text-center">
                    No purchase order items found.
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>


    {{-- TOTALS --}}

    @php

        $subtotal = $purchaseOrder->subtotal
            ?? $purchaseOrder->lines->sum(function ($line) {

                return
                    ((float) $line->quantity)
                    *
                    ((float) $line->unit_price);

            });

        $discount =
            (float) ($purchaseOrder->discount_amount ?? 0);

        $tax =
            (float) ($purchaseOrder->tax_amount ?? 0);

        $shipping =
            (float) ($purchaseOrder->shipping_amount ?? 0);

        $grandTotal =
            $purchaseOrder->grand_total
            ?? (($subtotal - $discount) + $tax + $shipping);

    @endphp


    <div class="totals-wrapper">

        <table class="totals-table">

            <tr>
                <td>
                    Subtotal
                </td>

                <td class="text-right">
                    {{ number_format($subtotal, 2) }}
                </td>
            </tr>


            @if($discount > 0)

                <tr>
                    <td>
                        Discount
                    </td>

                    <td class="text-right">
                        - {{ number_format($discount, 2) }}
                    </td>
                </tr>

            @endif


            @if($tax > 0)

                <tr>
                    <td>
                        Tax
                    </td>

                    <td class="text-right">
                        {{ number_format($tax, 2) }}
                    </td>
                </tr>

            @endif


            @if($shipping > 0)

                <tr>
                    <td>
                        Shipping / Freight
                    </td>

                    <td class="text-right">
                        {{ number_format($shipping, 2) }}
                    </td>
                </tr>

            @endif


            <tr class="grand-total">

                <td>
                    Grand Total
                </td>

                <td class="text-right">

                    {{ $purchaseOrder->currency ?? 'PKR' }}

                    {{ number_format($grandTotal, 2) }}

                </td>

            </tr>

        </table>

    </div>


    {{-- DELIVERY --}}

    @if(
        !empty($purchaseOrder->delivery_address)
        || !empty($purchaseOrder->payment_terms)
        || !empty($purchaseOrder->delivery_terms)
    )

        <div class="section">

            <div class="section-title">
                Terms & Delivery
            </div>

            <table class="info-table">

                @if(!empty($purchaseOrder->delivery_address))
                    <tr>

                        <td class="label">
                            Delivery To:
                        </td>

                        <td>
                            {{ $purchaseOrder->delivery_address }}
                        </td>

                    </tr>
                @endif


                @if(!empty($purchaseOrder->payment_terms))
                    <tr>

                        <td class="label">
                            Payment Terms:
                        </td>

                        <td>
                            {{ $purchaseOrder->payment_terms }}
                        </td>

                    </tr>
                @endif


                @if(!empty($purchaseOrder->delivery_terms))
                    <tr>

                        <td class="label">
                            Delivery Terms:
                        </td>

                        <td>
                            {{ $purchaseOrder->delivery_terms }}
                        </td>

                    </tr>
                @endif

            </table>

        </div>

    @endif


    {{-- NOTES --}}

    @if(!empty($purchaseOrder->notes))

        <div class="terms">

            <strong>Notes / Instructions</strong>

            <div>
                {!! nl2br(e($purchaseOrder->notes)) !!}
            </div>

        </div>

    @endif


    {{-- SIGNATURES --}}

    <table class="signature-table">

        <tr>

            <td>
                <div class="signature-line">
                    Prepared By
                </div>

                @if($purchaseOrder->creator)
                    <small>
                        {{ $purchaseOrder->creator->name }}
                    </small>
                @endif
            </td>

            <td>
                <div class="signature-line">
                    Procurement Manager
                </div>
            </td>

            <td>
                <div class="signature-line">
                    Authorized By
                </div>

                @if($purchaseOrder->approver)
                    <small>
                        {{ $purchaseOrder->approver->name }}
                    </small>
                @endif
            </td>

        </tr>

    </table>

</div>
 <div class="footer">

        This Purchase Order is system generated and subject to
        the company's approved procurement terms and conditions.

        <br>

        Please reference
        <strong>{{ $purchaseOrder->po_number }}</strong>
        on all invoices, delivery documents and correspondence.

    </div>
</body>
</html>
