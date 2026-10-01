<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
        <input id="code" name="code"
               class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $costCenter->code) }}"
               pattern="[A-Z0-9_-]+" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
        <input id="name" name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $costCenter->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="department_id">Department</label>
        <select id="department_id" name="department_id" class="form-select">
            <option value="">— None —</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    @selected((int) old('department_id', $costCenter->department_id) === $d->id)>
                    {{ $d->code }} — {{ $d->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select" required>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}"
                    @selected(old('status', $costCenter->status?->value) === $s->value)>
                    {{ $s->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" rows="2"
                  class="form-control">{{ old('description', $costCenter->description) }}</textarea>
    </div>
</div>
