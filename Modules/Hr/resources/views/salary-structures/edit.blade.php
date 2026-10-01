<x-default-layout>

@section('title', 'Edit - ' . $salaryStructure->name)

@section('toolbar-button')
    <a href="{{ route('salary-structures.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>Back</a>
@endsection


    <form method="POST" action="{{ route('salary-structures.update', $salaryStructure) }}">
        @csrf
        @method('PUT')
        @include('hr::salary-structures._form')
        <button class="btn btn-primary">Save Salary Structure</button>
        <a href="{{ route('salary-structures.show', $salaryStructure) }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</x-default-layout>
