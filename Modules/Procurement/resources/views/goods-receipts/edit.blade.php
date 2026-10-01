<x-default-layout>
@section('title', 'Edit Goods Receipt - ' . $goodsReceipt->number)



@section('toolbar-button')
        <a href="{{ route('procurement.goods-receipts.show', $goodsReceipt) }}" class="btn btn-outline-secondary btn-sm">View</a>

        <a href="{{ route('procurement.goods-receipts.index') }}" class="btn btn-sm btn-light-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection

@if(! $goodsReceipt->status->isEditable())
    <div class="alert alert-warning">
        This GRN is <strong>{{ $goodsReceipt->status->label() }}</strong> and cannot be edited.
    </div>
@endif

<form method="POST" action="{{ route('procurement.goods-receipts.update', $goodsReceipt) }}" novalidate>
    @csrf @method('PUT')
    <input type="hidden" name="purchase_order_id" value="{{ $goodsReceipt->purchase_order_id }}">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title">Receipt Header</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="received_date">Received Date <span class="text-danger">*</span></label>
                    <input id="received_date" name="received_date" type="date"
                           class="form-control"
                           value="{{ old('received_date', $goodsReceipt->received_date->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="supplier_delivery_note">Delivery Note #</label>
                    <input id="supplier_delivery_note" name="supplier_delivery_note"
                           class="form-control" value="{{ old('supplier_delivery_note', $goodsReceipt->supplier_delivery_note) }}" maxlength="64">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="carrier">Driver Details</label>
                    <input id="carrier" name="carrier" class="form-control"
                           value="{{ old('carrier', $goodsReceipt->carrier) }}" maxlength="64">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="notes">Bilty No</label>
                    <input id="notes" name="notes" class="form-control"
                           value="{{ old('notes', $goodsReceipt->notes) }}" maxlength="40">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title">Lines</h3>
        </div>
        <div class="card-body" id="grn-lines-container">
            @php
                $existing = old('lines');

                if ($existing === null) {
                    $existing = $goodsReceipt->lines->map(fn ($line) => [
                        'purchase_order_line_id' => $line->purchase_order_line_id,
                        'item_id' => $line->item_id,
                        'received_quantity' => $line->received_quantity,
                        'accepted_quantity' => $line->accepted_quantity,
                        'rejected_quantity' => $line->rejected_quantity,
                        'batch_number' => $line->batch_number,
                        'expiry_date' => $line->expiry_date?->toDateString(),
                        'manufacturing_date' => $line->manufacturing_date?->toDateString(),
                        'rejection_reason' => $line->rejection_reason,
                        'notes' => $line->notes,
                    ])->all();
                }
            @endphp
            @foreach($existing as $i => $l)
                @php
                    $poLine = $goodsReceipt->purchaseOrder->lines->firstWhere('id', (int) ($l['purchase_order_line_id'] ?? 0));
                    $l['purchase_order_line'] = $poLine;
                @endphp
                @include('procurement::goods-receipts._line-row', ['index' => $i, 'line' => $l])
            @endforeach
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.goods-receipts.show', $goodsReceipt) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit"
                @if(! $goodsReceipt->status->isEditable()) disabled @endif>
            Save Changes
        </button>
    </div>
</form>


@push('scripts')
@include('procurement::goods-receipts._form-scripts')
@endpush
</x-default-layout>
