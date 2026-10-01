<x-default-layout>
@section('title', 'New Tax Rate')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">New Tax Rate</h1>
    <a href="{{ route('tax-rates.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

<form method="POST" action="{{ route('tax-rates.store') }}" novalidate>
    @csrf
    @include('tax-rates._form', ['taxRate' => $taxRate])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('tax-rates.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Tax Rate</button>
    </div>
</form>
</x-default-layout>
