<x-default-layout>
@section('title', 'Financial Years')
@section('toolbar-button')
    @can('create', \App\Models\FinancialYear::class)
        <a href="{{ route('financial-years.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Financial Year
        </a>
    @endcan
    <a href="{{ route('financial-years.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Name</th>
                    <th>Range</th>
                    <th class="text-center">Periods</th>
                    <th>Status</th>
                    <th>Current</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($years as $y)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $y->name }}</div>
                            @if($y->closed_at)
                                <div class="text-muted small">Closed {{ $y->closed_at->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $y->start_date->format('d/m/Y') }}
                            <span class="text-muted">→</span>
                            {{ $y->end_date->format('d/m/Y') }}
                        </td>
                        <td class="text-center">
                            <a href="{{ route('accounting-periods.index', ['financial_year_id' => $y->id]) }}">
                                {{ $y->periods()->count() }}
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-{{ $y->status->value === 'open' ? 'success' : ($y->status->value === 'closing' ? 'warning' : 'secondary') }}">
                                {{ $y->status->label() }}
                            </span>
                        </td>
                        <td>
                            @if($y->is_current)
                                <span class="badge badge-primary">Current</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('markCurrent', $y)
                                @if(! $y->is_current && $y->status->value === 'open')
                                    <form method="POST" action="{{ route('financial-years.mark-current', $y) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary">Mark Current</button>
                                    </form>
                                @endif
                            @endcan
                            @can('close', $y)
                                @if($y->status->value === 'open')
                                    <form method="POST" action="{{ route('financial-years.begin-closing', $y) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-warning">Begin Closing</button>
                                    </form>
                                @elseif($y->status->value === 'closing')
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#closeYearModal-{{ $y->id }}">
                                        Close Year
                                    </button>
                                @endif
                            @endcan
                        </td>
                    </tr>

                    @can('close', $y)
                        @if($y->status->value === 'closing')
                            <div class="modal fade" id="closeYearModal-{{ $y->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('financial-years.close', $y) }}" class="modal-content">
                                        @csrf @method('PATCH')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Close {{ $y->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>All periods must be closed. This action is irreversible.</p>
                                            <label class="form-label small">Close Notes</label>
                                            <textarea name="notes" rows="3" class="form-control" maxlength="1000"></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Close this financial year?')">Close Year</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endcan
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No financial years.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
</x-default-layout>
