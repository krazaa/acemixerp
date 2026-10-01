{{-- Identity --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="code">Code</label>
                <input id="code" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $item->code) }}" pattern="[A-Z0-9_-]+"
                       placeholder="Auto-generated if blank">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $item->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sku">SKU</label>
                <input id="sku" name="sku" class="form-control" value="{{ old('sku', $item->sku) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="barcode">Barcode</label>
                <input id="barcode" name="barcode" class="form-control" value="{{ old('barcode', $item->barcode) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="item_type">Item Type <span class="text-danger">*</span></label>
                <select id="item_type" name="item_type" class="form-select" required>
                    @foreach(\App\Enums\ItemType::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('item_type', $item->item_type?->value) === $t->value)>
                            {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="category_id">Category</label>
                <select id="category_id" name="category_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) old('category_id', $item->category_id) === $cat->id)>
                            {{ $cat->code }} — {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="unit_id">Unit of Measure</label>
                <select id="unit_id" name="unit_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" @selected((int) old('unit_id', $item->unit_id) === $u->id)>
                            {{ $u->code }} — {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" rows="2" class="form-control">{{ old('description', $item->description) }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- Pricing --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="cost_price">Cost Price</label>
                <input id="cost_price" name="cost_price" type="number" step="0.0001" min="0"
                       class="form-control" value="{{ old('cost_price', $item->cost_price ?? 0) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="selling_price">Selling Price</label>
                <input id="selling_price" name="selling_price" type="number" step="0.0001" min="0"
                       class="form-control" value="{{ old('selling_price', $item->selling_price ?? 0) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="minimum_selling_price">Min Selling Price</label>
                <input id="minimum_selling_price" name="minimum_selling_price" type="number" step="0.0001" min="0"
                       class="form-control" value="{{ old('minimum_selling_price', $item->minimum_selling_price) }}">
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch mt-4">
                    <input type="hidden" name="is_tax_exempt" value="0">
                    <input id="is_tax_exempt" name="is_tax_exempt" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_tax_exempt', $item->is_tax_exempt))>
                    <label class="form-check-label" for="is_tax_exempt">Tax Exempt</label>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Inventory --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="track_inventory" value="0">
                    <input id="track_inventory" name="track_inventory" type="checkbox" value="1"
                           class="form-check-input" @checked(old('track_inventory', $item->track_inventory ?? true))>
                    <label class="form-check-label" for="track_inventory">Track Inventory</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="allow_negative_stock" value="0">
                    <input id="allow_negative_stock" name="allow_negative_stock" type="checkbox" value="1"
                           class="form-check-input" @checked(old('allow_negative_stock', $item->allow_negative_stock))>
                    <label class="form-check-label" for="allow_negative_stock">Allow Negative Stock</label>
                </div>
            </div>
            <div class="col-md-6"></div>
            <div class="col-md-3">
                <label class="form-label" for="reorder_level">Reorder Level</label>
                <input id="reorder_level" name="reorder_level" type="number" step="0.0001" min="0"
                       class="form-control" value="{{ old('reorder_level', $item->reorder_level ?? 0) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="minimum_stock">Minimum Stock</label>
                <input id="minimum_stock" name="minimum_stock" type="number" step="0.0001" min="0"
                       class="form-control" value="{{ old('minimum_stock', $item->minimum_stock ?? 0) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="maximum_stock">Maximum Stock</label>
                <input id="maximum_stock" name="maximum_stock" type="number" step="0.0001" min="0"
                       class="form-control" value="{{ old('maximum_stock', $item->maximum_stock) }}">
            </div>
            <div class="col-md-3"></div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="track_batch" value="0">
                    <input id="track_batch" name="track_batch" type="checkbox" value="1"
                           class="form-check-input" @checked(old('track_batch', $item->track_batch))>
                    <label class="form-check-label" for="track_batch">Track Batch</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="track_serial" value="0">
                    <input id="track_serial" name="track_serial" type="checkbox" value="1"
                           class="form-check-input" @checked(old('track_serial', $item->track_serial))>
                    <label class="form-check-label" for="track_serial">Track Serial Number</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="track_expiry" value="0">
                    <input id="track_expiry" name="track_expiry" type="checkbox" value="1"
                           class="form-check-input" @checked(old('track_expiry', $item->track_expiry))>
                    <label class="form-check-label" for="track_expiry">Track Expiry</label>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Flags & Status --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Availability</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_sellable" value="0">
                    <input id="is_sellable" name="is_sellable" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_sellable', $item->is_sellable ?? true))>
                    <label class="form-check-label" for="is_sellable">Sellable</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_purchasable" value="0">
                    <input id="is_purchasable" name="is_purchasable" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_purchasable', $item->is_purchasable ?? true))>
                    <label class="form-check-label" for="is_purchasable">Purchasable</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $item->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="image">Image</label>
                <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp"
                       class="form-control @error('image') is-invalid @enderror">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror

                @if($item->image_path ?? false)
                    <img id="item-image-preview"
                        src="{{ route('items.image', $item) }}"
                        alt="Item image"
                        class="img-thumbnail mt-2"
                        style="max-height: 120px;">
                @else
                    <img id="item-image-preview" alt="" class="img-thumbnail mt-2" style="max-height: 120px; display:none;">
                @endif
            </div>
        </div>
    </div>
</div>
