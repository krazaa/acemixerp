<x-default-layout>

    @section('title', 'Edit Bank - ' . $bank->name)

    @section('toolbar-button')
        <a href="{{ route('banks.show', $bank) }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-eye fa-sm"></i> View
        </a>

        <a href="{{ route('banks.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
    @endsection

    <form method="POST" action="{{ route('banks.update', $bank) }}" novalidate>
        @csrf
        @method('PUT')

        @include('banks._form', ['bank' => $bank])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('banks.show', $bank) }}" class="btn btn-outline-secondary">
                Cancel
            </a>

            <button class="btn btn-primary" type="submit">
                Save Changes
            </button>
        </div>
    </form>

    @push('scripts')
        @include('banks._form-scripts')
    @endpush

</x-default-layout>
