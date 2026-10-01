<x-default-layout>
@section('title', 'Edit Journal Entry')

 @section('toolbar-button')
        <a href="{{ route('journals.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection


<form method="POST" action="{{ route('journals.update', $entry) }}" novalidate>
    @csrf @method('PUT')
    @include('journals._form', ['entry' => $entry])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('journals.show', $entry) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
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
