<x-default-layout>
@section('title', 'New Vendor Invoice')

@section('toolbar-button')
    <a href="{{ route('expense.vendor-invoices.index') }}" class="btn btn-outline-secondary">Back</a>
@endsection


<form method="POST" action="{{ route('expense.vendor-invoices.store') }}">@csrf
<div class="card border-0 shadow-sm"><div class="card-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Vendor *</label><select name="vendor_id" class="form-select" required><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->code }} — {{ $vendor->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Vendor Invoice # *</label><input name="vendor_invoice_number" class="form-control" required></div><div class="col-md-4"><label class="form-label">Invoice Date *</label><input type="date" name="invoice_date" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Due Date *</label><input type="date" name="due_date" value="{{ now()->addDays(30)->toDateString() }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Billing Month *</label><input type="month" name="billing_month" value="{{ now()->format('Y-m') }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Cargo/Freight Expense Account *</label><select name="debit_account_id" class="form-select" required><option value="">Select account</option>@foreach($expenseAccounts as $account)<option value="{{ $account->id }}" @selected(old('debit_account_id') == $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Billing Amount *</label>
    <input type="number" name="subtotal" step="0.01" min="0" class="form-control" required>
</div>

<div class="col-md-3">
    <label class="form-label">GST (Input Tax) % *</label>
    <input type="number" name="tax_rate" value="16" step="0.01" min="0" max="100" class="form-control" required>
</div>
<div class="col-md-3">
    <label class="form-label">Withholding Tax (WHT) % *</label>
    <div class="form-text">Calculated on billing amount plus GST.</div>
    <input type="number" name="wht_rate" value="6" step="0.01" min="0" max="100" class="form-control" required>
</div>
<div class="col-12"><label class="form-label">Notes</label>
    <textarea name="notes" class="form-control"></textarea></div></div></div><div class="card-footer text-end"><button class="btn btn-primary">Save Draft</button></div></div></form>

@push('scripts')
<!-- Place the first <script> tag in your HTML's <head> -->
<script src="https://cdn.tiny.cloud/1/2xolflh0suxbu1ss46u87uypkft0jqnq7vc687uki7lfa4xd/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>

<!-- Place the following <script> and <textarea> tags your HTML's <body> -->
<script>
  tinymce.init({
    selector: 'textarea',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
  });
</script>
@endpush

</x-default-layout>
