<x-default-layout>
@section('title', 'Accounting Periods')

@section('toolbar-button')
    <a href="{{ route('financial-years.index') }}" class="btn btn-sm btn-secondary btn-sm">
         <i class="fas fa-arrow-left fa-sm"></i>  Financial Years
        </a>
@endsection

<div class="row g-2 mb-3">
    <form method="GET" class="col-md-4">
        <label class="form-label small mb-1">Financial Year</label>
        <select name="financial_year_id" class="form-select" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y->id }}" @selected(optional($year)->id === $y->id)>
                    {{ $y->name }} ({{ $y->start_date->format('Y-m-d') }} → {{ $y->end_date->format('Y-m-d') }})
                </option>
            @endforeach
        </select>
    </form>
    @if($year)
        @can('year.create')
            <div class="col-md-3 align-self-end">
                <form method="POST" action="{{ route('accounting-periods.generate', $year) }}">
                    @csrf
                    <div class="input-group">
                        <input name="months" type="number" min="1" max="24" value="12" class="form-control">
                        <button class="btn btn-info">Generate Periods</button>
                    </div>
                </form>
            </div>
        @endcan
    @endif
</div>

@if($year)
    <div class="card border-0 shadow-sm">
        <div class="card-header">
        <h3 class="card-title">{{ $year->name }} — Periodse</h3>
            <div class="card-toolbar">
                {{ $periods->count() }} period(s)
            </div>
        </div>
        <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="fw-bold fs-6 text-gray-800">
                        <th>Name</th>
                        <th>Range</th>
                        <th>Status</th>
                        <th>Closed</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->name }}</td>
                            <td>{{ $p->start_date->format('Y-m-d') }} → {{ $p->end_date->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge badge-{{ $p->status->badgeClass() }}">
                                    {{ $p->status->label() }}
                                </span>
                            </td>
                            <td class="small text-muted">
                                @if($p->closed_at)
                                    {{ $p->closed_at->format('Y-m-d H:i') }}
                                    @if($p->closer) by {{ $p->closer->name }}@endif
                                @else — @endif
                            </td>
                            <td class="text-end">
                                @can('close', $p)
                                    @if($p->status->value !== 'closed')
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#closePeriodModal-{{ $p->id }}">
                                            Close
                                        </button>
                                    @endif
                                @endcan
                                @can('reopen', $p)
                                    @if($p->status->value === 'closed')
                                        <form method="POST" action="{{ route('accounting-periods.reopen', $p) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-outline-warning"
                                                    onclick="return confirm('Reopen this period for adjustments?')">
                                                Reopen
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>

                        @can('close', $p)
                            @if($p->status->value !== 'closed')
                                @push('modals')
                                <div class="modal fade" id="closePeriodModal-{{ $p->id }}" tabindex="-1" aria-labelledby="closePeriodTitle-{{ $p->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('accounting-periods.close', $p) }}" class="modal-content">
                                            @csrf @method('PATCH')
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="closePeriodTitle-{{ $p->id }}">Close {{ $p->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Dismiss"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Once closed, no postings can be made to this period.</p>
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
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No periods for this year.
                                @can('year.create')
                                    Use "Generate Periods" above.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
    <div class="alert alert-info">Select a financial year to view periods.</div>
@endif
</x-default-layout>
