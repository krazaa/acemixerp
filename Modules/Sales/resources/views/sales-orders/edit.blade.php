<x-default-layout>
@section('title', 'Edit Sales Order - ' .$salesOrder->number)

@section('toolbar-button')
     <a href="{{ route('sales.sales-orders.show', $salesOrder) }}" class="btn btn-outline-secondary btn-sm">View</a>
@endsection


@if(! $salesOrder->status->isEditable())
    <div class="alert alert-warning">
        This order is <strong>{{ $salesOrder->status->label() }}</strong> and cannot be edited.
    </div>
@endif

<form method="POST" action="{{ route('sales.sales-orders.update', $salesOrder) }}" novalidate>
    @csrf @method('PUT')
    @include('sales::sales-orders._form', ['salesOrder' => $salesOrder])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.sales-orders.show', $salesOrder) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit"
                @if(! $salesOrder->status->isEditable()) disabled @endif>
            Save Changes
        </button>
    </div>
</form>

@push('scripts')
@include('sales::sales-orders._form-scripts', ['salesOrder' => $salesOrder])
@endpush

</x-default-layout>
