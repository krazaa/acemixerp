<x-default-layout>
@section('title', 'New Sales Order')

@section('toolbar-button')
 <a href="{{ route('sales.sales-orders.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
@endsection

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form method="POST" action="{{ route('sales.sales-orders.store') }}" novalidate>
    @csrf
    @include('sales::sales-orders._form', ['salesOrder' => $salesOrder])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.sales-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>


@push('scripts')
@include('sales::sales-orders._form-scripts', ['salesOrder' => $salesOrder])
@endpush

</x-default-layout>
