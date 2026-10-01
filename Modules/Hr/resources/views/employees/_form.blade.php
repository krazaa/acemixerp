@php
    $currentContract = $employee->contracts?->firstWhere('is_current', true) ?? $employee->contracts?->first();
@endphp

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Employment Details</h3>
    </div>
    <div class="card-body row g-3">
        @if ($employee->exists)
            <div class="col-md-4"><label class="form-label">Employee Number</label><input class="form-control" value="{{ $employee->number }}" readonly></div>
        @endif
        <div class="col-md-6"><label class="form-label" for="first_name">First Name</label><input id="first_name" name="first_name" value="{{ old('first_name', $employee->first_name) }}" class="form-control @error('first_name') is-invalid @enderror" required>@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="last_name">Last Name</label><input id="last_name" name="last_name" value="{{ old('last_name', $employee->last_name) }}" class="form-control @error('last_name') is-invalid @enderror" required>@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="email">Work Email</label><input id="email" type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="phone">Work Phone</label><input id="phone" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label" for="department_id">Department</label><select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required><option value="">Select department</option>@foreach ($departments as $item)<option value="{{ $item->id }}" @selected((string) old('department_id', $employee->department_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select>@error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="designation_id">Designation</label><select id="designation_id" name="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required><option value="">Select designation</option>@foreach ($designations as $item)<option value="{{ $item->id }}" @selected((string) old('designation_id', $employee->designation_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select>@error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label" for="joining_date">Joining Date</label><input id="joining_date" type="date" name="joining_date" value="{{ old('joining_date', $employee->joining_date?->toDateString()) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label" for="hire_date">Hire Date</label><input id="hire_date" type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->toDateString()) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="contract_type">Contract</label><select id="contract_type" name="contract_type" class="form-select" required>@foreach (['probation' => 'Probation', 'permanent' => 'Permanent', 'fixed_term' => 'Fixed term'] as $value => $label)<option value="{{ $value }}" @selected(old('contract_type', $currentContract?->type ?? 'probation') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="contract_end_date">Contract End Date</label><input id="contract_end_date" type="date" name="contract_end_date" value="{{ old('contract_end_date', $currentContract?->end_date?->toDateString()) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="monthly_salary">Monthly Salary</label><input id="monthly_salary" type="number" step="0.0001" min="0" name="monthly_salary" value="{{ old('monthly_salary', $currentContract?->monthly_salary) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label" for="salary_structure_id">Salary Structure</label><select id="salary_structure_id" name="salary_structure_id" class="form-select"><option value="">None</option>@foreach ($salaryStructures as $item)<option value="{{ $item->id }}" @selected((string) old('salary_structure_id', $employee->salary_structure_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="leave_policy_id">Leave Policy</label><select id="leave_policy_id" name="leave_policy_id" class="form-select"><option value="">None</option>@foreach ($leavePolicies as $item)<option value="{{ $item->id }}" @selected((string) old('leave_policy_id', $employee->leave_policy_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Personal Details</h3>
    </div>
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label" for="personal_email">Personal Email</label><input id="personal_email" type="email" name="personal_email" value="{{ old('personal_email', $employee->personal_email) }}" class="form-control @error('personal_email') is-invalid @enderror">@error('personal_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="personal_phone">Personal Phone</label><input id="personal_phone" name="personal_phone" value="{{ old('personal_phone', $employee->personal_phone) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="date_of_birth">Date of Birth</label><input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth?->toDateString()) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="gender">Gender</label><select id="gender" name="gender" class="form-select"><option value="">Not specified</option>@foreach (['Female', 'Male', 'Non-binary', 'Prefer not to say'] as $value)<option value="{{ $value }}" @selected(old('gender', $employee->gender) === $value)>{{ $value }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="tax_number">Tax Number</label><input id="tax_number" name="tax_number" value="{{ old('tax_number', $employee->tax_number) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label" for="address_line1">Address</label><input id="address_line1" name="address_line1" value="{{ old('address_line1', $employee->address_line1) }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label" for="city">City</label><input id="city" name="city" value="{{ old('city', $employee->city) }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label" for="postal_code">Postal Code</label><input id="postal_code" name="postal_code" value="{{ old('postal_code', $employee->postal_code) }}" class="form-control"></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Bank and Emergency Contact</h3>
    </div>
    <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label" for="bank_name">Bank Name</label><input id="bank_name" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="bank_branch">Bank Branch</label><input id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $employee->bank_branch) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="bank_account_number">Bank Account Number</label><input id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" class="form-control" autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label" for="emergency_contact_name">Emergency Contact Name</label><input id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label" for="emergency_contact_phone">Emergency Contact Phone</label><input id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" class="form-control"></div>
    </div>
</div>
