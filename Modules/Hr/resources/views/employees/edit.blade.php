<x-default-layout>
@section('title', 'Edit - ' .$employee->fullName())

@section('sub-title')
    <code>{{ $employee->number }}</code> · {{ $employee->status }}
@endsection

    <form method="POST" action="{{ route('employees.update', $employee) }}">
        @csrf
        @method('PUT')
        @include('hr::employees._form')
        <button class="btn btn-primary">Save Employee</button>
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</x-default-layout>
