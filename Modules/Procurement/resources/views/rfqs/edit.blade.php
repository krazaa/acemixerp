<x-default-layout>
@section('title', 'Edit RFQ')
@section('sub-title')
     <div class="text-muted small">
            <code>{{ $rfq->number }}</code> — {{ $rfq->purpose }}
        </div>
@endsection


@section('toolbar-button')
        <a href="{{ route('procurement.rfqs.show', $rfq) }}" class="btn btn-outline-secondary btn-sm">
            View
        </a>

        <a href="{{ route('procurement.rfqs.index') }}" class="btn btn-sm btn-light-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection


@if(! $rfq->status->isEditable())
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        This RFQ is <strong>{{ $rfq->status->label() }}</strong> and is no longer editable.
        Only draft RFQs can be modified.
    </div>
@endif

<form method="POST" action="{{ route('procurement.rfqs.update', $rfq) }}" novalidate>
    @csrf
    @method('PUT')

    @include('procurement::rfqs._form', ['rfq' => $rfq])

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.rfqs.show', $rfq) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary"
                type="submit"
                @if(! $rfq->status->isEditable()) disabled @endif>
            Save Changes
        </button>
    </div>
</form>


@push('scripts')
@include('procurement::rfqs._form-scripts', [
    'rfq'     => $rfq,
    'items'   => $items,
    'units'   => $units,
    'vendors' => $vendors,
])
@endpush
</x-default-layout>
