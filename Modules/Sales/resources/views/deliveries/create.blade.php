<x-default-layout>
@section('title', 'New Delivery')
@section('sub-title')
        <div class="text-muted small">A draft delivery is created. Pick and dispatch it to move stock.</div>
@endsection

@section('toolbar-button')
    <a href="{{ route('sales.deliveries.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
@endsection

@if(! $salesOrder)
    {{-- Standalone entry: pick a sales order first --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
                <h3 class="card-title">Choose Sales Order</h3></div>
        <div class="card-body">
            @if($openOrders->isEmpty())
                <div class="alert alert-info mb-0">
                    No confirmed orders available to deliver. Confirm a sales order first.
                </div>
            @else
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label small">Sales Order</label>
                        <select name="sales_order_id" class="form-select" required>
                            <option value="">— Select order —</option>
                            @foreach($openOrders as $o)
                                <option value="{{ $o->id }}">
                                    {{ $o->number }} — {{ $o->customer?->name }}
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
@else
    <form method="POST" action="{{ route('sales.deliveries.store') }}" novalidate>
        @csrf
        @include('sales::deliveries._form', [
            'delivery'   => $delivery,
            'salesOrder' => $salesOrder,
            'openOrders' => $openOrders,
        ])
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('sales.deliveries.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Draft</button>
        </div>
    </form>
@endif
</x-default-layout>
