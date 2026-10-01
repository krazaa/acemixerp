<x-default-layout>
@section('title', 'Edit Customer - '. $customer->name)
@section('toolbar-button')
    <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
@endsection

<form method="POST" action="{{ route('customers.update', $customer) }}">
    @csrf @method('PUT')
    @include('customers._form')
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Save Changes</button>
    </div>
</form>
</x-default-layout>
