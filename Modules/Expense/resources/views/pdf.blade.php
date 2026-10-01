<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $claim->number }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 20px; margin: 20px 0 8px; }
        h2 { font-size: 13px; margin-top: 22px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; table-layout: fixed; }
        th, td { border: 1px solid #bbb; padding: 7px; vertical-align: top; overflow-wrap: break-word; }
        th { background: #eee; text-align: left; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .right { text-align: right; }
        .logo { width: 190px; }
        .notes { white-space: pre-wrap; }
    </style>
</head>
<body>
    <img class="logo" src="{{ public_path('assets/media/logos/acemix-logo.png') }}" alt="ACEMIX">
    <h1>Expense Claim {{ $claim->number }}</h1>
    <p>{{ str($claim->status->value)->replace('_', ' ')->title()->replace('Ceo', 'CEO') }}</p>
    <table>
        <tr><th>Employee</th><td>{{ $claim->employee?->fullName() }}</td><th>Date</th><td>{{ $claim->expense_date->format('d M Y') }}</td></tr>
        <tr><th>Department</th><td>{{ $claim->department?->name }}</td><th>Created by</th><td>{{ $claim->creator?->name }}</td></tr>
    </table>
    <p class="notes">{{ $claim->description }}</p>
    <h2>Expense Entries</h2>
    <table>
        <thead><tr><th style="width: 15%">Date</th><th>Expense Account / Reference</th><th style="width: 16%">Amount</th><th style="width: 16%">Deduction</th><th style="width: 16%">Review</th></tr></thead>
        <tbody>
        @foreach($claim->lines as $line)
            <tr>
                <td>{{ $line->expense_date?->format('d M Y') }}</td>
                <td>{{ $line->expenseAccount?->code }} {{ $line->expenseAccount?->name }}<br>{{ $line->reference }}</td>
                <td class="right">{{ number_format((float) $line->amount, 2) }}</td>
                <td class="right">{{ number_format((float) $line->manager_deduction_amount, 2) }}<br>{{ $line->manager_deduction_reason }}</td>
                <td>{{ ucfirst($line->manager_decision ?? 'Pending') }}<br>{{ $line->manager_rejection_reason }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="right">Claimed: {{ $claim->currency_code }} {{ number_format((float) $claim->amount, 2) }}</p>
    <p class="right">{{ $claim->hasManagerApproval() ? 'Approved' : 'Pending Review' }}: {{ $claim->currency_code }} {{ number_format((float) $claim->approvedAmount(), 2) }}</p>
    <h2>Approval Details</h2>
    <p>Manager approval: {{ $claim->manager_approved_at?->format('d M Y H:i') ?? 'Pending' }}<br>
        CEO approval: {{ $claim->ceo_approved_at?->format('d M Y H:i') ?? 'Pending' }}<br>
        Reimbursed: {{ $claim->reimbursed_at?->format('d M Y H:i') ?? 'Pending' }}<br>
        Payment reference: {{ $claim->payment_reference ?? '-' }}</p>
    @if($claim->rejection_reason)<p>Rejection reason: {{ $claim->rejection_reason }}</p>@endif
    @if($evidenceHtml)
        <h2>Receipts / Evidence</h2>
        {!! $evidenceHtml !!}
    @endif
</body>
</html>
