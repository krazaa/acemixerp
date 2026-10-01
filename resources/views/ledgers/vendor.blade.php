<x-default-layout>
@section('title', "Vendor Ledger — {$vendor->name}")
@section('sub-title')
 <code>{{ $vendor->code }}</code>
 @if($vendor->tax_number)
                · Tax {{ $vendor->tax_number }}
            @endif
@endsection

 @section('toolbar-button')
  <a href="{{ route('vendors.statement', $vendor) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-text"></i> Statement
        </a>
        <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-outline-secondary btn-sm">
            Vendor Details
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection


<div class="alert alert-info d-flex justify-content-between">
    <span>Outstanding balance</span>
    <strong>{{ number_format((float) $balance, 4) }}</strong>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">From</label>
        <input name="from" type="date" value="{{ $from?->format('Y-m-d') }}" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">To</label>
        <input name="to" type="date" value="{{ $to?->format('Y-m-d') }}" class="form-control form-control-sm">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-sm btn-light-info w-100">Filter</button>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <a href="{{ route('vendors.ledger', $vendor) }}" class="btn btn-sm btn-light-secondary w-100">Reset</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Ledger Movements</h3>
        <div class="card-toolbar">
            <span class="text-muted small">{{ $lines->count() }} entr(y/ies)</span>
        </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Date</th>
                    <th>Entry</th>
                    <th>Description</th>
                    <th>Account</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $l)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($l->entry_date)->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('journals.show', $l->journal_entry_id) }}">
                                <code>{{ $l->number }}</code>
                            </a>
                        </td>
                        <td>
                            {{ $l->description }}
                            @if(! empty($l->line_memo))
                                <div class="text-muted small">{{ $l->line_memo }}</div>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $l->account_code }} — {{ $l->account_name }}
                        </td>
                        <td class="text-end">
                            {{ bccomp((string) $l->debit, '0', 4) > 0 ? number_format((float) $l->debit, 4) : '' }}
                        </td>
                        <td class="text-end">
                            {{ bccomp((string) $l->credit, '0', 4) > 0 ? number_format((float) $l->credit, 4) : '' }}
                        </td>
                        <td class="text-end fw-semibold">
                            {{ number_format((float) $l->running_balance, 4) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No ledger entries yet.
                            Post a supplier invoice or a vendor payment to populate this ledger.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($lines->isNotEmpty())
                <tfoot>
                    <tr class="fw-bold fs-6 text-gray-800">
                        <td colspan="6" class="text-end fw-semibold">Closing Balance</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $balance, 4) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
</div>

{{-- Open items (unpaid invoices) --}}
@if(isset($openItems) && $openItems->isNotEmpty())
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between">
            <span>Open Items</span>
            <span class="text-muted small">{{ $openItems->count() }} item(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Entry</th>
                        <th>Description</th>
                        <th class="text-end">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($openItems as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->entry_date)->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('journals.show', $item->journal_entry_id) }}">
                                    <code>{{ $item->number }}</code>
                                </a>
                            </td>
                            <td>{{ $item->description }}</td>
                            <td class="text-end">{{ number_format((float) $item->open_balance, 4) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
</x-default-layout>
