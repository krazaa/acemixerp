<div class="col-md-1">
    <label class="form-label small mb-1" for="origin-{{ $index }}">Origins</label>
    <select id="origin-{{ $index }}" name="lines[{{ $index }}][origin_id]" class="form-select form-select-sm @error('lines.'.$index.'.origin_id') is-invalid @enderror">
        <option value="" @disabled(!empty($lockedOriginId))>No origin specified</option>
        @foreach($origins as $origin)
            <option value="{{ $origin->id }}" @disabled(!empty($lockedOriginId) && (int) $lockedOriginId !== $origin->id) @selected((int) ($selectedOriginId ?? 0) === $origin->id)>{{ $origin->name }}{{ $origin->status->value !== 'active' ? ' ('.$origin->status->label().')' : '' }}</option>
        @endforeach
    </select>

    @error('lines.'.$index.'.origin_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
