<x-default-layout>

@section('title', 'New Salary Structure')

@section('toolbar-button')
    <a href="{{ route('salary-structures.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>Back</a>
@endsection

    <form method="POST" action="{{ route('salary-structures.store') }}">
        @csrf
        @include('hr::salary-structures._form')
        <button class="btn btn-primary">Create Salary Structure</button>
        <a href="{{ route('salary-structures.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</x-default-layout>
