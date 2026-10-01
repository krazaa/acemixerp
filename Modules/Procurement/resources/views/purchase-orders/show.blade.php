<x-default-layout>
@section('title', $purchaseOrder->number)
@section('sub-title')
 <div>
        <div class="text-muted small">
            Order {{ $purchaseOrder->order_date->format('d M Y') }}
            @if($purchaseOrder->expected_date)
                · Expected {{ $purchaseOrder->expected_date->format('d M Y') }}
            @endif
            · {{ $purchaseOrder->currency_code }}
              <span class="badge badge-light-{{ $purchaseOrder->status->badgeClass() }} ms-2">
                {{ $purchaseOrder->status->label() }}
            </span>
        </div>
    </div>
@endsection



@section('toolbar-button')
 @can('view', $purchaseOrder)
            <a href="{{ route('procurement.purchase-orders.print', ['purchaseOrder' => $purchaseOrder, 'download' => 1]) }}" class="btn btn-sm btn-light-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download PDF</a>
            <a href="{{ route('procurement.purchase-orders.print', $purchaseOrder) }}"
               class="btn btn-sm btn-light" target="_blank" rel="noopener">
                <i class="bi bi-printer" aria-hidden="true"></i> Print
            </a>
        @endcan
        @can('submit', $purchaseOrder)
            <form method="POST" action="{{ route('procurement.purchase-orders.submit', $purchaseOrder) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary">Submit</button>
            </form>
        @endcan
        @can('approve', $purchaseOrder)
            <form method="POST" action="{{ route('procurement.purchase-orders.approve', $purchaseOrder) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success">Approve</button>
            </form>
            <button class="btn btn-sm btn-light-danger" data-bs-toggle="modal" data-bs-target="#rejectPoModal">Reject</button>
        @endcan
        @can('issue', $purchaseOrder)
            <form method="POST" action="{{ route('procurement.purchase-orders.issue', $purchaseOrder) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary"
                        onclick="return confirm('Issue this PO to {{ $purchaseOrder->vendor->name }}? The PO becomes immutable after issuing.')">
                    Issue to Vendor
                </button>
            </form>
        @endcan
        @if($purchaseOrder->status->acceptsReceipt() && $purchaseOrder->openQuantity() !== '0.0000')
            @can('create', \Modules\Procurement\Models\GoodsReceipt::class)
                <a href="{{ route('procurement.goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}"
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-box-arrow-in-down"></i> Receive Goods
                </a>
            @endcan
        @endif
        @can('close', $purchaseOrder)
            @if(in_array($purchaseOrder->status->value, ['received', 'partially_received']))
                <form method="POST" action="{{ route('procurement.purchase-orders.close', $purchaseOrder) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-light"
                            onclick="return confirm('Close this PO?')">Close</button>
                </form>
            @endif
        @endcan
        @can('cancel', $purchaseOrder)
            @if($purchaseOrder->status->canCancel())
                <button class="btn btn-sm btn-light-danger" data-bs-toggle="modal" data-bs-target="#cancelPoModal">Cancel</button>
            @endif
        @endcan
        @can('update', $purchaseOrder)
            <a href="{{ route('procurement.purchase-orders.edit', $purchaseOrder) }}" class="btn btn-sm btn-light-primary">Edit</a>
        @endcan
    <a href="{{ route('procurement.purchase-orders.index',) }}" class="btn btn-sm btn-light">
        <i class="fas fa-arrow-left fa-sm"></i> Back </a>
@endsection



