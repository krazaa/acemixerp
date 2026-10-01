<x-default-layout>
@section('title', 'New Financial Year')

@section('toolbar-button')

    <a href="{{ route('financial-years.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

<form method="POST" action="{{ route('financial-years.store') }}" novalidate>
    @csrf

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', 'FY ' . now()->year) }}" required maxlength="32">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="start_date">Start Date <span class="text-danger">*</span></label>
                    <input id="start_date" name="start_date" type="date"
                           class="form-control @error('start_date') is-invalid @enderror"
                           value="{{ old('start_date', now()->startOfYear()->toDateString()) }}" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="end_date">End Date <span class="text-danger">*</span></label>
                    <input id="end_date" name="end_date" type="date"
                           class="form-control @error('end_date') is-invalid @enderror"
                           value="{{ old('end_date', now()->endOfYear()->toDateString()) }}" required>
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="period_count">Period Count</label>
                    <input id="period_count" name="period_count" type="number" min="1" max="24"
                           class="form-control" value="{{ old('period_count', 12) }}">
                    <div class="form-text">Monthly periods are generated automatically.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('financial-years.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Financial Year</button>
    </div>
</form>
</x-default-layout>
