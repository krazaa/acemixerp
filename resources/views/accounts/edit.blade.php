<x-default-layout>
@section('title', 'Edit Account - ' . $account->code . ' - ' . $account->name)

@section('toolbar-button')
     @can('coa.manage')
        <a href="{{ route('system-accounts.edit') }}" class="btn btn-sm btn-light">System Mappings</a>
    @endcan
    @can('create', \App\Models\Account::class)
        <a href="{{ route('accounts.create') }}" class="btn btn-sm btn-primary"><span aria-hidden="true">+</span> New Account</a>
    @endcan

  <a href="{{ route('accounts.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


<form method="POST" action="{{ route('accounts.update', $account) }}" novalidate>
    @csrf @method('PUT')
    @include('accounts._form', ['account' => $account, 'parents' => $parents, 'types' => $types, 'locked' => $locked])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>


@push('scripts')
@include('accounts._form-scripts')
@endpush
</x-default-layout>
