<x-default-layout>
@section('title', 'New Quotation')

@section('toolbar-button')
     <a href="{{ route('sales.quotations.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
@endsection

<form method="POST" action="{{ route('sales.quotations.store') }}" novalidate>
    @csrf
    @include('sales::quotations._form', ['quotation' => $quotation])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.quotations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>

@push('scripts')
@include('sales::quotations._form-scripts', ['quotation' => $quotation, 'items' => $items, 'units' => $units])
@endpush
</x-default-layout>
