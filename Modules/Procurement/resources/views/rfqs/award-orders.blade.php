@if($rfq->status->value === 'awarded' && $rfq->converted_to_id === null)
    @can('createPurchaseOrders', $rfq)
        <form method="POST" action="{{ route('procurement.rfqs.create-po', $rfq) }}" class="my-3">
            @csrf
            <button class="btn btn-success" type="submit">Create Purchase Orders</button>
            <span class="text-muted small ms-2">One draft purchase order per winning vendor, containing only their awarded products.</span>
        </form>
    @endcan
@endif
@if($rfq->purchaseOrders->isNotEmpty())
    <div class="card my-3">
        <div class="card-body">
        <h2 class="h5">Purchase Orders</h2>
        @foreach($rfq->purchaseOrders as $order)
            @can('view', $order)
                <a class="btn btn-outline-primary me-2 mb-2" href="{{ route('procurement.purchase-orders.show', $order) }}">{{ $order->number }} — {{ $order->vendor?->name }}</a>
            @endcan
        @endforeach
    </div></div>
@endif
