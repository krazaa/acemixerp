{{-- @extends('layouts.app') --}}
<x-default-layout>
@section('title', 'Goods Receipts')
 @section('toolbar-button')
  @can('create', \Modules\Procurement\Models\GoodsReceipt::class)
        <a href="{{ route('procurement.goods-receipts.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Goods Receipt
        </a>
    @endcan
 @endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-2">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm"
               placeholder="GRN number or delivery note">
    </div>
    <div class="col-md-1">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Procurement\Enums\GoodsReceiptStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="vendor_id" class="form-select form-select-sm">
            <option value="">All vendors</option>
            @foreach($vendors as $v)
                <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('procurement.goods-receipts.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead >
                <tr>
                    <th>GRN</th>
                    <th>Received</th>
                    <th>PO</th>
                    <th>Vendor</th>
                    <th>Warehouse</th>
                    <th class="text-center">Lines</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($goodsReceipts as $grn)
                    <tr>
                        <td><code>{{ $grn->number }}</code></td>
                        <td>{{ $grn->received_date->format('Y-m-d') }}</td>
                        <td>
                            @if($grn->purchaseOrder)
                                <a href="{{ route('procurement.purchase-orders.show', $grn->purchase_order_id) }}">
                                    {{ $grn->purchaseOrder->number }}
                                </a>
                            @else — @endif
                        </td>
                        <td>{{ $grn->vendor?->name }}</td>
                        <td>{{ $grn->warehouse?->name }}</td>
                        <td class="text-center">{{ $grn->lines_count }}</td>
                        <td><span class="badge badge-{{ $grn->status->badgeClass() }}">{{ $grn->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('procurement.goods-receipts.print', $grn->id) }}"
   target="_blank"
   class="btn btn-sm btn-outline-dark"
   title="Print GRN">
    <i class="fas fa-print"></i>
</a>
                            <a href="{{ route('procurement.goods-receipts.show', $grn) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $grn)
                                <a href="{{ route('procurement.goods-receipts.edit', $grn) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No goods receipts.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $goodsReceipts->links() }}</div>
{{-- @endsection --}}
</x-default-layout>
