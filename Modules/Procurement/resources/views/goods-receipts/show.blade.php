<x-default-layout>
@section('title', $goodsReceipt->number)
@section('sub-title')

        <div class="text-muted small">
            Received {{ $goodsReceipt->received_date->format('d/m/Y') }}
            · Against PO
            <a href="{{ route('procurement.purchase-orders.show', $goodsReceipt->purchase_order_id) }}">
                {{ $goodsReceipt->purchaseOrder?->number }}
            </a>
             <span class="badge badge-{{ $goodsReceipt->status->badgeClass() }} ms-2">
        {{ $goodsReceipt->status->label() }}
        </span>
        </div>
@endsection

 @section('toolbar-button')
  @can('post', $goodsReceipt)
            <form method="POST" action="{{ route('procurement.goods-receipts.post', $goodsReceipt) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-light-success"
                        onclick="return confirm('Post this GRN? Once posted, quantities cannot be changed and stock movements will be queued.')">
                    Post GRN
                </button>
            </form>
        @endcan
        <a href="{{ route('procurement.goods-receipts.print', $goodsReceipt) }}"
            target="_blank"
            class="btn btn-sm btn-light-dark">
                <i class="fas fa-print me-1"></i>
                Print GRN
            </a>
        @can('cancel', $goodsReceipt)
            <form method="POST" action="{{ route('procurement.goods-receipts.cancel', $goodsReceipt) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-light-danger"
                        onclick="return confirm('Cancel this GRN?')">Cancel</button>
            </form>
        @endcan
        @can('update', $goodsReceipt)
            <a href="{{ route('procurement.goods-receipts.edit', $goodsReceipt) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan
        @can('delete', $goodsReceipt)
            <form method="POST" action="{{ route('procurement.goods-receipts.destroy', $goodsReceipt) }}"
                  onsubmit="return confirm('Delete this draft GRN?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-light-danger">Delete</button>
            </form>
        @endcan

        <a href="{{ route('procurement.goods-receipts.index') }}" class="btn btn-sm btn-light-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Vendor</dt><dd class="col-sm-8">{{ $goodsReceipt->vendor?->name }}</dd>
                    <dt class="col-sm-4">Warehouse</dt><dd class="col-sm-8">{{ $goodsReceipt->warehouse?->name }}</dd>
                    <dt class="col-sm-4">Delivery Note</dt><dd class="col-sm-8">{{ $goodsReceipt->supplier_delivery_note ?: '—' }}</dd>
                    <dt class="col-sm-4">Carrier</dt><dd class="col-sm-8">{{ $goodsReceipt->carrier ?: '—' }}</dd>
                    <dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $goodsReceipt->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Audit</h3>
            </div>
            <div class="card-body small text-muted">
                <div>Created: {{ $goodsReceipt->created_at?->format('d/m/Y H:i A') }}
                    @if($goodsReceipt->creator) · {{ $goodsReceipt->creator->name }}@endif
                </div>
                @if($goodsReceipt->posted_at)
                    <div>Posted: {{ $goodsReceipt->posted_at->format('d/m/Y H:i A') }}
                        @if($goodsReceipt->poster) · {{ $goodsReceipt->poster->name }}@endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Lines</h3>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead ">
                <tr>
                    <th>#</th><th>Item</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Accepted</th>
                    <th class="text-end">Rejected</th>
                    <th>Rejection Reason</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($goodsReceipt->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <code>{{ $line->item?->code }}</code>
                            {{ $line->item?->name }}
                            @if(!$line->brand?->name == null)
                                <span class="d-block small text-muted">Brand: {{ $line->brand?->name ?? "—" }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $line->received_quantity, 0) }} </td>
                        <td class="text-end">{{ number_format((float) $line->accepted_quantity, 0) }}</td>
                        <td class="text-end">{{ number_format((float) $line->rejected_quantity, 0) }}</td>
                        <td class="text-muted small">{{ $line->rejection_reason ?: '—' }}</td>
                        <td class="text-muted small">{{ $line->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
</x-default-layout>
