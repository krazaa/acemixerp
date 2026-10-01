@extends('layouts.app')
@section('title', 'New Permission')
@section('content')
<h1 class="h3 mb-3">New Permission</h1>
<form method="POST" action="{{ route('permissions.store') }}">
    @csrf
    @include('permissions._form', ['permission' => null])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Create Permission</button>
    </div>
</form>
@endsection
