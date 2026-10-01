<x-default-layout>
@section('title', 'New Stock Count')
<h1 class="h3 mb-3">New Stock Count</h1>
<div class="alert alert-info">
    A physical count snapshots the current system quantities. Warehouse staff count each item and
    record the actual quantity. Variances become a stock adjustment on posting.
</div>
<form method="POST" action="{{ route('inventory.counts.store') }}">
    @csrf
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="warehouse_id">Warehouse <span class="text-danger">*</span></label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                        <option value="">— Select warehouse —</option>
                        @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(old('warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="count_date">Count Date <span class="text-danger">*</span></label>
                    <input id="count_date" name="count_date" type="date" class="form-control"
                           value="{{ old('count_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="scope">Scope</label>
                    <input id="scope" name="scope" class="form-control"
                           value="{{ old('scope') }}" placeholder="e.g. All items" maxlength="500">
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('inventory.counts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Count</button>
    </div>
</form>
</x-default-layout>
