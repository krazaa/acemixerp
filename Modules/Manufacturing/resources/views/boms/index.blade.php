<x-default-layout>
@section('title', 'Bills of Materials')

@section('toolbar-button')
        @can('create', \Modules\Manufacturing\Models\BillOfMaterials::class)
        <a href="{{ route('manufacturing.boms.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New BOM
        </a>
    @endcan
  <a href="{{ route('manufacturing.boms.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm"
               placeholder="Search code or name">
    </div>
    <div class="col-md-3">
        <select name="product_id" class="form-select form-select-sm">
            <option value="">All products</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>
                    {{ $p->code }} — {{ $p->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Manufacturing\Enums\BomStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('manufacturing.boms.index') }}" class="btn btn-sm btn-light-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Name</th>
                    <th>Product</th>
                    <th class="text-center">Rev</th>
                    <th class="text-end">Output</th>
                    <th class="text-center">Ingredients</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($boms as $bom)
                    <tr>

                        <td>
                            <a href="{{ route('manufacturing.boms.show', $bom) }}" class="text-decoration-none">
                                {{ $bom->name }}
                            </a>
                        </td>
                        <td>
                         {{ $bom->product?->name }}
                        </td>
                        <td class="text-center">v{{ $bom->revision }}</td>
                        <td class="text-end">
                            {{ number_format((float) $bom->output_quantity, 0) }}
                            {{ $bom->outputUnit?->name }}
                        </td>
                        <td class="text-center">{{ $bom->lines_count }}</td>
                        <td><span class="badge badge-{{ $bom->status->badgeClass() }}">{{ $bom->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('manufacturing.boms.show', $bom) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $bom)
                                <a href="{{ route('manufacturing.boms.edit', $bom) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No BOMs defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $boms->links() }}</div>
</x-default-layout>
