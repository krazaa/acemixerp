@php($i = $index)
@php($l = is_array($line) ? $line : [])
<div class="line-row border rounded p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">Account <span class="text-danger">*</span></label>
            <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm" required>
                <option value="">— Select account —</option>
                @foreach($accounts as $a)
                    <option value="{{ $a->id }}" @selected((int) ($l['account_id'] ?? 0) === $a->id)>
                        {{ $a->code }} — {{ $a->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Debit</label>
            <input name="lines[{{ $i }}][debit]" type="number" step="0.0001" min="0"
                   class="form-control form-control-sm line-debit"
                   value="{{ $l['debit'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Credit</label>
            <input name="lines[{{ $i }}][credit]" type="number" step="0.0001" min="0"
                   class="form-control form-control-sm line-credit"
                   value="{{ $l['credit'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Memo</label>
            <input name="lines[{{ $i }}][memo]" class="form-control form-control-sm" maxlength="500"
                   value="{{ $l['memo'] ?? '' }}">
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="journalRemoveLine(this)">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div class="row g-2 mt-1">
        <div class="col-md-3">
            <label class="form-label small mb-1">Cost Center</label>
            <select name="lines[{{ $i }}][cost_center_id]" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach($costCenters as $c)
                    <option value="{{ $c->id }}" @selected((int) ($l['cost_center_id'] ?? 0) === $c->id)>{{ $c->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Department</label>
            <select name="lines[{{ $i }}][department_id]" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected((int) ($l['department_id'] ?? 0) === $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Customer</label>
            <select name="lines[{{ $i }}][customer_id]" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" @selected((int) ($l['customer_id'] ?? 0) === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Vendor</label>
            <select name="lines[{{ $i }}][vendor_id]" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}" @selected((int) ($l['vendor_id'] ?? 0) === $v->id)>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Employee</label>
            <select name="lines[{{ $i }}][employee_id]" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((int) ($l['employee_id'] ?? 0) === $employee->id)>{{ $employee->fullName() }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
