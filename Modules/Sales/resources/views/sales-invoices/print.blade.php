<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            color: #20242a;
            margin: 32px;
            font-size: 12px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1f6feb;
            padding-bottom: 16px;
        }

        .company {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .company-logo {
            max-width: 220px;
            max-height: 80px;
            object-fit: contain;
            display: block;
        }

        .company-info {
            line-height: 1.5;
        }


        h2 {
            font-size: 16px;
            margin: 24px 0 8px;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .muted {
            color: #666;
        }

        .right {
            text-align: right;
        }

        .num {
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #d8dee4;
            padding: 8px;
            vertical-align: top;
        }

        th {
            background: #f3f5f7;
            text-align: left;
        }

        .invoice-info td {
            width: 50%;
        }

        .totals {
            margin-left: auto;
            width: 300px;
        }

        .totals td {
            border: 0;
            padding: 5px;
        }

        .total td {
            border-top: 2px solid #20242a;
            font-size: 15px;
            font-weight: bold;
        }

        .bank-details {
            margin-top: 25px;
            width: 100%;
        }

        .bank-details td {
            border: 1px solid #d8dee4;
        }

        .bank-title {
            font-weight: bold;
            font-size: 13px;
            background: #f3f5f7;
        }

        .footer {
            margin-top: 35px;
            border-top: 1px solid #d8dee4;
            padding-top: 12px;
            font-size: 10px;
            color: #666;
        }

        button {
            margin-bottom: 18px;
            padding: 8px 12px;
        }

        @media print {
            button {
                display: none;
            }

            body {
                margin: 16px;
            }
        }
    </style>
</head>

<body>

    {{-- Print Button --}}
    <button type="button" onclick="window.print()">
        Print / Save as PDF
    </button>


    {{-- ========================================================= --}}
    {{-- COMPANY HEADER --}}
    {{-- ========================================================= --}}

    <header>

        <div class="company">

            {{-- Company Logo --}}
            @if($company?->logo_path)
                <img src="{{ $logoBase64 }}" alt="{{ $company->name }}" class="company-logo">
            @endif

            <div class="company-info">

                @php
                    $officeAddress = $company?->addresses?->where('type', 'office')->first();
                @endphp

                @if(!empty($officeAddress))
                    <div>
                        {{ $officeAddress->address_line1}}
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

                {{-- NTN --}}
                @if(!empty($company?->ntn))
                    <div>
                        <strong>NTN:</strong>
                        {{ $company->ntn }}
                    </div>
                @endif

                {{-- SRTN --}}
                @if(!empty($company?->strn))
                    <div>
                        <strong>SRTN:</strong>
                        {{ $company->strn }}
                    </div>
                @endif

            </div>

        </div>


        {{-- Invoice Information --}}
        <div class="invoice-title">

            <h1>SALES TAX INVOICE</h1>

            <strong>
                {{ $invoice->number }}
            </strong>

            <br>

            <span class="muted">
                {{ $invoice->invoice_date?->format('d M Y') }}
            </span>
             <br>
            <strong>
                {{ $invoice->salesorder->number }}
                <br>
                 <strong>Salese Representative : </strong> {{ $invoice->salesorder->salesperson->first_name ?? '' }} {{ $invoice->salesorder->salesperson->last_name ?? '' }}
            </strong>

            <br>

            <span class="muted">
                {{ $invoice->invoice_date?->format('d M Y') }}
            </span>

        </div>

    </header>


    {{-- ========================================================= --}}
    {{-- CUSTOMER + INVOICE DETAILS --}}
    {{-- ========================================================= --}}

    <table class="invoice-info">

        <tr>

            {{-- Bill To --}}
            <td>

                <strong>Bill To</strong>

                <br>

                {{ $invoice->customer?->name }}

                @if(!empty($invoice->customer?->email))
                    <br>
                    {{ $invoice->customer->email }}
                @endif

                @if(!empty($invoice->customer?->address))
                    <br>
                    {{ $invoice->customer->address }}
                @endif

                @if(!empty($invoice->customer?->ntn))
                    <br>
                    <strong>NTN:</strong>
                    {{ $invoice->customer->ntn }}
                @endif

                @if(!empty($invoice->customer?->strn))
                    <br>
                    <strong>SRTN:</strong>
                    {{ $invoice->customer->strn }}
                @endif

            </td>


            {{-- Invoice Details --}}
            <td>

                <strong>Invoice Details</strong>

                <br>

                <strong>Invoice Date:</strong>
                {{ $invoice->invoice_date?->format('d M Y') }}

                <br>

                <strong>Due Date:</strong>
                {{ $invoice->due_date?->format('d M Y') }}

                <br>

                <strong>Currency:</strong>
                {{ $invoice->currency_code }}

                <br>

                <strong>Status:</strong>
                {{ $invoice->status->label() }}

                {{-- Salesman --}}
                @if(!empty($invoice->salesman))
                    <br>

                    <strong>Salesman:</strong>
                    {{ $invoice->salesman->name }}
                @elseif(!empty($invoice->salesman_name))
                    <br>

                    <strong>Salesman:</strong>
                    {{ $invoice->salesman_name }}
                @endif

            </td>

        </tr>

    </table>


    {{-- ========================================================= --}}
    {{-- ITEMS --}}
    {{-- ========================================================= --}}

    <h2>Items</h2>

    <table>

        <thead>

            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Unit</th>
                <th class="num">Qty</th>
                <th class="num">Price</th>
                <th class="num">GST</th>
                <th class="num">Total</th>
            </tr>

        </thead>

        <tbody>

            @foreach($invoice->lines as $index => $line)

                <tr>

                    <td>
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $line->item?->code }}
                        —
                        {{ $line->item?->name }}
                    </td>

                    <td>
                        {{ $line->unit?->code }}
                    </td>

                    <td class="num">
                        {{ number_format((float) $line->quantity, 4) }}
                    </td>

                    <td class="num">
                        {{ number_format((float) $line->unit_price, 4) }}
                    </td>

                    <td class="num">
                        {{ number_format((float) $line->tax_rate, 2) }}%
                    </td>

                    <td class="num">
                        {{ number_format((float) $line->line_total, 4) }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>


    {{-- ========================================================= --}}
    {{-- TOTALS --}}
    {{-- ========================================================= --}}

    <table class="totals">

        <tr>

            <td>
                Subtotal
            </td>

            <td class="num">
                {{ number_format((float) $invoice->subtotal, 4) }}
            </td>

        </tr>

        <tr>

            <td>
                GST
            </td>

            <td class="num">
                {{ number_format((float) $invoice->tax_total, 4) }}
            </td>

        </tr>

        <tr class="total">

            <td>
                Total
            </td>

            <td class="num">
                {{ number_format((float) $invoice->total, 4) }}
            </td>

        </tr>

    </table>


    {{-- ========================================================= --}}
    {{-- COMPANY BANK DETAILS --}}
    {{-- ========================================================= --}}

    @if(!empty($company?->bankAccounts) && $company->bankAccounts->count())

        <table class="bank-details">

            <tr>
                <td colspan="4" class="bank-title">
                    Company Bank Details
                </td>
            </tr>

            @foreach($company->bankAccounts as $bank)

                <tr>

                    <td>
                        <strong>Bank:</strong><br>
                        {{ $bank->bank_name }}
                    </td>

                    <td>
                        <strong>Account Title:</strong><br>
                        {{ $bank->account_title }}
                    </td>

                    <td>
                        <strong>Account No:</strong><br>
                        {{ $bank->account_number }}
                    </td>

                    <td>
                        <strong>IBAN:</strong><br>
                        {{ $bank->iban }}
                    </td>

                </tr>

            @endforeach

        </table>

    @endif


    {{-- ========================================================= --}}
    {{-- FOOTER --}}
    {{-- ========================================================= --}}

    <div class="footer">

        <strong>Payment Instructions:</strong>

        Please make payment to the above-mentioned company bank account.

        <br><br>

        This is a computer-generated invoice and does not require a signature.

    </div>

</body>
</html>
