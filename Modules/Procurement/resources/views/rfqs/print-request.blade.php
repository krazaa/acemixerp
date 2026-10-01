<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request for Quotation {{ $rfq->number }}</title>
    <style>
        body { color: #222; font: 14px/1.5 Arial, sans-serif; margin: 24px auto; max-width: 1000px; padding: 0 20px; }
        h1 { font-size: 24px; margin: 20px 0 8px; text-align: center;}
        h2 { font-size: 17px; margin: 24px 0 8px; }
        .organization { font-size: 20px; font-weight: bold; }
        .print-header { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 12px; break-inside: avoid; }
        .organization-logo { display: block; width: 280px; max-width: 50%; height: 82px; object-fit: contain; object-position: left center; }
        .actions { text-align: right; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed;  border: 1px solid #000;}
        th, td { border: 1px solid #aaa; padding: 8px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #f3f4f6; }
        .text { white-space: pre-wrap; overflow-wrap: anywhere; }
        .number { text-align: right; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        .issuedby {
                text-align: right;
                min-width: 0;
                overflow-wrap: anywhere;
        }
       .signature {
            margin-top: 40px;
            text-align: right;
        }

        @page { size: A4; margin: 0; }
        @media print {
            html { margin: 0; padding: 0; }
            body { margin: 0; padding: 15mm; max-width: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Print</button></div>
    <header class="print-header">
    <img class="organization-logo"
         src="{{ $organization?->logo_path ? route('organization.logo') : asset('assets/media/logos/acemix-logo.png') }}"
         alt="{{ $organization?->name ?? 'ACEMIX' }} logo" width="180" height="82">
    <div class="issuedby">
        <b>Created By:</b> {{ $rfq->creator?->name ?? ''}}
        </div>
    </header>



    {{-- @if($organization?->phone)<div>{{ $organization->phone }}</div>@endif --}}
    <h1>REQUEST FOR QUOTATION</h1>
    <table>
        <tr><th>RFQ number</th><td>{{ $rfq->number }}</td><th>Currency</th><td>{{ $rfq->currency_code }}</td></tr>
        <tr><th>Issue date</th><td>{{ ($rfq->issued_at ?? $rfq->issue_date)->format('d M Y') }}</td><th>Response deadline</th><td>{{ $rfq->due_date->format('d M Y') }}</td></tr>
    </table>
    @if($vendor)
        <h2>To Vendor</h2>
        <div><strong>{{ $vendor->name }}</strong></div>
        @if($vendor->legal_name)<div>{{ $vendor->legal_name }}</div>@endif
        @if($vendor->contact_person)<div>Attention: {{ $vendor->contact_person }}</div>@endif
        @if($vendor->email)<div>Email: {{ $vendor->email }}</div>@endif
        @if($vendor->phone)<div>Phone: {{ $vendor->phone }}</div>@endif
        @if($vendor->billingAddress())<div class="text">{{ $vendor->billingAddress()->oneLine() }}</div>@endif

    @endif

    <h2>Requested Items</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 3%">#</th>
                <th style="width: 83%">Item / Specification</th>
                <th style="width: 8%" class="number">Quantity</th>
                <th style="width: 7%">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rfq->lines as $line)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $line->item?->name }}</strong>
                        <div class="text">{{ $line->specification }}</div>

                        @if(filled($line->brand?->name))
                        <div class="text">
                            {{ $line->brand?->name ?? '-' }}
                            | @if(filled($line->origin?->name))
                                {{ $line->origin?->name ?? '-' }}
                            @endif
                        </div>
                        @endif
                    </td>

                    <td class="number text-end">{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ','), '0'), '.') }}</td>
                    <td>{{ $line->unit?->code }}</td>

                </tr>
            @endforeach
        </tbody>
    </table>
    @if($rfq->terms)
        <h2>Terms and Conditions</h2>
        <div class="text">{{ $rfq->terms }}</div>
    @endif

    <div class="signature">
     Authorized signature / stamp: __________________________________
    </div>

</body>
</html>
