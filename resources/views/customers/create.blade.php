<x-default-layout>
@section('title', 'New Customer')
@section('toolbar-button')
    <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
@endsection


<form method="POST" action="{{ route('customers.store') }}">
    @csrf
    @include('customers._form')
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Create Customer</button>
    </div>
</form>
</x-default-layout>
