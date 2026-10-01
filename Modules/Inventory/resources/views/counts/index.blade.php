<x-default-layout>

@section('title', 'Stock Counts')

@section('toolbar-button')
        @can('create', \Modules\Inventory\Models\StockCount::class)
            <a href="{{ route('inventory.counts.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> New Stock Count
            </a>
        @endcan
  <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All statuses</option>
                @foreach(\Modules\Inventory\Enums\StockCountStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="warehouse_id" class="form-select form-select-sm">
                <option value="">All warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
        <div class="col-md-2">
            <a href="{{ route('inventory.counts.index') }}" class="btn btn-sm btn-light-secondary w-100">Reset</a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Number</th>
                        <th>Date</th>
                        <th>Warehouse</th>
                        <th>Scope</th>
                        <th class="text-center">Lines</th>
                        <th class="text-end">Total Variance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($counts as $count)
                        @php($totalVariance = (float) ($count->lines_sum_variance ?? 0))
                        <tr>
                            <td><code>{{ $count->number }}</code></td>
                            <td>{{ $count->count_date->format('Y-m-d') }}</td>
                            <td>{{ $count->warehouse?->name }}</td>
                            <td class="text-muted small">{{ $count->scope ?: '—' }}</td>
                            <td class="text-center">{{ $count->lines_count }}</td>
                            <td class="text-end {{ $totalVariance > 0 ? 'text-success' : ($totalVariance < 0 ? 'text-danger' : 'text-muted') }}">
                                {{ number_format($totalVariance, 2) }}
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $count->status->badgeClass() }}">{{ $count->status->label() }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('inventory.counts.show', $count) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @can('update', $count)
                                    @if(in_array($count->status->value, ['draft', 'counting'], true))
                                        <a href="{{ route('inventory.counts.edit', $count) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No stock counts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <div class="mt-3">{{ $counts->links() }}</div>
</x-default-layout>
