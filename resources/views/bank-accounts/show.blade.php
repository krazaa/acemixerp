<x-default-layout>
@section('title', $bankAccount->name)
@section('sub-title')
    {{ $bankAccount->code }}
    @if($bankAccount->is_default)
        <span class="badge badge-primary ms-1">Default</span>
    @endif
    · {{ $bankAccount->bank?->name ?? '—' }}
@endsection

@section('toolbar-button')
 @can('update', $bankAccount)
            <a href="{{ route('bank-accounts.edit', $bankAccount) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan
        @can('makeDefault', $bankAccount)
            @if(! $bankAccount->is_default && $bankAccount->status === \App\Enums\RecordStatus::Active)
                <form method="POST" action="{{ route('bank-accounts.make-default', $bankAccount) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-secondary">Make Default</button>
                </form>
            @endif
        @endcan
        @can('delete', $bankAccount)
            <form method="POST" action="{{ route('bank-accounts.destroy', $bankAccount) }}"
                  onsubmit="return confirm('Delete this bank account?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        @endcan
    <a href="{{ route('bank-accounts.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection
<div class="row g-3">
    {{-- Balance card --}}
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Current Balance</h3>
            </div>
            <div class="card-body">
            {{ number_format((float) $balance, 4) }}
                <div class="text-muted small">{{ $bankAccount->currency_code }}</div>
            </div>
        </div>
        </div>

    {{-- Details --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge text-bg-{{ $bankAccount->status->badgeClass() }}">
                            {{ $bankAccount->status->label() }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Bank</dt>
                    <dd class="col-sm-8">{{ $bankAccount->bank?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Account Type</dt>
                    <dd class="col-sm-8">{{ $bankAccount->account_type->label() }}</dd>
                    <dt class="col-sm-4">Account Number</dt>
                    <dd class="col-sm-8"><code>{{ $bankAccount->account_number }}</code></dd>
                    @if($bankAccount->iban)
                        <dt class="col-sm-4">IBAN</dt>
                        <dd class="col-sm-8"><code>{{ $bankAccount->iban }}</code></dd>
                    @endif
                    @if($bankAccount->swift_code)
                        <dt class="col-sm-4">SWIFT</dt>
                        <dd class="col-sm-8"><code>{{ $bankAccount->swift_code }}</code></dd>
                    @endif
                    <dt class="col-sm-4">GL Account</dt>
                    <dd class="col-sm-8">
                        @if($bankAccount->glAccount)
                            <code>{{ $bankAccount->glAccount->code }}</code>
                            {{ $bankAccount->glAccount->name }}
                        @else — @endif
                    </dd>
                    <dt class="col-sm-4">Opening Balance</dt>
                    <dd class="col-sm-8">
                        {{ number_format((float) $bankAccount->opening_balance, 4) }}
                        @if($bankAccount->opening_balance_date)
                            <span class="text-muted small">
                                (as of {{ $bankAccount->opening_balance_date->format('Y-m-d') }})
                            </span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Audit --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Audit</h3>
            </div>
            <div class="card-body small text-muted">
                <div>Created: {{ $bankAccount->created_at?->format('Y-m-d H:i') }}</div>
                <div>Updated: {{ $bankAccount->updated_at?->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>
</div>
</x-default-layout>
