@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<h1 class="h3 mb-3">Reports</h1>
<div class="list-group">
    <a class="list-group-item list-group-item-action" href="{{ route('reports.balance-sheet') }}">
        <h2 class="h5 mb-1">Balance Sheet</h2>
        <p class="mb-0 text-muted">View assets, liabilities, equity, and unclosed earnings as of a selected date.</p>
    </a>
    <a class="list-group-item list-group-item-action" href="{{ route('reports.accounts-payable') }}">
        <h2 class="h5 mb-1">Accounts Payable Report</h2>
        <p class="mb-0 text-muted">View vendor debit, credit, and outstanding balances as of a selected date.</p>
    </a>
    <a class="list-group-item list-group-item-action" href="{{ route('reports.accounts-receivable') }}">
        <h2 class="h5 mb-1">Accounts Receivable Report</h2>
        <p class="mb-0 text-muted">View customer debit, credit, and outstanding balances as of a selected date.</p>
    </a>
    <a class="list-group-item list-group-item-action" href="{{ route('reports.trial-balance') }}">
        <h2 class="h5 mb-1">Trial Balance Report</h2>
        <p class="mb-0 text-muted">View account debit and credit balances as of a selected date.</p>
    </a>
    <a class="list-group-item list-group-item-action" href="{{ route('gl.index') }}">General Ledger</a>
</div>
@endsection
