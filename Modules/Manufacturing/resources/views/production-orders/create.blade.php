<x-default-layout>
@section('title', 'New Production Order')

@section('toolbar-button')

  <a href="{{ route('manufacturing.production-orders.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

<form method="POST" action="{{ route('manufacturing.production-orders.store') }}" novalidate>
    @csrf
    @include('manufacturing::production-orders._form', [
        'order'      => $order,
        'bom'        => $bom,
        'activeBoms' => $activeBoms,
        'warehouses' => $warehouses,
        'products'   => \App\Models\Item::query()->where('is_purchasable', false)->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('manufacturing.production-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Order</button>
    </div>
</form>
</x-default-layout>
