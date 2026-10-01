<x-default-layout>
@section('title', 'Items')

@section('toolbar-button')
 @can('create', \App\Models\Item::class)
        <a href="{{ route('items.create') }}" class="btn btn-sm btn-primary">New Item</a>
    @endcan
@endsection



<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control"
               placeholder="Search name, code, SKU, barcode">
    </div>
    <div class="col-md-2">
        <select name="item_type" class="form-select">
            <option value="">All types</option>
            @foreach(\App\Enums\ItemType::cases() as $t)
                <option value="{{ $t->value }}" @selected(request('item_type') === $t->value)>{{ $t->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="category_id" class="form-select">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><button class="btn btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('items.index') }}" class="btn btn-light-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
        <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Code / SKU</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th class="text-end">Cost</th>
                    <th class="text-end">Sell</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i)
                    <tr>
                        <td>
                            <code>{{ $i->code }}</code>
                            @if($i->sku)<div class="text-muted small">SKU: {{ $i->sku }}</div>@endif
                        </td>
                        <td>
                            <a href="{{ route('items.show', $i) }}" class="text-decoration-none fw-semibold">
                                {{ $i->name }}
                            </a>
                        </td>
                        <td><span class="badge badge-light border">{{ $i->item_type->label() }}</span></td>
                        <td>{{ $i->category?->name ?? '—' }}</td>
                        <td>{{ $i->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $i->cost_price, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $i->selling_price, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $i->status->badgeClass() }}">{{ $i->status->label() }}</span>
                        </td>
                        <td class="text-end">
                            @can('update', $i)
                                <a href="{{ route('items.edit', $i) }}" class="btn btn-sm btn-light-primary">Edit</a>
                            @endcan
                            @can('delete', $i)
                                <form method="POST" action="{{ route('items.destroy', $i) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this item?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No items yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $items->links() }}</div>
</x-default-layout>
