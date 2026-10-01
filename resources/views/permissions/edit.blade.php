@extends('layouts.app')
@section('title', 'Edit Permission')
@section('content')
<h1 class="h3 mb-3">Edit Permission — <code>{{ $permission->name }}</code></h1>
<form method="POST" action="{{ route('permissions.update', $permission) }}">
    @csrf @method('PUT')
    @include('permissions._form', ['permission' => $permission])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Save Changes</button>
    </div>
</form>
@endsection
