@php
    $entryLines = old('lines');
    if ($entryLines === null) {
        $entryLines = $claim?->lines->map(fn ($line) => ['expense_date' => $line->expense_date?->format('Y-m-d'), 'expense_account_id' => $line->expense_account_id, 'reference' => $line->reference, 'amount' => $line->amount])->all();
    }
    if (empty($entryLines)) {
        $entryLines = [['expense_date' => $claim?->expense_date?->format('Y-m-d') ?? now()->toDateString(), 'expense_account_id' => null, 'reference' => null, 'amount' => $claim?->amount]];
    }
@endphp
<div class="card border-0 shadow-sm mb-3">

    <div class="card-body"><div class="row g-3">
        @if($claim)
            <div class="col-md-6"><label class="form-label">Employee</label><input class="form-control" value="{{ $claim->employee?->number }} — {{ $claim->employee?->fullName() }}" readonly><div class="form-text">{{ $claim->employee?->designation?->name ?? 'No designation assigned' }}</div></div>
        @else
            <div class="col-md-6"><label class="form-label" for="employee_id">Employee *</label><select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int) old('employee_id') === $employee->id)>{{ $employee->number }} — {{ $employee->fullName() }}</option>@endforeach</select>@error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        @endif
        <div class="col-md-6"><label class="form-label" for="department_id">Department</label><select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror"><option value="">Employee department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((int) old('department_id', $claim?->department_id) === $department->id)>{{ $department->name }}</option>@endforeach</select>@error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><label class="form-label" for="notes">Notes</label><textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional overall claim note">{{ old('notes', $claim?->description) }}</textarea>@error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div></div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header gap-2">
                <h3 class="card-title">Expense Entries <span class="text-muted small ms-2">Select an Expense Account for each entry.</span></h3>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-outline-primary btn-sm" id="add-expense-line">+ Add Entry</button>
        </div>
    </div>

    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
        <thead>
            <tr class="fw-bold fs-6 text-gray-800">
            <th scope="col" style="width:5%">#</th><th scope="col">Date</th><th scope="col">Expense Account</th><th scope="col">Reference</th><th scope="col" class="text-end">Amount (PKR)</th><th scope="col"></th></tr></thead>
        <tbody id="expense-lines-body">
            @foreach($entryLines as $index => $line)
                <tr class="expense-line">
                    <td class="line-number">{{ $loop->iteration }}</td>
                    <td><input type="date" name="lines[{{ $index }}][expense_date]" value="{{ $line['expense_date'] }}" class="form-control @error("lines.$index.expense_date") is-invalid @enderror" required></td>
                    <td><select name="lines[{{ $index }}][expense_account_id]" class="form-select @error("lines.$index.expense_account_id") is-invalid @enderror" required><option value="">Select expense account</option>@foreach($expenseAccounts as $account)<option value="{{ $account->id }}" @selected((int) ($line['expense_account_id'] ?? 0) === $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></td>
                    <td><input type="text" name="lines[{{ $index }}][reference]" value="{{ $line['reference'] ?? '' }}" maxlength="255" class="form-control" placeholder="Meter reading, invoice or trip ref."></td>
                    <td><input type="number" name="lines[{{ $index }}][amount]" value="{{ $line['amount'] ?? '' }}" min="0.01" step="0.01" inputmode="decimal" class="form-control text-end line-amount @error("lines.$index.amount") is-invalid @enderror" required></td>
                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-expense-line" aria-label="Remove entry">×</button></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="4" class="text-end">Claim Total</th>
                <th class="text-end" id="expense-claim-total">PKR 0.00</th><th></th></tr></tfoot>
    </table></div>
    </div>
    @error('lines')<div class="text-danger small px-3 pb-3">{{ $message }}</div>@enderror
</div>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
        <label class="form-label" for="evidence_text">Expanse Details</label>
        <textarea id="evidence_text" name="evidence_text" rows="5" maxlength="10000" class="form-control @error('evidence_text') is-invalid @enderror">{{ old('evidence_text', $claim?->evidence_text) }}</textarea>
        @error('evidence_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<template id="expense-line-template"><tr class="expense-line"><td class="line-number"></td><td><input type="date" name="lines[__INDEX__][expense_date]" value="{{ now()->toDateString() }}" class="form-control" required></td><td><select name="lines[__INDEX__][expense_account_id]" class="form-select" required><option value="">Select expense account</option>@foreach($expenseAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></td><td><input type="text" name="lines[__INDEX__][reference]" maxlength="255" class="form-control" placeholder="Meter reading, invoice or trip ref."></td><td><input type="number" name="lines[__INDEX__][amount]" min="0.01" step="0.01" inputmode="decimal" class="form-control text-end line-amount" required></td><td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-expense-line" aria-label="Remove entry">×</button></td></tr></template>
@push('scripts')
<script src="{{ asset('assets/plugins/custom/tinymce/tinymce.bundle.js') }}"></script>
<script>
(() => {
    if (!window.tinymce) {
        return;
    }
    const field = document.getElementById('evidence_text');
    const initialText = field.value;
    tinymce.init({
        target: field,
        base_url: @json(asset('assets/plugins/custom/tinymce')),
        suffix: '.min',
        height: 300,
        menubar: false,
        plugins: 'paste anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | formatselect fontselect fontsizeselect | bold italic underline strikethrough | table | align lineheight | numlist bullist indent outdent',
        paste_as_text: false,
        paste_data_images: false,
        paste_webkit_styles: 'all',
        paste_remove_styles_if_webkit: false,
        setup: function (editor) {
            editor.on('init', function () {
                @if(strip_tags(old('evidence_text', $claim?->evidence_text) ?? '') === (old('evidence_text', $claim?->evidence_text) ?? ''))
                    const content = document.createElement('div');
                    content.textContent = initialText;
                    editor.setContent(content.innerHTML.replace(/\r\n|\r|\n/g, '<br>'));
                @endif
            });
            editor.on('change input undo redo', function () {
                editor.save();
            });
            field.form.addEventListener('submit', function () {
                editor.save();
                field.value = editor.getContent({ format: 'html' });
            });
        }
    });
})();
</script>
<script>
(() => {
    const body = document.getElementById('expense-lines-body');
    const template = document.getElementById('expense-line-template');
    const total = document.getElementById('expense-claim-total');
    const initializeSelects = () => {
        if (!window.jQuery?.fn.select2) {
            return;
        }
        document.querySelectorAll('#employee_id, #department_id, #expense-lines-body select[name$="[expense_account_id]"]').forEach((select) => {
            if (select.classList.contains('select2-hidden-accessible')) {
                return;
            }
            window.jQuery(select).select2({
                width: '100%',
                placeholder: select.options[0].text,
                allowClear: !select.required,
                minimumResultsForSearch: 0,
            });
        });
    };
    let nextIndex = Math.max(-1, ...[...body.querySelectorAll('[name$="[amount]"]')].map((input) => Number.parseInt(input.name.match(/lines\[(\d+)\]/)?.[1], 10) || 0)) + 1;
    const refresh = () => {
        const rows = [...body.querySelectorAll('.expense-line')];
        rows.forEach((row, index) => { row.querySelector('.line-number').textContent = index + 1; row.querySelector('.remove-expense-line').disabled = rows.length === 1; });
        const amount = rows.reduce((sum, row) => sum + (Number.parseFloat(row.querySelector('.line-amount').value) || 0), 0);
        total.textContent = 'PKR ' + amount.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    document.getElementById('add-expense-line').addEventListener('click', () => { body.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex)); nextIndex += 1; initializeSelects(); refresh(); });
    body.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-expense-line');
        if (button && body.querySelectorAll('.expense-line').length > 1) {
            const row = button.closest('.expense-line');
            if (window.jQuery?.fn.select2) {
                window.jQuery(row).find('.select2-hidden-accessible').select2('destroy');
            }
            row.remove();
            refresh();
        }
    });
    body.addEventListener('input', (event) => { if (event.target.classList.contains('line-amount')) { refresh(); } });
    refresh();
    initializeSelects();
})();
</script>
@endpush
