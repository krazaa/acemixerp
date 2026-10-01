<x-default-layout>
@section('title', 'Sales Return Request')
@section('toolbar-button')<a class="btn btn-sm btn-light" href="{{ route('sales.returns.index') }}">Back</a>@endsection
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<section class="bg-body p-6">
    <form method="GET" class="row g-3 mb-7">
        <div class="col-md-9"><label class="form-label" for="invoice_id">Original Sales Invoice</label><select id="invoice_id" name="invoice_id" class="form-select" data-control="select2" required><option value="">Select invoice</option>@foreach($invoices as $option)<option value="{{ $option->id }}" @selected($invoice?->id === $option->id)>{{ $option->number }} - {{ $option->customer?->name }}</option>@endforeach</select></div><div class="col-md-3 d-flex align-items-end"><button class="btn btn-light-primary">Load Invoice</button></div>
    </form>
    @if($invoice)
    <form method="POST" action="{{ route('sales.returns.store') }}">
        @csrf<input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">
        <div class="row g-4 mb-6">
            <div class="col-md-6"><label class="form-label" for="warehouse_id">Receiving Warehouse</label><select id="warehouse_id" name="warehouse_id" class="form-select" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(old('warehouse_id',$invoice->warehouse_id)==$warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="return_date">Request Date</label><input id="return_date" type="date" name="return_date" class="form-control" value="{{ old('return_date',today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></div>
            <div class="col-12"><label class="form-label" for="reason">Reason</label><textarea id="reason" name="reason" class="form-control" required minlength="3" maxlength="2000">{{ old('reason') }}</textarea></div>
        </div>
        <div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr><th>Return</th><th>Product</th><th>Invoiced Quantity</th><th>Requested Quantity</th></tr></thead><tbody>
        @foreach($invoice->lines as $line)
            @if($line->item?->item_type->tracksInventory())
            <tr><td><input class="form-check-input return-select" type="checkbox" aria-label="Return {{ $line->item->name }}" data-row="{{ $line->id }}" @checked(old('lines.'.$line->id.'.sales_invoice_line_id'))></td><td>{{ $line->item->name }}<input class="return-field-{{ $line->id }}" type="hidden" name="lines[{{ $line->id }}][sales_invoice_line_id]" value="{{ $line->id }}" @disabled(!old('lines.'.$line->id.'.sales_invoice_line_id'))></td><td>{{ $line->quantity }} {{ $line->unit?->code }}</td><td><input class="form-control return-field-{{ $line->id }}" type="number" name="lines[{{ $line->id }}][quantity]" aria-label="Requested quantity for {{ $line->item->name }}" min="0.0001" step="0.0001" max="{{ $line->quantity }}" value="{{ old('lines.'.$line->id.'.quantity') }}" required @disabled(!old('lines.'.$line->id.'.sales_invoice_line_id'))></td></tr>
            @endif
        @endforeach
        </tbody></table></div>
        <div class="text-end mt-5"><button class="btn btn-primary">Submit Return Request</button></div>
    </form>
    @endif
</section>
@push('scripts')<script>document.querySelectorAll('.return-select').forEach(checkbox => checkbox.addEventListener('change', () => document.querySelectorAll('.return-field-' + checkbox.dataset.row).forEach(input => input.disabled = !checkbox.checked)));</script>@endpush
</x-default-layout>
