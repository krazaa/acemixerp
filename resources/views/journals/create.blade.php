<x-default-layout>
@section('title', 'New Journal Entry')

 @section('toolbar-button')
        <a href="{{ route('journals.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection

<form method="POST" action="{{ route('journals.store') }}" novalidate>
    @csrf
    @include('journals._form', ['entry' => $entry])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('journals.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>


@push('scripts')
@include('journals._form-scripts', [
    'entry' => $entry,
    'accounts' => $accounts, 'costCenters' => $costCenters,
    'departments' => $departments, 'customers' => $customers, 'vendors' => $vendors,
])
@endpush
</x-default-layout>
