<x-default-layout>
@section('title', "Period {$period->name}")

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h3 m-0">
            {{ $period->name }}
            <span class="badge text-bg-{{ $period->status->badgeClass() }} ms-2">
                {{ $period->status->label() }}
            </span>
        </h1>
        <div class="text-muted small">
            {{ $period->financialYear?->name }} ·
            {{ $period->start_date->format('Y-m-d') }} → {{ $period->end_date->format('Y-m-d') }}
        </div>
    </div>
    <div class="d-flex gap-2">
        @can('close', $period)
            @if($period->status->value !== 'closed')
                <button class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#closePeriodModal">
                    Close Period
                </button>
            @endif
        @endcan
        @can('reopen', $period)
            @if($period->status->value === 'closed')
                <form method="POST" action="{{ route('accounting-periods.reopen', $period) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-outline-warning"
                            onclick="return confirm('Reopen this period for adjustments?')">
                        Reopen
                    </button>
                </form>
            @endif
        @endcan
        <a href="{{ route('accounting-periods.index', ['financial_year_id' => $period->financial_year_id]) }}"
           class="btn btn-outline-secondary">Back to list</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Financial Year</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('financial-years.show', $period->financial_year_id) }}">
                            {{ $period->financialYear?->name }}
                        </a>
                    </dd>
                    <dt class="col-sm-4">Start Date</dt>
                    <dd class="col-sm-8">{{ $period->start_date->format('Y-m-d') }}</dd>
                    <dt class="col-sm-4">End Date</dt>
                    <dd class="col-sm-8">{{ $period->end_date->format('Y-m-d') }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge text-bg-{{ $period->status->badgeClass() }}">
                            {{ $period->status->label() }}
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Audit</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Created</dt>
                    <dd class="col-sm-7">{{ $period->created_at?->format('Y-m-d H:i') }}</dd>
                    <dt class="col-sm-5">Last Updated</dt>
                    <dd class="col-sm-7">{{ $period->updated_at?->format('Y-m-d H:i') }}</dd>
                    <dt class="col-sm-5">Closed At</dt>
                    <dd class="col-sm-7">{{ $period->closed_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    <dt class="col-sm-5">Closed By</dt>
                    <dd class="col-sm-7">{{ $period->closer?->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @if($period->close_notes)
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Close Notes</div>
                <div class="card-body" style="white-space: pre-wrap;">{{ $period->close_notes }}</div>
            </div>
        </div>
    @endif
</div>

@can('close', $period)
    @if($period->status->value !== 'closed')
        @push('modals')
        <div class="modal fade" id="closePeriodModal" tabindex="-1" aria-labelledby="closePeriodTitle" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('accounting-periods.close', $period) }}" class="modal-content">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="closePeriodTitle">Close {{ $period->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Dismiss"></button>
                    </div>
                    <div class="modal-body">
                        <p>Once closed, no postings can be made to this period. Reopening is possible but audited.</p>
                        <label class="form-label small">Close Notes</label>
                        <textarea name="notes" rows="3" class="form-control" maxlength="500"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Close Period</button>
                    </div>
                </form>
            </div>
        </div>
        @endpush
    @endif
@endcan
</x-default-layout>
