<x-default-layout>
@section('title', 'Chart of Accounts')
@section('toolbar-button')
     @can('coa.manage')
        <a href="{{ route('system-accounts.edit') }}" class="btn btn-sm btn-light">System Mappings</a>
    @endcan
    @can('create', \App\Models\Account::class)
        <a href="{{ route('accounts.create') }}" class="btn btn-sm btn-primary"><span aria-hidden="true">+</span> New Account</a>
    @endcan

  <a href="{{ route('manufacturing.production-orders.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

@section('sub-title', 'Browse and manage your accounts by type.')

<style>
    .accounts-page .card { border: 1px solid var(--bs-border-color, #e4e6ef); border-radius: 1rem; overflow: hidden; }
    .accounts-page .account-types { display: flex; gap: .65rem; flex-wrap: wrap; }
    .accounts-page .account-types a { border: 1px solid var(--bs-border-color, #e4e6ef); }
    .accounts-page .account-types a[aria-current="page"] { border-color: var(--bs-primary, #009ef7); }
    .accounts-page .account-table { min-width: 850px; }
    .accounts-page .account-table > :not(caption) > * > * { padding: 1.1rem 1.5rem; }
    .accounts-page .account-table thead th { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; }
    .accounts-page .account-description { max-width: 30rem; overflow-wrap: anywhere; }
    .accounts-page .account-code { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .accounts-page .account-section { border-top: 3px solid var(--bs-primary, #009ef7); }
</style>

<div class="accounts-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mb-6">
        <div>
            <div class="text-muted">{{ number_format($accounts->total()) }} accounts match your filters</div>
        </div>
        <div class="d-flex flex-wrap gap-2">

        </div>
    </div>

    <div class="card mb-6">
        <div class="card-body p-5 p-md-6">
            <nav class="account-types mb-5" aria-label="Filter accounts by type">
                <a href="{{ route('accounts.index', request()->only(['search', 'status', 'postable'])) }}"
                   class="btn btn-sm {{ ! request('type') ? 'btn-primary' : 'btn-light' }}"
                   @if(! request('type')) aria-current="page" @endif>All accounts</a>
                @foreach($types as $type)
                    <a href="{{ route('accounts.index', array_merge(request()->only(['search', 'status', 'postable']), ['type' => $type->value])) }}"
                       class="btn btn-sm {{ request('type') === $type->value ? 'btn-primary' : 'btn-light' }}"
                       @if(request('type') === $type->value) aria-current="page" @endif>{{ $type->label() }}</a>
                @endforeach
            </nav>
            <form method="GET" action="{{ route('accounts.index') }}" class="row g-4 align-items-end border-top pt-4">
                @if(request('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
                <div class="col-md-5">
                    <label for="account-search" class="form-label fw-semibold">Search accounts</label>
                    <input id="account-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by account name or code" type="search">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label for="account-status" class="form-label fw-semibold">Status</label>
                    <select id="account-status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(\App\Enums\RecordStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-light">Reset</a>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="postable" name="postable" value="1" @checked(request('postable'))>
                        <label class="form-check-label" for="postable">Postable accounts only <span class="text-muted">— accounts that accept journal entries</span></label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($accounts->isNotEmpty())
        <div class="d-flex flex-wrap justify-content-between gap-2 text-muted fs-7 mb-4">
            <span>Showing {{ $accounts->firstItem() }}–{{ $accounts->lastItem() }} of {{ number_format($accounts->total()) }} accounts</span>
            <span>Grouped by type · Counts below apply to this page</span>
        </div>
        @foreach($types as $type)
            @php($typeAccounts = $accounts->getCollection()->where('type', $type))
            @if($typeAccounts->isNotEmpty())
                <section class="card account-section mb-6" aria-labelledby="accounts-{{ $type->value }}">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3 px-5 py-4">
                        <div class="d-flex align-items-center gap-3">
                            <h2 id="accounts-{{ $type->value }}" class="fs-4 fw-bold mb-0">{{ $type->label() }}</h2>
                            <span class="badge badge-light-primary">{{ $typeAccounts->count() }} on this page</span>
                        </div>
                        <span class="text-muted fs-7">{{ $type->isBalanceSheet() ? 'Balance sheet' : 'Profit and loss' }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-hover align-middle mb-0 account-table">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th scope="col">Account</th>
                                    <th scope="col">Normal balance</th>
                                    <th scope="col">Parent account</th>
                                    <th scope="col">Posting</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($typeAccounts as $account)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <span class="badge badge-light font-monospace account-code">{{ $account->code }}</span>
                                                <span class="fw-semibold">{{ $account->name }}</span>
                                            </div>
                                            @if($account->description)<div class="text-muted fs-7 account-description">{{ $account->description }}</div>@endif
                                        </td>
                                        <td><span class="badge badge-light-{{ $account->normal_balance->value === 'debit' ? 'primary' : 'info' }}">{{ ucfirst($account->normal_balance->value) }}</span></td>
                                        <td>
                                            @if($account->parent)
                                                <div class="fw-semibold">{{ $account->parent->name }}</div>
                                                <span class="text-muted fs-7 font-monospace">{{ $account->parent->code }}</span>
                                            @else
                                                <span class="text-muted">Top-level account</span>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $account->is_postable ? 'badge-light-success' : 'badge-light' }}">{{ $account->is_postable ? 'Postable' : 'Group only' }}</span></td>
                                        <td><span class="badge badge-light-{{ $account->status->badgeClass() }}">{{ $account->status->label() }}</span></td>
                                        <td>
                                            <div class="d-flex justify-content-end gap-2">
                                                @can('update', $account)
                                                    <a href="{{ route('accounts.edit', $account) }}" class="btn btn-sm btn-light-primary" aria-label="Edit account {{ $account->code }}">Edit</a>
                                                @endcan
                                                @can('delete', $account)
                                                    <form method="POST" action="{{ route('accounts.destroy', $account) }}" onsubmit="return confirm('Delete this account?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-light-danger" aria-label="Delete account {{ $account->code }}">Delete</button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        @endforeach
        <div class="mt-5">{{ $accounts->links() }}</div>
    @else
        <div class="card"><div class="card-body text-center py-10">
            <h2 class="fs-4 mb-3">No accounts found</h2>
            <p class="text-muted mb-5">Try a different account type, search term, or status.</p>
            <a href="{{ route('accounts.index') }}" class="btn btn-light-primary">Clear filters</a>
        </div></div>
    @endif
</div>
</x-default-layout>
