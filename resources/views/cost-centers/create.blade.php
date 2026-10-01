<x-default-layout>
@section('title', 'New Cost Center')
@section('toolbar-button')
        <a href="{{ route('cost-centers.index') }}" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i>Back</a>
@endsection

<form method="POST" action="{{ route('cost-centers.store') }}">
    @csrf
    @include('cost-centers._form')
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('cost-centers.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Create Cost Center</button>
    </div>
</form>
</x-default-layout>
