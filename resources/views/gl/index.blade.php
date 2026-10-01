<x-default-layout>
@section('title', 'General Ledger')

@section('toolbar-button')
     @if($query->accountId)<a href="{{ route('gl.csv', request()->query()) }}" class="btn btn-sm btn-light-success">Export CSV</a><a href="{{ route('gl.print', request()->query()) }}" target="_blank" class="btn btn-sm btn-light-secondary">Print / PDF</a>@endif
        <a href="{{ route('gl.trial-balance') }}" class="btn btn-sm btn-light-secondary">Trial Balance</a>
        <a href="{{ route('gl.aging.receivables') }}" class="btn btn-sm btn-light-secondary">AR Aging</a>
        <a href="{{ route('gl.aging.payables') }}" class="btn btn-sm btn-light-secondary">AP Aging</a>

  <a href="{{ route('manufacturing.production-orders.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


<form method="GET" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small">Account</label>
                <select name="account_id" class="form-select form-select-sm">
                    <option value="">— Select account —</option>
                    @foreach($accounts as $a)
                        <option value="{{ $a->id }}" @selected((int) request('account_id') === $a->id)>
                            {{ $a->code }} — {{ $a->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small">From</label>
                <input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2"><label class="form-label small">To</label>
                <input name="to" type="date" value="{{ request('to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2"><label class="form-label small">Cost Center</label>
                <select name="cost_center_id" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach($costCenters as $c)
                        <option value="{{ $c->id }}" @selected((int) request('cost_center_id') === $c->id)>{{ $c->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small">Search</label>
                <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number, description…">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-sm btn-primary w-100">Run</button>
            </div>
        </div>
    </div>
</form>

@if($query->accountId && $lines->isNotEmpty())
    <div class="alert alert-info d-flex justify-content-between">
        <span>Closing balance as of period end</span>
        <strong>{{ number_format((float) $balance, 2) }}</strong>
    </div>
@endif

@if($query->accountId)
    <div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="fw-bold fs-6 text-gray-800">
                        <th>Date</th><th>Entry</th><th>Description</th><th>Memo</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Running</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $l)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($l->entry_date)->format('d/m/Y') }}</td>
                            <td><a href="{{ route('journals.show', $l->journal_entry_id) }}"><code>{{ $l->number }}</code></a></td>
                            <td>{{ $l->description }}</td>
                            <td class="text-muted small">{{ $l->line_memo }}</td>
                            <td class="text-end">{{ bccomp($l->debit, '0', 4) > 0 ? number_format((float) $l->debit, 2) : '' }}</td>
                            <td class="text-end">{{ bccomp($l->credit, '0', 4) > 0 ? number_format((float) $l->credit, 2) : '' }}</td>
                            <td class="text-end">{{ number_format((float) $l->running_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No postings in the selected range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
@else
    <div class="alert alert-secondary">Choose an account to view its ledger.</div>
@endif
</x-default-layout>
