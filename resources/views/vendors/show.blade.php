<x-default-layout>
@section('title', $vendor->name)
@section('sub-title')
 <code>{{ $vendor->code }}</code>
            @if($vendor->legal_name && $vendor->legal_name !== $vendor->name)
                · {{ $vendor->legal_name }}
            @endif
@endsection
@section('toolbar-button')
 @can('update', $vendor)
        <a href="{{ route('vendors.ledger', $vendor) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-journal-text"></i> Ledger
        </a>
            <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan
        @can('delete', $vendor)
            <form method="POST" action="{{ route('vendors.destroy', $vendor) }}"
                  onsubmit="return confirm('Delete this vendor?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        @endcan
  <a href="{{ route('vendors.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

<div class="row g-3">
    {{-- Summary --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-{{ $vendor->status->badgeClass() }}">
                            {{ $vendor->status->label() }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Tax #</dt>
                    <dd class="col-sm-8">{{ $vendor->tax_number ?: '—' }}</dd>
                    <dt class="col-sm-4">Registration #</dt>
                    <dd class="col-sm-8">{{ $vendor->registration_number ?: '—' }}</dd>
                    <dt class="col-sm-4">Tax Exempt</dt>
                    <dd class="col-sm-8">
                        @if($vendor->is_tax_exempt)
                            <span class="badge text-bg-warning">Exempt</span>
                        @else
                            <span class="text-muted">No</span>
                        @endif
                    </dd>
                    <dt class="col-sm-4">Credit Days</dt>
                    <dd class="col-sm-8">{{ $vendor->credit_days }}</dd>

                </dl>
            </div>
        </div>
    </div>

    {{-- Contact --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Contact Person</dt>
                     <dd class="col-sm-8">
                        @if($vendor->contact_person)
                            {{ $vendor->contact_person }}
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">
                        @if($vendor->email)
                            <a href="mailto:{{ $vendor->email }}">{{ $vendor->email }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8">{{ $vendor->phone ?: '—' }}</dd>

                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Bank Name</dt><dd class="col-sm-7">{{ $vendor->bank_name ?: '—' }}</dd>
                    <dt class="col-sm-5">Branch</dt><dd class="col-sm-7">{{ $vendor->bank_branch ?: '—' }}</dd>
                    <dt class="col-sm-5">Account Number</dt><dd class="col-sm-7">{{ $vendor->bank_account_number ?: '—' }}</dd>
                    <dt class="col-sm-5">IBAN</dt><dd class="col-sm-7">{{ $vendor->bank_iban ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Addresses --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Addresses</h3>
                    <div class="card-toolbar">{{ $vendor->addresses->count() }} on file</div>
            </div>
            <div class="card-body">
                @forelse($vendor->addresses as $addr)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">
                                {{ $addr->label ?: ucfirst($addr->type) }}
                                @if($addr->is_primary)
                                    <span class="badge text-bg-info ms-1">Primary</span>
                                @endif
                                <span class="badge text-bg-light border ms-1">{{ $addr->type }}</span>
                            </div>
                        </div>
                        <div class="text-muted small mt-1">{{ $addr->oneLine() }}</div>
                        @if($addr->contact_name || $addr->contact_email || $addr->contact_phone)
                            <div class="text-muted small">
                                @if($addr->contact_name){{ $addr->contact_name }}@endif
                                @if($addr->contact_email)
                                    · <a href="mailto:{{ $addr->contact_email }}">{{ $addr->contact_email }}</a>
                                @endif
                                @if($addr->contact_phone) · {{ $addr->contact_phone }}@endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted">No addresses on file.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($vendor->notes)
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                     <h3 class="card-title">Notes</h3>

                    </div>
                <div class="card-body">
                    <div class="text-pre-wrap">{{ $vendor->notes }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Audit --}}
    <div class="col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Audit</h3>
                </div>
            <div class="card-body small text-muted">
                <div>Created: {{ $vendor->created_at?->format('Y-m-d H:i') }}
                    @if($vendor->creator) by {{ $vendor->creator->name }}@endif</div>
                <div>Updated: {{ $vendor->updated_at?->format('Y-m-d H:i') }}
                    @if($vendor->updater) by {{ $vendor->updater->name }}@endif</div>
            </div>
        </div>
    </div>
</div>
</x-default-layout>
