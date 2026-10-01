<x-default-layout>
@section('title', "Reconcile — {$reconciliation->bankAccount->name}")
@section('sub-title')
  Statement Date: {{ $reconciliation->statement_date->format('d/m/Y') }}
            · <span class="badge text-bg-{{ $reconciliation->status->badgeClass() }}">
                {{ $reconciliation->status->label() }}
            </span>
@endsection

@section('toolbar-button')
 @if($reconciliation->status->value !== 'reconciled')
        <form method="POST" action="{{ route('bank-reconciliation.complete', $reconciliation) }}">
            @csrf @method('PATCH')
            <button class="btn btn-sm btn-success"
                    onclick="return confirm('Complete this reconciliation? No further matching will be possible.')">
                Mark Reconciled
            </button>
        </form>
    @endif
   <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection


@if($reconciliation->status->value !== 'reconciled')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="POST"
              action="{{ route('bank-reconciliation.import', $reconciliation) }}"
              enctype="multipart/form-data"
              class="row g-2 align-items-end">
            @csrf

            <div class="col-md-8">
                <label class="form-label" for="statement_file">CSV Statement File</label>
                <input id="statement_file"
                       name="statement_file"
                       type="file"
                       accept=".csv,text/csv,text/plain"
                       class="form-control @error('statement_file') is-invalid @enderror"
                       required>

                @error('statement_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="form-text">
                    Required headings: Date, Description or Narration, Debit and/or Credit.
                    Optional: Reference. Maximum 5 MB.
                </div>
            </div>

            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    Upload Statement
                </button>
            </div>
        </form>
    </div>
</div>

@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Statement Lines</h3>
            </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="fw-bold fs-6 text-gray-800">
                            <th>Date</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th>Match</th></tr>
                    </thead>
                    <tbody>
                        @foreach($reconciliation->statementLines as $line)
                            <tr class="{{ $line->isMatched() ? 'table-success' : '' }}">
                                <td>{{ $line->transaction_date->format('Y-m-d') }}</td>
                                <td>{{ $line->description }}</td>
                                <td class="text-end">{{ $line->debit > 0 ? number_format((float) $line->debit, 2) : '' }}</td>
                                <td class="text-end">{{ $line->credit > 0 ? number_format((float) $line->credit, 2) : '' }}</td>
                                <td>
                                    @if($line->isMatched())
                                        <span class="badge text-bg-success">Matched</span>
                                        @if($reconciliation->status->value !== 'reconciled')
                                            <form method="POST" action="{{ route('bank-statement-lines.unmatch', $line) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm btn-outline-warning">Unmatch</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="badge text-bg-secondary">Open</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Unmatched Ledger Entries</h3>
            </div>
            <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="fw-bold fs-6 text-gray-800">
                            <th>Date</th><th>Entry</th><th>Description</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        @foreach($unmatchedLedger as $e)
                            <tr>
                                <td>{{ $e->entry_date }}</td>
                                <td><code>{{ $e->number }}</code></td>
                                <td>{{ $e->description }}</td>
                                <td class="text-end">
                                    {{ number_format((float) ($e->debit > 0 ? $e->debit : $e->credit), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</div>
</x-default-layout>