@if(session('status'))
    <div class="alert alert-success mb-6" role="status">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-6" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="row g-6 mb-6">
    <div class="col-md-8">
        <div class="card card-flush h-100">
            <div class="card-header align-items-center fw-bold fs-5">Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Vendor</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('vendors.show', $purchaseOrder->vendor_id) }}">
                            {{ $purchaseOrder->vendor?->name }}
                        </a>
                    </dd>
                    <dt class="col-sm-4">Department</dt><dd class="col-sm-8">{{ $purchaseOrder->department?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Cost Center</dt><dd class="col-sm-8">{{ $purchaseOrder->costCenter?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Warehouse</dt><dd class="col-sm-8">{{ $purchaseOrder->warehouse?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Payment Term</dt><dd class="col-sm-8">{{ $purchaseOrder->paymentTerm?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Reference</dt><dd class="col-sm-8">{{ $purchaseOrder->reference ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100">
            <div class="card-header align-items-center fw-bold fs-5">Totals</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span>{{ number_format((float) $purchaseOrder->subtotal, 4) }}</span></div>
                <div class="d-flex justify-content-between"><span>GST</span><span>{{ number_format((float) $purchaseOrder->tax_total, 4) }}</span></div>
                <div class="d-flex justify-content-between mt-3"><span>WHT amount</span><span class="text-danger">- {{ number_format((float) $purchaseOrder->lines->sum('wht_line_tax'), 4) }}</span></div>

                <hr class="my-5">
                <div class="d-flex justify-content-between fw-semibold fs-5"><span>Total ({{ $purchaseOrder->currency_code }})</span><span>{{ number_format((float) $purchaseOrder->total, 4) }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="card card-flush mb-6">
    <div class="card-header align-items-center fw-bold fs-5 d-flex justify-content-between">
        <span>Order items</span>
        <span class="badge badge-light-primary">{{ $purchaseOrder->lines->count() }} items</span>
    </div>
    <div class="card-body pt-0"><div class="table-responsive">
        <table class="table table-row-dashed align-middle gy-5 mb-0">
            <thead class="text-muted fs-7 text-nowrap">
                <tr>
                    <th>#</th><th>Item</th><th>Specification</th>
                    <th class="text-end">Ordered</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Open</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">GST %</th>
                    <th class="text-end">WHT %</th>
                    <th class="text-end">WHT amount</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><code>{{ $line->item?->code }}</code> {{ $line->item?->name }} <span class="d-block small text-muted">
                            @if($line->brand?->name){{ $line->brand?->name ?? "—" }}@endif
                            @if($line->origin?->name){{ $line->origin?->name ?? "—" }}
                            @endif
                        </span></td>
                        <td class="text-muted small">{{ $line->specification }}</td>
                        <td class="text-end">
                            {{ number_format((float) $line->quantity, 4) }}
                            {{ $line->unit?->name }}
                        </td>
                        <td class="text-end">{{ number_format((float) $line->received_quantity, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->openQuantity(), 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->unit_price, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->tax_rate, 2) }}%</td>
                        <td class="text-end text-nowrap">{{ rtrim(rtrim(number_format((float) $line->wht_tax_rate, 6), '0'), '.') }}%</td>
                        <td class="text-end text-nowrap">{{ number_format((float) $line->wht_line_tax, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div></div>
</div>

<div class="card card-flush mb-6">
    <div class="card-header align-items-center fw-bold fs-5 d-flex justify-content-between">
        <span>Goods Receipts</span>
        @can('create', \Modules\Procurement\Models\GoodsReceipt::class)
            @if($purchaseOrder->status->acceptsReceipt())
                <a href="{{ route('procurement.goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}"
                   class="btn btn-sm btn-light">
                    <i class="bi bi-plus-lg"></i> New Receipt
                </a>
            @endif
        @endcan
    </div>
    <div class="card-body pt-0"><div class="table-responsive">
        <table class="table table-row-dashed align-middle gy-5 mb-0">
            <thead class="text-muted fs-7 text-nowrap">
                <tr>
                    <th>GRN</th><th>Date</th><th>Warehouse</th>
                    <th>Delivery Note</th><th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrder->goodsReceipts as $grn)
                    <tr>
                        <td><code>{{ $grn->number }}</code></td>
                        <td>{{ $grn->received_date->format('d M Y') }}</td>
                        <td>{{ $grn->warehouse?->name ?? '—' }}</td>
                        <td>{{ $grn->supplier_delivery_note ?: '—' }}</td>
                        <td>
                            <span class="badge badge-light-{{ $grn->status->badgeClass() }}">
                                {{ $grn->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('procurement.goods-receipts.show', $grn) }}"
                               class="btn btn-sm btn-light">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-8">No goods received yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>

{{-- Reject modal --}}
@can('reject', $purchaseOrder)
    <div class="modal fade" id="rejectPoModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('procurement.purchase-orders.reject', $purchaseOrder) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Reject Purchase Order</h5></div>
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
@endcan

{{-- Cancel modal --}}
@can('cancel', $purchaseOrder)
    @if($purchaseOrder->status->canCancel())
        <div class="modal fade" id="cancelPoModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('procurement.purchase-orders.cancel', $purchaseOrder) }}" class="modal-content">
                    @csrf @method('PATCH')
                    <div class="modal-header"><h5 class="modal-title">Cancel Purchase Order</h5></div>
                    <div class="modal-body">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" maxlength="500"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep</button>
                        <button type="submit" class="btn btn-danger">Cancel PO</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan
</x-default-layout>
