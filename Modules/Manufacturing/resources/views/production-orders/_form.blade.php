@php($isEdit = $order->exists)

<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="bill_of_materials_id">Bill of Materials <span class="text-danger">*</span></label>
                <select id="bill_of_materials_id" name="bill_of_materials_id"
                        class="form-select form-select-sm @error('bill_of_materials_id') is-invalid @enderror" required>
                    <option value="">— Select BOM —</option>
                    @foreach($activeBoms as $b)
                        <option value="{{ $b->id }}"
                            @selected((int) old('bill_of_materials_id', $order->bill_of_materials_id ?? $bom?->id) === $b->id)>
                            {{ $b->code }} — {{ $b->name }} ({{ $b->product?->name }})
                        </option>
                    @endforeach
                </select>
                @error('bill_of_materials_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="product_id">Product <span class="text-danger">*</span></label>
                <select id="product_id" name="product_id"
                        class="form-select form-select-sm @error('product_id') is-invalid @enderror" required>
                    <option value="">— Select product —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected((int) old('product_id', $order->product_id ?? $bom?->product_id) === $p->id)>
                            {{ $p->code }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="planned_quantity">Planned Quantity <span class="text-danger">*</span></label>
                <input id="planned_quantity" name="planned_quantity" type="number" step="0.0001" min="0.0001"
                       class="form-control form-control-sm" value="{{ old('planned_quantity', $order->planned_quantity ?? 1) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="source_warehouse_id">Source Warehouse <span class="text-danger">*</span></label>
                <select id="source_warehouse_id" name="source_warehouse_id" class="form-select form-select-sm" required>
                    <option value="">— Select —</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected((int) old('source_warehouse_id', $order->source_warehouse_id) === $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
                <select id="type" name="type" class="form-select form-select-sm" required>
                    @foreach(\Modules\Manufacturing\Enums\ProductionOrderType::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('type', $order->type?->value ?? 'standard') === $t->value)>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="scheduled_start_date">Scheduled Start</label>
                <input id="scheduled_start_date" name="scheduled_start_date" type="date" class="form-control form-control-sm"
                       value="{{ old('scheduled_start_date', $order->scheduled_start_date?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="scheduled_end_date">Scheduled End</label>
                <input id="scheduled_end_date" name="scheduled_end_date" type="date" class="form-control form-control-sm"
                       value="{{ old('scheduled_end_date', $order->scheduled_end_date?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="sales_order_id">Sales Order (if make-to-order)</label>
                <input id="sales_order_id" name="sales_order_id" type="number" class="form-control form-control-sm"
                       value="{{ old('sales_order_id', $order->sales_order_id) }}"
                       placeholder="SO id">
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control form-control-sm" maxlength="2000">{{ old('notes', $order->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>
