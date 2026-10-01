@php($isEdit = $entry->exists)
@php($existingLines = old('lines', $entry->lines?->map->toArray()->all() ?? []))
@if(empty($existingLines))
    @php($existingLines = [
        ['account_id' => null, 'debit' => '', 'credit' => ''],
        ['account_id' => null, 'debit' => '', 'credit' => ''],
    ])
@endif

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="entry_date">Entry Date <span class="text-danger">*</span></label>
                <input id="entry_date" name="entry_date" type="date"
                       class="form-control @error('entry_date') is-invalid @enderror"
                       value="{{ old('entry_date', $entry->entry_date?->toDateString() ?? now()->toDateString()) }}" required>
                @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="reference">Reference</label>
                <input id="reference" name="reference" class="form-control"
                       value="{{ old('reference', $entry->reference) }}" maxlength="64">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="currency_code">Currency</label>
                <input id="currency_code" name="currency_code" maxlength="3"
                       class="form-control" value="{{ old('currency_code', $entry->currency_code) }}">
            </div>
            <div class="col-md-3"></div>
            <div class="col-12">
                <label class="form-label" for="description">Description <span class="text-danger">*</span></label>
                <input id="description" name="description"
                       class="form-control @error('description') is-invalid @enderror"
                       value="{{ old('description', $entry->description) }}" required maxlength="500">
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Lines --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Lines</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="journalAddLine()">
            <i class="bi bi-plus-lg"></i> Add Line
        </button>
    </div>
    <div class="card-body" id="journal-lines-container">
        @foreach($existingLines as $i => $line)
            @include('journals._line-row', ['index' => $i, 'line' => $line])
        @endforeach
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-4">
        <div>Total Debit: <strong id="total-debit">0.0000</strong></div>
        <div>Total Credit: <strong id="total-credit">0.0000</strong></div>
        <div>Difference: <strong id="total-difference">0.0000</strong></div>
    </div>
</div>

{{-- Notes --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Notes</div>
    <div class="card-body">
        <textarea name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $entry->notes) }}</textarea>
    </div>
</div>
