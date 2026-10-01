@php($isEdit = $bom->exists)
@php($existingLines = old('lines', $bom->lines?->toArray() ?? []))
@if(empty($existingLines))
    @php($existingLines = [['component_id' => null, 'quantity' => '', 'scrap_percent' => '']])
@endif

<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                <input id="code" name="code" class="form-control form-control-sm @error('code') is-invalid @enderror"
                       value="{{ old('code', $bom->code) }}" pattern="[A-Z0-9_-]+" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                       value="{{ old('name', $bom->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="product_id">Finished Product <span class="text-danger">*</span></label>
                <select id="product_id" name="product_id" class="form-select form-select-sm" data-control="select2" required>
                    <option value="">— Select product —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected((int) old('product_id', $bom->product_id) === $p->id)>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="revision">Revision</label>
                <input id="revision" name="revision" type="number" min="1"
                       class="form-control form-control-sm" value="{{ old('revision', $bom->revision ?? 1) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="output_quantity">Output Quantity <span class="text-danger">*</span></label>
                <input id="output_quantity" name="output_quantity" type="number" step="0.0001" min="0.0001"
                       class="form-control form-control-sm" value="{{ old('output_quantity', $bom->output_quantity ?? 1) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="output_unit_id">Output Unit</label>
                <select id="output_unit_id" name="output_unit_id" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" @selected((int) old('output_unit_id', $bom->output_unit_id) === $u->id)>{{ $u->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="labour_cost">Labour Cost / Run</label>
                <input id="labour_cost" name="labour_cost" type="number" step="0.0001" min="0"
                       class="form-control form-control-sm" value="{{ old('labour_cost', $bom->labour_cost ?? 0) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="overhead_cost">Overhead Cost / Run</label>
                <input id="overhead_cost" name="overhead_cost" type="number" step="0.0001" min="0"
                       class="form-control form-control-sm" value="{{ old('overhead_cost', $bom->overhead_cost ?? 0) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select form-select-sm" required>
                    @foreach(\Modules\Manufacturing\Enums\BomStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $bom->status?->value) === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control form-control-sm" maxlength="2000">{{ old('notes', $bom->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Ingredients</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bomAddLine()">
            <i class="bi bi-plus-lg"></i> Add Ingredient
        </button>
    </div>
    <div class="card-body" id="bom-lines-container">
        @foreach($existingLines as $i => $line)
            @include('manufacturing::boms._line-row', [
                'index' => $i, 'line' => $line, 'components' => $components, 'units' => $units,
            ])
        @endforeach
    </div>
</div>
