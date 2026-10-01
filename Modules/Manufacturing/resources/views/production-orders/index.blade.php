<x-default-layout>
@section('title', 'Production Orders')

@section('toolbar-button')
    @can('create', \Modules\Manufacturing\Models\ProductionOrder::class)
        <a href="{{ route('manufacturing.production-orders.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Production Order
        </a>
    @endcan
  <a href="{{ route('manufacturing.production-orders.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number"></div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Manufacturing\Enums\ProductionOrderStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1">
        <select name="type" class="form-select form-select-sm">
            <option value="">All types</option>
            @foreach(\Modules\Manufacturing\Enums\ProductionOrderType::cases() as $t)
                <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('manufacturing.production-orders.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
        <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th>
                    <th>Product</th>
                    <th>BOM</th>
                    <th class="text-end">Planned</th>
                    <th class="text-end">Produced</th>
                    <th class="text-end">Cost</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td><code>{{ $o->number }}</code></td>
                        <td>{{ $o->product?->name }}</td>
                        <td><code>{{ $o->bom?->code }}</code></td>
                        <td class="text-end">{{ number_format((float) $o->planned_quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $o->produced_quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $o->total_cost, 2) }}</td>
                        <td><span class="badge text-bg-{{ $o->status->badgeClass() }}">{{ $o->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('manufacturing.production-orders.show', $o) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $o)
                                <a href="{{ route('manufacturing.production-orders.edit', $o) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No production orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
<div class="mt-3">{{ $orders->links() }}</div>
</x-default-layout>
