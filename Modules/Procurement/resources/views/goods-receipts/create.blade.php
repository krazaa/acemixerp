<x-default-layout>
@section('title', 'New Goods Receipt')

@section('toolbar-button')
    <a href="{{ route('procurement.goods-receipts.index') }}" class="btn btn-sm btn-light-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i>  Back
    </a>
 @endsection


@if($errors->any())
    <div class="alert alert-danger mb-6" role="alert"><div class="fw-bold mb-2">Please check the receipt details.</div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="notice bg-light-primary border border-primary border-dashed rounded p-6 mb-6">
    <div class="fw-bold text-gray-900 mb-1">Record your delivery</div>
    <div class="text-gray-700">Check the received quantities, separate accepted and rejected goods, and add batch details. Brand and origin follow the purchase order where specified.</div>
</div>
@if(! $purchaseOrder)
    {{-- Standalone entry: pick a PO --}}
    <div class="card card-flush mb-6">
        <div class="card-header">
            <div class="card-title">
                Choose Purchase Order
                </div>
            </div>
        <div class="card-body">
            @if($purchaseOrders->isEmpty())
                <div class="alert alert-info mb-0">
                    No open purchase orders to receive against. Create and issue a PO first.
                </div>
            @else
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label small">Purchase Order</label>
                        <select name="purchase_order_id" class="form-select" required>
                            <option value="">— Select PO —</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}">
                                    {{ $po->number }} — {{ $po->vendor?->name }}
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
    <form method="POST" action="{{ route('procurement.goods-receipts.store') }}" novalidate>
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

        <div class="card card-flush mb-6">
            <div class="card-header">
                <div class="card-title">Receipt Header
            </div>
        </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label">Purchase Order</label>
                        <div class="form-control form-control-solid">
                            <code>{{ $purchaseOrder->number }}</code>
                            — {{ $purchaseOrder->vendor?->name }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Destination Warehouse</label>
                        <div class="form-control form-control-solid">{{ $purchaseOrder->warehouse?->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="received_date">
                            Received Date <span class="text-danger">*</span>
                        </label>
                        <input id="received_date" name="received_date" type="date"
                               class="form-control" value="{{ old('received_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="supplier_delivery_note">Delivery Note #</label>
                        <input id="supplier_delivery_note" name="supplier_delivery_note"
                               class="form-control" value="{{ old('supplier_delivery_note') }}" maxlength="64">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="carrier">Driver details</label>
                        <input id="carrier" name="carrier" class="form-control"
                               value="{{ old('carrier') }}" maxlength="200">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="notes">Bilty No</label>
                        <input id="notes" name="notes" class="form-control"
                               value="{{ old('notes') }}" maxlength="40">
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-flush mb-6">
            <div class="card-header align-items-center fw-bold fs-5">Items to receive</div>
            <div class="card-body" id="grn-lines-container">
                @php
                    $lines = old('lines', $prefilledLines ?? []);
                @endphp
                @foreach($lines as $i => $l)
                    @php
                        $poLine = $purchaseOrder->lines->firstWhere('id', (int) ($l['purchase_order_line_id'] ?? 0));
                        $l['purchase_order_line'] = $poLine;
                    @endphp
                    @include('procurement::goods-receipts._line-row', ['index' => $i, 'line' => $l])
                @endforeach
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-3 mt-6">
            <a href="{{ route('procurement.purchase-orders.show', $purchaseOrder) }}" class="btn btn-light">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Draft</button>
        </div>
    </form>
@endif
{{-- @endsection --}}

@push('scripts')
@include('procurement::goods-receipts._form-scripts')
@endpush
</x-default-layout>
