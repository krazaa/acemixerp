<x-default-layout>
@section('title', 'Edit Bank Account')
@section('sub-title', $bankAccount->code . ' - ' . $bankAccount->name)

@section('toolbar-button')

    <a href="{{ route('bank-accounts.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection

<form method="POST" action="{{ route('bank-accounts.update', $bankAccount) }}" novalidate>
    @csrf
    @method('PUT')
    @include('bank-accounts._form', ['bankAccount' => $bankAccount])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('bank-accounts.show', $bankAccount) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
</x-default-layout>
