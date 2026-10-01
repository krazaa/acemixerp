<x-default-layout>
@section('title', 'New Bank Account')

 @section('toolbar-button')
        <a href="{{ route('bank-accounts.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection
<div class="d-flex justify-content-between align-items-center mb-3">


</div>

<form method="POST" action="{{ route('bank-accounts.store') }}" novalidate>
    @csrf
    @include('bank-accounts._form', ['bankAccount' => $bankAccount])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('bank-accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Bank Account</button>
    </div>
</form>
</x-default-layout>
