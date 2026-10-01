<x-default-layout>
@section('title', 'Bank Reconciliation')

@section('toolbar-button')
   <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="bank_account_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($accounts as $a)
                <option value="{{ $a->id }}" @selected(optional($account)->id === $a->id)>
                    {{ $a->code }} — {{ $a->name }}
                </option>
            @endforeach
        </select>
    </div>
</form>

@if($account)
    <div class="card border-0 shadow-sm">
        <div class="card-header">
            <h3 class="card-title">Start New Reconciliation</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('bank-reconciliation.store') }}" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="bank_account_id" value="{{ $account->id }}">
                <div class="col-md-3">
                    <label class="form-label small">Statement Date</label>
                    <input name="statement_date" type="date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Opening Balance</label>
                    <input name="opening_balance" type="number" step="0.0001" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Closing Balance</label>
                    <input name="closing_balance" type="number" step="0.0001" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-sm w-100">Open Reconciliation</button>
                </div>
            </form>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <h2 class="h5">No active bank accounts</h2>
            <p class="text-muted mb-3">Create and activate a bank account before starting a reconciliation.</p>
            @can('create', \App\Models\BankAccount::class)
                <a href="{{ route('bank-accounts.create') }}" class="btn btn-primary">Create Bank Account</a>
            @endcan
        </div>
    </div>
@endif
</x-default-layout>
