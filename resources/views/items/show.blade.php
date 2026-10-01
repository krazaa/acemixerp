<x-default-layout>
@section('title', $item->name)
@section('sub-title')
    <code>{{ $item->code }}</code>
    @if($item->sku) · SKU: {{ $item->sku }}@endif
    @if($item->barcode) · Barcode: {{ $item->barcode }}@endif
@endsection

@section('toolbar-button')
 @can('update', $item)
            <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-light-primary">Edit</a>
        @endcan
        @can('delete', $item)
            <form method="POST" action="{{ route('items.destroy', $item) }}"
                  onsubmit="return confirm('Delete this item?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-light-danger">Delete</button>
            </form>
        @endcan
          <a href="{{ route('items.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8"><span class="badge badge-light border">{{ $item->item_type->label() }}</span></dd>
                    <dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $item->category?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Unit</dt><dd class="col-sm-8">{{ $item->unit?->code ?? '—' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-{{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                    </dd>
                    <dt class="col-sm-4">Sellable</dt>
                    <dd class="col-sm-8">{!! $item->is_sellable ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>' !!}</dd>
                    <dt class="col-sm-4">Purchasable</dt>
                    <dd class="col-sm-8">{!! $item->is_purchasable ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>' !!}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Cost Price</dt>
                    <dd class="col-sm-6 text-end">{{ number_format((float) $item->cost_price, 2) }}</dd>
                    <dt class="col-sm-6">Selling Price</dt>
                    <dd class="col-sm-6 text-end">{{ number_format((float) $item->selling_price, 2) }}</dd>
                    <dt class="col-sm-6">Min Selling Price</dt>
                    <dd class="col-sm-6 text-end">{{ $item->minimum_selling_price ? number_format((float) $item->minimum_selling_price, 2) : '—' }}</dd>
                    <dt class="col-sm-6">Margin</dt>
                    <dd class="col-sm-6 text-end">{{ $item->marginPercent() !== null ? number_format($item->marginPercent(), 2) . '%' : '—' }}</dd>
                    <dt class="col-sm-6">Tax Exempt</dt>
                    <dd class="col-sm-6 text-end">{!! $item->is_tax_exempt ? '<span class="badge text-bg-warning">Yes</span>' : 'No' !!}</dd>
                </dl>
            </div>
        </div>
    </div>

    @if($item->tracksInventory())
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-muted small">Reorder Level</div>
                            <div>{{ number_format((float) $item->reorder_level, 2) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Minimum Stock</div>
                            <div>{{ number_format((float) $item->minimum_stock, 2) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Maximum Stock</div>
                            <div>{{ $item->maximum_stock ? number_format((float) $item->maximum_stock, 2) : '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Allow Negative</div>
                            <div>{{ $item->allow_negative_stock ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="text-muted small">Track Batch</div>
                            <div>{{ $item->track_batch ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Track Serial</div>
                            <div>{{ $item->track_serial ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Track Expiry</div>
                            <div>{{ $item->track_expiry ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
</x-default-layout>
