<x-default-layout>
@section('title', 'New Brand')
@section('toolbar-button')
        <a href="{{ route('inventory.brands.index') }}" class="btn btn-sm btn-light-secondary">
            <i class="fa fa-arrow-left"></i>Back</a>
@endsection
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('inventory.brands.store') }}">
    @csrf
    @include('inventory::brands._form')
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('inventory.brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Brand</button>
    </div>
</form>
</div></div>
</x-default-layout>
