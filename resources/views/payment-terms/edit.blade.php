<x-default-layout>
@section('title', 'Edit Payment Term')
@section('sub-title')
<div class="text-muted small">{{ $paymentTerm->code }} — {{ $paymentTerm->name }}</div>
@endsection

 @section('toolbar-button')
        <a href="{{ route('payment-terms.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection


<form method="POST" action="{{ route('payment-terms.update', $paymentTerm) }}" novalidate>
    @csrf
    @method('PUT')
    @include('payment-terms._form', ['paymentTerm' => $paymentTerm])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('payment-terms.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
</x-default-layout>
