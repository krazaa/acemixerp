<x-default-layout>
@section('title', 'New Payment Term')
 @section('toolbar-button')
        <a href="{{ route('payment-terms.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection

<form method="POST" action="{{ route('payment-terms.store') }}" novalidate>
    @csrf
    @include('payment-terms._form', ['paymentTerm' => $paymentTerm])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('payment-terms.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Payment Term</button>
    </div>
</form>
</x-default-layout>
