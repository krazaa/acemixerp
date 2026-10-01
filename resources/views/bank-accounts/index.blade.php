<x-default-layout>
@section('title', 'Bank Accounts')
@section('toolbar-button')
    @can('viewAny', \App\Models\BankReconciliation::class)
            <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left-right"></i> Reconciliation
            </a>
        @endcan
        @can('create', \App\Models\BankAccount::class)
            <a href="{{ route('bank-accounts.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> New Bank Account
            </a>
        @endcan
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm"
               placeholder="Search name, code, or account number">
    </div>
    <div class="col-md-3">
        <select name="bank_id" class="form-select form-select-sm">
            <option value="">All banks</option>
            @foreach($banks as $b)
                <option value="{{ $b->id }}" @selected(request('bank_id') == $b->id)>
                    {{ $b->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('bank-accounts.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
        <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Code</th>
                    <th>Name</th>
                    <th>Account #</th>
                    <th>GL Account</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bankAccounts as $a)
                    <tr>
                        <td>
                            <code>{{ $a->code }}</code>
                            @if($a->is_default)
                                <span class="badge badge-primary ms-1">Default</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('bank-accounts.show', $a) }}" class="text-decoration-none fw-semibold">
                                {{ $a->name }}
                            </a>
                        </td>

                        <td><code>{{ $a->account_number }}</code></td>
                        <td>
                            @if($a->glAccount)
                                <code>{{ $a->glAccount->code }}</code>
                                <span class="text-muted small">{{ $a->glAccount->name }}</span>
                            @else — @endif
                        </td>
                        <td><span class="badge text-bg-light border">{{ $a->account_type?->label() ?? '—' }}</span></td>
                        <td>
                            <span class="badge badge-{{ $a->status->badgeClass() }}">
                                {{ $a->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('view', $a)
                                <a href="{{ route('bank-accounts.show', $a) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @endcan
                            @can('update', $a)
                                <a href="{{ route('bank-accounts.edit', $a) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('makeDefault', $a)
                                @if(! $a->is_default && $a->status === \App\Enums\RecordStatus::Active)
                                    <form method="POST" action="{{ route('bank-accounts.make-default', $a) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary">Default</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No bank accounts yet.
                            @can('create', \App\Models\BankAccount::class)
                                <a href="{{ route('bank-accounts.create') }}">Create the first one</a>.
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $bankAccounts->links() }}</div>
</x-default-layout>
