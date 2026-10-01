<x-default-layout>
@section('title', 'New Supplier Invoice')
@section('sub-title')
 <div class="text-muted small">
            A draft invoice is created. Run the three-way match before approval and posting.
        </div>
@endsection

@section('toolbar-button')
     <a href="{{ route('procurement.supplier-invoices.index') }}" class="btn btn-outline-secondary btn-sm">
        Back to list
    </a>
@endsection

@if(! $purchaseOrder)
    {{-- Standalone entry: pick a PO first --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title">Choose Purchase Order</h3>
            </div>
        <div class="card-body">
            @if($purchaseOrders->isEmpty())
                <div class="alert alert-info mb-0">
                    No issued purchase orders to invoice. Create and issue a PO first.
                </div>
            @else
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label small">Purchase Order</label>
                        <select name="purchase_order_id" class="form-select" required>
                            <option value="">— Select PO —</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}">
                                    {{ $po->number }} — {{ $po->vendor?->name }} ({{ $po->currency_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100">Continue</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
    <div class="text-center text-muted mb-3">or</div>
    <div class="text-center"><a href="{{ route('procurement.supplier-invoices.create', ['standalone' => 1]) }}" class="btn btn-outline-primary">New Vendor Invoice Without PO</a></div>
@else
    <form method="POST" action="{{ route('procurement.supplier-invoices.store') }}" novalidate>
        @csrf

       @include('procurement::supplier-invoices._form', [
    'invoice'         => $invoice,
    'purchaseOrder'   => $purchaseOrder,
    'purchaseOrders'  => $purchaseOrders,
    'prefilledLines'  => $prefilledLines ?? [],
])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('procurement.supplier-invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Draft</button>
        </div>
    </form>
@endif

@push('scripts')
@include('procurement::supplier-invoices._form-scripts', ['invoice' => $invoice])
@endpush
</x-default-layout>
