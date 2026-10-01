<x-default-layout>
@section('title', 'Edit Delivery - '. $delivery->number)

@section('toolbar-button')
    <a href="{{ route('sales.deliveries.show', $delivery) }}" class="btn btn-outline-secondary btn-sm">View</a>
@endsection

@if(! $delivery->status->isEditable())
    <div class="alert alert-warning">
        This delivery is <strong>{{ $delivery->status->label() }}</strong> and cannot be edited.
    </div>
@endif

<form method="POST" action="{{ route('sales.deliveries.update', $delivery) }}" novalidate>
    @csrf @method('PUT')
    @include('sales::deliveries._form', [
        'delivery'   => $delivery,
        'salesOrder' => $delivery->salesOrder->load('lines.item'),
        'openOrders' => collect(),
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.deliveries.show', $delivery) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit"
                @if(! $delivery->status->isEditable()) disabled @endif>
            Save Changes
        </button>
    </div>
</form>
</x-default-layout>
