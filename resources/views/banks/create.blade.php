<x-default-layout>
@section('title', 'New Bank')

@section('toolbar-button')
   <a href="{{ route('banks.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back to list
    </a>
@endsection

<form method="POST" action="{{ route('banks.store') }}" novalidate>
    @csrf
    @include('banks._form', ['bank' => $bank])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('banks.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Bank</button>
    </div>
</form>


@push('scripts')
@include('banks._form-scripts')
@endpush
</x-default-layout>
