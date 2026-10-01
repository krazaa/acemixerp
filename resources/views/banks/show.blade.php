<x-default-layout>
@section('title', $bank->name)
@section('sub-title')
     <div class="text-muted small">
            <code>{{ $bank->code }}</code>
            @if($bank->short_name) · {{ $bank->short_name }} @endif
        </div>
@endsection
@section('toolbar-button')
 @can('update', $bank)
            <a href="{{ route('banks.edit', $bank) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('delete', $bank)
            <form method="POST" action="{{ route('banks.destroy', $bank) }}"
                  onsubmit="return confirm('Delete this bank?');">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger">Delete</button>
            </form>
        @endcan
   <a href="{{ route('banks.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection


<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                        <h3 class="card-title">Details</h3>
                    </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge text-bg-{{ $bank->status->badgeClass() }}">
                            {{ $bank->status->label() }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Account Number</dt>
                    <dd class="col-sm-8">{{ $bank->account_number ?: '—' }}</dd>
                    <dt class="col-sm-4">IBAN #</dt>
                    <dd class="col-sm-8">{{ $bank->iban ?: '—' }}</dd>

                </dl>
            </div>
        </div>
    </div>

@if(! empty($bank->bank_contacts))
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Bank Contacts</h3>
                 <div class="card-toolbar">
                <span class="text-muted small">{{ count($bank->bank_contacts) }} on file</span>
            </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bank->bank_contacts as $c)
                                <tr>
                                    <td class="fw-semibold">{{ $c['name'] ?? '—' }}</td>
                                    <td>{{ $c['role'] ?? '—' }}</td>
                                    <td>
                                        @if(! empty($c['email']))
                                            <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a>
                                        @else — @endif
                                    </td>
                                    <td>{{ $c['phone'] ?? '—' }}</td>
                                    <td class="text-muted small">{{ $c['notes'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif


    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                  <h3 class="card-title">Addresses</h3>
        <div class="card-toolbar">
           <span class="text-muted small">{{ $bank->addresses->count() }} on file</span>
        </div>
            </div>
            <div class="card-body">
                @forelse($bank->addresses as $addr)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="fw-semibold">
                            {{ $addr->label ?: ucfirst($addr->type) }}
                            @if($addr->is_primary)
                                <span class="badge text-bg-info ms-1">Primary</span>
                            @endif
                        </div>
                        <div class="text-muted small mt-1">{{ $addr->oneLine() }}</div>
                    </div>
                @empty
                    <div class="text-muted">No addresses on file.</div>
                @endforelse
            </div>
        </div>
    </div>

    @if($bank->notes)
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Notes</div>
                <div class="card-body">
                    <div style="white-space: pre-wrap;">{{ $bank->notes }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Audit</h3>
        <div class="card-toolbar">
           <span class="text-muted small">{{ $bank->addresses->count() }} on file</span>
        </div>
            </div>
            <div class="card-body small text-muted">
                <div>Created: {{ $bank->created_at?->format('Y-m-d H:i') }}
                    @if($bank->creator) by {{ $bank->creator->name }}@endif</div>
                <div>Updated: {{ $bank->updated_at?->format('Y-m-d H:i') }}
                    @if($bank->updater) by {{ $bank->updater->name }}@endif</div>
            </div>
        </div>
    </div>
</div>
</x-default-layout>
