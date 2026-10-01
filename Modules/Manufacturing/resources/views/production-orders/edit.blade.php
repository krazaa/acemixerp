<x-default-layout>
@section('title', 'Edit Production Order - ' . $order->name)
    @section('toolbar-button')
        <a href="{{ route('manufacturing.production-orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">View</a>
        <a href="{{ route('manufacturing.production-orders.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
    @endsection


@if(! $order->status->isEditable())
    <div class="alert alert-warning">
        This order is <strong>{{ $order->status->label() }}</strong> and cannot be edited.
    </div>
@endif

<form method="POST" action="{{ route('manufacturing.production-orders.update', $order) }}" novalidate>
    @csrf @method('PUT')
    @include('manufacturing::production-orders._form', [
        'order'      => $order,
        'bom'        => $bom,
        'activeBoms' => $activeBoms,
        'warehouses' => $warehouses,
        'products'   => \App\Models\Item::query()->where('is_purchasable', true)->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('manufacturing.production-orders.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit"
                @if(! $order->status->isEditable()) disabled @endif>
            Save Changes
        </button>
    </div>
</form>
</x-default-layout>
