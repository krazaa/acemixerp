<x-default-layout>
@section('title', $bom->name)

 @section('toolbar-button')
        @can('update', $bom)
            <a href="{{ route('manufacturing.boms.edit', $bom) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan
        @if($bom->status->value === 'draft')
            @can('update', $bom)
                <form method="POST" action="{{ route('manufacturing.boms.activate', $bom) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-success"
                            onclick="return confirm('Activate this BOM? Any currently active BOM for this product will be superseded.')">
                        Activate
                    </button>
                </form>
            @endcan
        @endif
        @can('create', \Modules\Manufacturing\Models\ProductionOrder::class)
            <a href="{{ route('manufacturing.production-orders.create', ['bom_id' => $bom->id]) }}"
               class="btn btn-sm btn-primary">
                <i class="bi bi-play-circle"></i> Create Production Order
            </a>
        @endcan
    <a href="{{ route('manufacturing.boms.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                 <h3 class="card-title">Details</h3>
            <div class="card-toolbar">
            <span class="badge badge-{{ $bom->status->badgeClass() }} ms-2">{{ $bom->status->label() }}</span>
            <span class="badge badge-light border ms-1">v{{ $bom->revision }}</span>
            <div>
                <div class=" small ms-2">
            Product: {{ $bom->product?->name }}
            · Output: {{ number_format((float) $bom->output_quantity, 4) }} {{ $bom->outputUnit?->name }}

    </div>
            </div>

        </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Product</dt><dd class="col-sm-8">{{ $bom->product?->name }}</dd>
                    <dt class="col-sm-4">Output</dt><dd class="col-sm-8">{{ number_format((float) $bom->output_quantity, 0) }} {{ $bom->outputUnit?->name }}</dd>
                    <dt class="col-sm-4">Labour Cost</dt><dd class="col-sm-8">{{ number_format((float) $bom->labour_cost, 2) }}</dd>
                    <dt class="col-sm-4">Overhead Cost</dt><dd class="col-sm-8">{{ number_format((float) $bom->overhead_cost, 2) }}</dd>
                    <dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $bom->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Estimated Cost</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Materials</span><span>{{ number_format((float) $estimatedMaterials, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Labour</span><span>{{ number_format((float) $bom->labour_cost, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Overhead</span><span>{{ number_format((float) $bom->overhead_cost, 2) }}</span></div>
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-semibold fs-5">
                    <span>Unit Cost</span>
                    <span>{{ number_format((float) $estimatedUnitCost, 4) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Components</h3></div>
        <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead >
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>#</th><th>Component</th><th>Stock Batch Number</th><th>Unit</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Scrap %</th>
                    <th class="text-end">Effective</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Line Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bom->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td> {{ $line->component?->name }}</td>
                        <td>{{ $line->batch?->number ?? '—' }}</td>
                        <td>{{ $line->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 3) }}</td>
                        <td class="text-end">{{ number_format((float) $line->scrap_percent, 3) }}</td>
                        <td class="text-end">{{ number_format((float) $line->effectiveQuantity(),3) }}</td>
                        <td class="text-end">{{ number_format((float) ($line->component?->cost_price ?? 0), 2) }}</td>
                        <td class="text-end">{{ number_format((float) bcmul($line->effectiveQuantity(), (string) ($line->component?->cost_price ?? 0), 2), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</div>
</x-default-layout>
