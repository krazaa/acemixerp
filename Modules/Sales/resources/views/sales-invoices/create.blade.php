<x-default-layout>
@section('title', 'New Sales Invoice')
@section('sub-title', 'A draft invoice is created. Run the three-way match before approval and posting.')

@section('toolbar-button')
    <a href="{{ route('sales.sales-invoices.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
@endsection

@if(! $salesOrder && $openOrders->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Start From a Sales Order (Optional)</div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <select name="sales_order_id" class="form-select" required>
                        <option value="">— Select a delivered order to pre-fill —</option>
                        @foreach($openOrders as $o)
                            <option value="{{ $o->id }}">{{ $o->number }} — {{ $o->customer?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-primary w-100">Pre-fill from Order</button>
                </div>
            </form>
            <div class="form-text">Or continue below to enter a standalone invoice.</div>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('sales.sales-invoices.store') }}" novalidate>
    @csrf
    @include('sales::sales-invoices._form', ['invoice' => $invoice])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.sales-invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>


@push('scripts')
@include('sales::sales-invoices._form-scripts', ['invoice' => $invoice])
@endpush
</x-default-layout>
