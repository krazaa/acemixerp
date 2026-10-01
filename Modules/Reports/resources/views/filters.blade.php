<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">{{ $title }}</h1>
    <div class="d-flex gap-2 d-print-none">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print / PDF</button>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">All Reports</a>
    </div>
</div>
@if($errors->any())
    <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form method="GET" action="{{ url()->current() }}" class="row g-3 mb-4 d-print-none">
    <div class="col-md-3">
        <label for="as_of" class="form-label">As of</label>
        <input id="as_of" name="as_of" type="date" value="{{ $filters['as_of'] }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label for="currency" class="form-label">Currency code</label>
        <input id="currency" name="currency" type="text" value="{{ $filters['currency'] ?? '' }}" class="form-control" maxlength="3" pattern="[A-Z]{3}" aria-describedby="currency-help">
        <div id="currency-help" class="form-text">Leave blank for all currencies, or enter a code such as PKR.</div>
    </div>
    @isset($parties)
        <div class="col-md-4">
            <label for="party_id" class="form-label">{{ $partyLabel }}</label>
            <select id="party_id" name="party_id" class="form-select">
                <option value="">All {{ strtolower($partyLabel) }}s</option>
                @foreach($parties as $party)
                    <option value="{{ $party->id }}" @selected(($filters['party_id'] ?? '') == $party->id)>{{ $party->code }} — {{ $party->name }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="col-md-2 d-flex align-items-start gap-2 pt-md-4">
        <button class="btn btn-primary" type="submit">Run</button>
        <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>
<p class="text-muted">As of {{ $filters['as_of'] }}. Amounts are grouped by journal currency; no currency conversion is applied.</p>
