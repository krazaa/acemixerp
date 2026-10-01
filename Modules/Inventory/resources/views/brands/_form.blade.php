<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label">Brand name *</label>
        <input id="name" name="name" maxlength="128" required value="{{ old('name', $brand->name) }}" class="form-control @error('name') is-invalid @enderror">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="status" class="form-label">Status *</label>
        <select id="status" name="status" required class="form-select @error('status') is-invalid @enderror">
            @foreach(\App\Enums\RecordStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $brand->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" maxlength="500" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $brand->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
