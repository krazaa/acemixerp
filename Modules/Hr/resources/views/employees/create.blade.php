<x-default-layout>

@section('title', 'New Employee')


    <form method="POST" action="{{ route('employees.store') }}">
        @csrf
        @include('hr::employees._form')
        <button class="btn btn-primary">Create Onboarding Employee</button>
    </form>
</x-default-layout>

