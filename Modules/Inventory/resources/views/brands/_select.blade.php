<div class="col-md-2">
    <label class="form-label small mb-1" for="brand-{{ $index }}">Brand</label>
    <select id="brand-{{ $index }}" name="lines[{{ $index }}][brand_id]" class="form-select form-select-sm @error('lines.'.$index.'.brand_id') is-invalid @enderror">
        <option value="" @disabled(!empty($lockedBrandId))>No brand specified</option>
        @foreach($brands as $brand)
            <option value="{{ $brand->id }}" @disabled(!empty($lockedBrandId) && (int) $lockedBrandId !== $brand->id) @selected((int) ($selectedBrandId ?? 0) === $brand->id)>{{ $brand->name }}{{ $brand->status->value !== 'active' ? ' ('.$brand->status->label().')' : '' }}</option>
        @endforeach
    </select>

    @error('lines.'.$index.'.brand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
