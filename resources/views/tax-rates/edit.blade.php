<x-default-layout>
@section('title', 'Edit Tax Rate')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 m-0">Edit Tax Rate</h1>
        <div class="text-muted small">{{ $taxRate->code }} — {{ $taxRate->name }}</div>
    </div>
    <a href="{{ route('tax-rates.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

<form method="POST" action="{{ route('tax-rates.update', $taxRate) }}" novalidate>
    @csrf
    @method('PUT')
    @include('tax-rates._form', ['taxRate' => $taxRate])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('tax-rates.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
</x-default-layout>
