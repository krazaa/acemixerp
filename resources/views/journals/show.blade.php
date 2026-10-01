<x-default-layout>
@section('title', $entry->number)
@section('sub-title', $entry->number . ' · Journal Entry')

 @section('toolbar-button')
        <a href="{{ route('journals.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  All journal entries
        </a>

 @endsection
<style>
    .journal-detail { --journal-border: var(--bs-border-color, #e4e6ef); }
    .journal-detail .journal-card { border: 1px solid var(--journal-border); border-radius: 1rem; overflow: hidden; }
    .journal-detail .journal-label { font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; color: var(--bs-secondary-color, #7e8299); font-weight: 600; }
    .journal-detail .journal-amount { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .journal-detail .journal-table { min-width: 760px; }
    .journal-detail .journal-table > :not(caption) > * > * { padding: 1.15rem 1.25rem; }
    .journal-detail .journal-table thead th { font-size: .75rem; letter-spacing: .05em; text-transform: uppercase; color: var(--bs-secondary-color, #7e8299); }
    .journal-detail .journal-notes { white-space: pre-wrap; overflow-wrap: anywhere; }
    .journal-detail .journal-activity { border-left: 2px solid var(--journal-border); padding-left: 1.25rem; }
    .journal-detail .journal-summary { border-top: 3px solid var(--bs-primary, #009ef7); }
    .journal-detail .journal-actions .btn { white-space: nowrap; }
</style>

<div class="journal-detail">


<div class="d-flex flex-column flex-xl-row justify-content-between align-items-start gap-4 mb-6">
    <div>

        <h1 class="h2 mb-2 d-flex flex-wrap align-items-center gap-3 text-break">
            {{ $entry->number }}
            <span class="badge badge-light-{{ $entry->status->badgeClass() }} fs-7">
                {{ $entry->status->label() }}
            </span>
        </h1>
        <div class="text-muted small">
            {{ $entry->entry_date->format('d/m/Y') }}
            @if($entry->reference) · Ref: {{ $entry->reference }}@endif
        </div>
    </div>
    <div class="journal-actions d-flex gap-2 flex-wrap">
        @can('submit', $entry)
            <form method="POST" action="{{ route('journals.submit', $entry) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-light-primary">Submit for Approval</button>
            </form>
        @endcan
        @can('approve', $entry)
            <form method="POST" action="{{ route('journals.approve', $entry) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-light-info">Approve</button>
            </form>
            <button class="btn btn-sm btn-light-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
        @endcan
        @can('post', $entry)
            <form method="POST" action="{{ route('journals.post', $entry) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success"
                        onclick="return confirm('Post this journal? This action is irreversible.')">Post</button>
            </form>
        @endcan
        @can('reverse', $entry)
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#reverseModal">Reverse</button>
        @endcan
        @can('update', $entry)
            <a href="{{ route('journals.edit', $entry) }}" class="btn btn-sm btn-light-primary">Edit</a>
        @endcan
        @can('cancel', $entry)
            <form method="POST" action="{{ route('journals.cancel', $entry) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-ligh-secondary"
                        onclick="return confirm('Cancel this journal?')">Cancel</button>
            </form>
        @endcan
    </div>
</div>

<div class="row g-4 mb-6">
    <div class="col-sm-6 col-xl-4">
        <div class="card journal-card journal-summary h-100">
            <div class="card-body p-5">
                <div class="journal-label mb-3">Total debit</div>
                <div class="fs-2 fw-bold journal-amount">{{ number_format((float) $entry->total_debit, 2) }} <span class="fs-7 text-muted fw-normal">{{ $entry->currency_code }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card journal-card journal-summary h-100">
            <div class="card-body p-5">
                <div class="journal-label mb-3">Total credit</div>
                <div class="fs-2 fw-bold journal-amount">{{ number_format((float) $entry->total_credit, 2) }} <span class="fs-7 text-muted fw-normal">{{ $entry->currency_code }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card journal-card h-100">
            <div class="card-body p-5">
                <div class="journal-label mb-3">Accounting period</div>
                <div class="fs-4 fw-semibold text-break">{{ $entry->period?->name ?? '—' }}</div>
                <div class="text-muted mt-1">Financial year: {{ $entry->financialYear?->name ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card journal-card mb-6">
    <div class="card-body p-5 p-md-6">
        <div class="row g-5">
            <div class="col-md-8">
                <h2 class="journal-label mb-3">Description</h2>
                <p class="fs-5 fw-semibold mb-0 text-break">{{ $entry->description }}</p>
                @if($entry->notes)
                    <div class="border-top pt-4 mt-4">
                        <h3 class="journal-label mb-2">Notes</h3>
                        <div class="text-muted journal-notes">{{ $entry->notes }}</div>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <dl class="mb-0">
                    <dt class="journal-label mb-2">Entry date</dt>
                    <dd class="fw-semibold mb-4">{{ $entry->entry_date->format('d M Y') }}</dd>
                    <dt class="journal-label mb-2">Reference</dt>
                    <dd class="fw-semibold mb-0 text-break">{{ $entry->reference ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@if($entry->reverses || $entry->reversedBy)
    <div class="alert alert-info">
        @if($entry->reverses)
            Reversal of <a href="{{ route('journals.show', $entry->reverses) }}">{{ $entry->reverses->number }}</a>
        @endif
        @if($entry->reversedBy)
            Reversed by <a href="{{ route('journals.show', $entry->reversedBy) }}">{{ $entry->reversedBy->number }}</a>
        @endif
    </div>
@endif

<div class="card journal-card mb-6">
    <div class="card-header align-items-center flex-wrap gap-3 py-4 px-5">
        <div>
            <h2 class="fs-4 fw-bold mb-1">Journal lines</h2>
            <div class="text-muted fs-7">Account allocations and transaction details</div>
        </div>
        <span class="badge badge-light-primary">{{ $entry->lines->count() }} lines · {{ $entry->currency_code }}</span>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table table-row-dashed table-row-gray-300 journal-table">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th scope="col">#</th>
                    <th scope="col">Account</th>
                    <th scope="col">Memo</th>
                    <th scope="col">Dimensions</th>
                    <th scope="col" class="text-end">Debit</th>
                    <th scope="col" class="text-end">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold mb-1">{{ $line->account?->name }}</div>
                            <span class="text-muted fs-7 font-monospace">{{ $line->account?->code }}</span>
                        </td>
                        <td class="text-muted small">{{ $line->memo ?: '—' }}</td>
                        <td class="text-muted small">
                            @if($line->costCenter)<div class="mb-1">Cost center: <span class="text-body">{{ $line->costCenter->code }}</span></div>@endif
                            @if($line->department)<div class="mb-1">Department: <span class="text-body">{{ $line->department->name }}</span></div>@endif
                            @if($line->customer)<div class="mb-1">Customer: <span class="text-body">{{ $line->customer->name }}</span></div>@endif
                            @if($line->employee)<div class="mb-1">Employee: <span class="text-body">{{ $line->employee->fullName() }}</span></div>@endif
                            @if($line->vendor)<div class="mb-1">Vendor: <span class="text-body">{{ $line->vendor->name }}</span></div>@endif
                        </td>
                        <td class="text-end journal-amount">{{ bccomp($line->debit, '0', 4) > 0 ? number_format((float) $line->debit, 2) : '' }}</td>
                        <td class="text-end journal-amount">{{ bccomp($line->credit, '0', 4) > 0 ? number_format((float) $line->credit, 2) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end">Totals</td>
                    <td class="text-end journal-amount">{{ number_format((float) $entry->total_debit, 2) }}</td>
                    <td class="text-end journal-amount">{{ number_format((float) $entry->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>

<div class="card journal-card mb-6">
    <div class="card-body p-5 p-md-6">
        <h2 class="fs-4 fw-bold mb-5">Activity</h2>
        <div class="row g-5">
            <div class="col-sm-6 col-xl-3">
                <div class="journal-activity">
                    <div class="journal-label mb-2">Created</div>
                    <div class="fw-semibold text-break">{{ $entry->creator?->name ?? '—' }}</div>
                    <div class="text-muted fs-7 mt-1">{{ $entry->created_at?->format('d M Y · h:i A') }}</div>
                </div>
            </div>
            @foreach(['submitted' => 'submitter', 'approved' => 'approver', 'posted' => 'poster'] as $event => $actor)
                @if($entry->{$event . '_at'})
                    <div class="col-sm-6 col-xl-3">
                        <div class="journal-activity">
                            <div class="journal-label mb-2">{{ ucfirst($event) }}</div>
                            <div class="fw-semibold text-break">{{ $entry->{$actor}?->name ?? '—' }}</div>
                            <div class="text-muted fs-7 mt-1">{{ $entry->{$event . '_at'}->format('d M Y · h:i A') }}</div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
</div>

{{-- Modals --}}
@can('reject', $entry)
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('journals.reject', $entry) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title" id="rejectModalTitle">Reject Journal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <label for="rejectReason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea id="rejectReason" name="reason" class="form-control" rows="3" required maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
@endcan

@can('reverse', $entry)
    <div class="modal fade" id="reverseModal" tabindex="-1" aria-labelledby="reverseModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('journals.reverse', $entry) }}" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title" id="reverseModalTitle">Reverse Journal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p>This creates a new posted journal that mirrors the original with debits and credits swapped.
                    The original is marked Reversed and cannot be edited further.</p>
                    <label for="reverseReason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea id="reverseReason" name="reason" class="form-control" rows="3" required minlength="3" maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Create Reversal</button>
                </div>
            </form>
        </div>
    </div>
@endcan
</x-default-layout>
