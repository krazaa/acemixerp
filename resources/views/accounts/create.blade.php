@extends('layouts.app')
@section('title', 'New Account')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">New Account</h1>
    <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

<form method="POST" action="{{ route('accounts.store') }}" novalidate>
    @csrf
    @include('accounts._form', ['account' => $account, 'parents' => $parents, 'types' => $types, 'locked' => false])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Account</button>
    </div>
</form>
@endsection

@push('scripts')
@include('accounts._form-scripts')
@endpush
