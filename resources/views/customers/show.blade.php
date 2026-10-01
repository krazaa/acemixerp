<x-default-layout>
@section('title', $customer->name)
@section('sub-title')

  <div class="text-muted small">
            <code>{{ $customer->code }}</code>
            @if($customer->legal_name && $customer->legal_name !== $customer->name)
                · {{ $customer->legal_name }}
            @endif
        </div>
@endsection

@section('toolbar-button')
 @can('update', $customer)
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan
        @can('delete', $customer)
            <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                  onsubmit="return confirm('Delete this customer?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        @endcan
    <a href="{{ route('categories.create') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
@endsection


<div class="row g-3">
    <div class="col-12">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex flex-wrap gap-2">
            @can('view', $customer)
                <a href="{{ route('customers.ledger', $customer) }}"
                   class="btn btn-sm btn-light-secondary">View Ledger</a>
            @endcan
            @can('sales_order.create')
                <a href="{{ route('sales.sales-orders.create', ['customer_id' => $customer->id]) }}"
                   class="btn btn-sm btn-light-success">New Sales Order</a>
            @endcan
            @can('invoice.create')
                <a href="{{ route('sales.sales-invoices.create', ['customer_id' => $customer->id]) }}"
                   class="btn btn-sm btn-light-info">New Invoice</a>
            @endcan
            @can('receipt.create')
                <a href="{{ route('sales.customer-receipts.create', ['customer_id' => $customer->id]) }}"
                   class="btn btn-sm btn-success">Record Payment</a>
            @endcan
        </div>
    </div>
</div>
    {{-- Summary --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                        <h3 class="card-title">Summary</h3>
                    </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-{{ $customer->status->badgeClass() }}">
                            {{ $customer->status->label() }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Tax #</dt>
                    <dd class="col-sm-8">{{ $customer->tax_number ?: '—' }}</dd>
                    <dt class="col-sm-4">Registration #</dt>
                    <dd class="col-sm-8">{{ $customer->registration_number ?: '—' }}</dd>
                    <dt class="col-sm-4">Tax Exempt</dt>
                    <dd class="col-sm-8">
                        @if($customer->is_tax_exempt)
                            <span class="badge text-bg-warning">Exempt</span>
                        @else
                            <span class="text-muted">No</span>
                        @endif
                    </dd>
                    <dt class="col-sm-4">Credit Limit</dt>
                    <dd class="col-sm-8">
                        @if((float) $customer->credit_limit > 0)
                            {{ number_format((float) $customer->credit_limit, 2) }}
                        @else
                            <span class="text-muted">No limit</span>
                        @endif
                    </dd>
                    <dt class="col-sm-4">Credit Days</dt>
                    <dd class="col-sm-8">{{ $customer->credit_days }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Contact --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                    <h3 class="card-title">Contact</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Contact Person</dt>
                    <dd class="col-sm-8">{{ $customer->c_person ?: '—' }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">
                        @if($customer->email)
                            <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-sm-4">Company Phone</dt>
                    <dd class="col-sm-8">{{ $customer->phone ?: '—' }}</dd>
                    <dt class="col-sm-4">Contact person phone</dt>
                    <dd class="col-sm-8">
                        @if($customer->cp_phone)
                                {{ $customer->cp_phone }}
                        @else
                            —
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Addresses --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Addresses</h3>
                <div class="card-toolbar">
            <span class="text-muted small">{{ $customer->addresses->count() }} on file</span>
        </div>

            </div>
            <div class="card-body">
                @forelse($customer->addresses as $addr)
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

    {{-- Status change (only if transitions are available) --}}
    @can('changeStatus', $customer)
        @php
            $availableTransitions = collect(\App\Enums\CustomerStatus::cases())
                ->filter(fn ($s) => $customer->status->canTransitionTo($s));
        @endphp
        @if($availableTransitions->isNotEmpty())
            <div class="col-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">Change Status</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('customers.change-status', $customer) }}"
                              class="row g-2 align-items-end">
                            @csrf @method('PATCH')
                            <div class="col-md-4">
                                <select id="customer-status" name="status" class="form-select form-select-sm">
                                    @foreach($availableTransitions as $s)
                                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-sm btn-warning" type="submit">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    {{-- Notes --}}
    @if($customer->notes)
        <div class="col-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                        <h3 class="card-title">Notes</h3>
                    </div>
                <div class="card-body">
                    <div class="text-pre-wrap">{{ $customer->notes }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Audit --}}
    <div class="col-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                    <h3 class="card-title">Audit</h3></div>
            <div class="card-body small text-muted">
                <div>Created: {{ $customer->created_at?->format('Y-m-d H:i') }}
                    @if($customer->creator) by {{ $customer->creator->name }}@endif</div>
                <div>Updated: {{ $customer->updated_at?->format('Y-m-d H:i') }}
                    @if($customer->updater) by {{ $customer->updater->name }}@endif</div>
            </div>
        </div>
    </div>
</div>
</x-default-layout>
