
<x-default-layout>
@section('title', 'Payment Terms')

 @section('toolbar-button')
 <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print / PDF</button><button type="button" class="btn btn-sm btn-outline-success" onclick="exportAgingCsv()">Export Excel</button>
    @can('create', \App\Models\PaymentTerm::class)
        <a href="{{ route('payment-terms.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Payment Term
        </a>
    @endcan

 @endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control"
               placeholder="Search name or code">
    </div>
    <div class="col-md-3">
        <select name="type" class="form-select">
            <option value="">All types</option>
            @foreach(\App\Enums\PaymentTermType::cases() as $t)
                <option value="{{ $t->value }}" @selected(request('type') === $t->value)>
                    {{ $t->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('payment-terms.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Days</th>
                    <th>Day of Month</th>
                    <th>Discount</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentTerms as $term)
                    <tr>
                        <td>
                            <code>{{ $term->code }}</code>
                            @if($term->is_default)
                                <span class="badge text-bg-primary ms-1">Default</span>
                            @endif
                        </td>
                        <td>{{ $term->name }}</td>
                        <td><span class="badge text-bg-light border">{{ $term->type->label() }}</span></td>
                        <td>{{ $term->days ?: '—' }}</td>
                        <td>{{ $term->day_of_month ?: '—' }}</td>
                        <td>
                            @if($term->discount_percent)
                                {{ number_format((float) $term->discount_percent, 2) }}% in {{ $term->discount_days }}d
                            @else — @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $term->status->badgeClass() }}">
                                {{ $term->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $term)
                                <a href="{{ route('payment-terms.edit', $term) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('makeDefault', $term)
                                @if(! $term->is_default && $term->status === \App\Enums\RecordStatus::Active)
                                    <form method="POST" action="{{ route('payment-terms.make-default', $term) }}"
                                          class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary">Make Default</button>
                                    </form>
                                @endif
                            @endcan
                            @can('delete', $term)
                                <form method="POST" action="{{ route('payment-terms.destroy', $term) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this payment term?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No payment terms.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $paymentTerms->links() }}</div>
</x-default-layout>
