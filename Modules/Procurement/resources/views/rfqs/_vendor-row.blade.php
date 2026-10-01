@php($i = $index)
@php($v = is_array($vendor) ? $vendor : [])
<div class="vendor-row d-flex align-items-center gap-2 border rounded p-2 mb-1 bg-light-subtle">
    <select name="vendor_ids[]" class="form-select form-select-sm" required>
        <option value="">— Select vendor —</option>
        @foreach($vendors as $vv)
            <option value="{{ $vv->id }}" @selected((int) ($v['id'] ?? 0) === $vv->id)>
                {{ $vv->code }} — {{ $vv->name }}
            </option>
        @endforeach
    </select>
    <button type="button" class="btn btn-sm btn-outline-danger"
            onclick="rfqRemoveVendor(this)">
        <i class="bi bi-x-lg"></i>
    </button>
</div>  
