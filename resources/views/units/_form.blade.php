<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
        <input id="code" name="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $unit->code) }}" pattern="[A-Z0-9_-]+" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $unit->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="quantity_precision">Quantity Precision <span class="text-danger">*</span></label>
        <input id="quantity_precision" name="quantity_precision" type="number" min="0" max="6"
               class="form-control" value="{{ old('quantity_precision', $unit->quantity_precision ?? 2) }}" required>
        <div class="form-text">Decimal places for quantities (e.g. 0 for pieces, 3 for kg).</div>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select" required>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(old('status', $unit->status?->value) === $s->value)>
                    {{ $s->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" rows="2" class="form-control">{{ old('description', $unit->description) }}</textarea>
    </div>
</div>
