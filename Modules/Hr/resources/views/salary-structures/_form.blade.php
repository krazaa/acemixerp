<div class="card mb-3">
    <div class="card-body row g-3">
        <div class="col-md-4">
            <label class="form-label" for="code">Code</label>
            <input id="code" name="code" value="{{ old('code', $salaryStructure->code) }}" class="form-control @error('code') is-invalid @enderror" required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label class="form-label" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $salaryStructure->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="basic_salary">Basic Salary</label>
            <input id="basic_salary" type="number" step="0.0001" min="0" name="basic_salary" value="{{ old('basic_salary', $salaryStructure->basic_salary) }}" class="form-control @error('basic_salary') is-invalid @enderror" required>
            @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="allowances">Allowances</label>
            <input id="allowances" type="number" step="0.0001" min="0" name="allowances" value="{{ old('allowances', $salaryStructure->allowances ?? 0) }}" class="form-control @error('allowances') is-invalid @enderror" required>
            @error('allowances')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="deductions">Deductions</label>
            <input id="deductions" type="number" step="0.0001" min="0" name="deductions" value="{{ old('deductions', $salaryStructure->deductions ?? 0) }}" class="form-control @error('deductions') is-invalid @enderror" required>
            @error('deductions')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check"><input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $salaryStructure->is_active))><label class="form-check-label" for="is_active">Active salary structure</label></div>
        </div>
    </div>
</div>
