<x-default-layout>
@section('title', 'Tax Rates')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Tax Rates</h1>
    @can('create', \App\Models\TaxRate::class)
        <a href="{{ route('tax-rates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Tax Rate
        </a>
    @endcan
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control"
               placeholder="Search name or code">
    </div>
    <div class="col-md-3">
        <select name="component" class="form-select">
            <option value="">All components</option>
            @foreach(\App\Enums\TaxRateComponent::cases() as $c)
                <option value="{{ $c->value }}" @selected(request('component') === $c->value)>
                    {{ $c->label() }}
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
    <div class="col-md-1"><a href="{{ route('tax-rates.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Component</th>
                    <th class="text-end">Rate</th>
                    <th>Effective</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($taxRates as $rate)
                    <tr>
                        <td>
                            <code>{{ $rate->code }}</code>
                            @if($rate->is_default)
                                <span class="badge text-bg-primary ms-1">Default</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $rate->name }}</div>
                            @if($rate->is_compound)
                                <span class="badge text-bg-light border">Compound</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $rate->component->value === 'input' ? 'warning' : 'info' }}">
                                {{ $rate->component->label() }}
                            </span>
                        </td>
                        <td class="text-end">{{ number_format((float) $rate->rate, 4) }}%</td>
                        <td class="small">
                            {{ $rate->effective_from->format('Y-m-d') }}
                            @if($rate->effective_to)
                                → {{ $rate->effective_to->format('Y-m-d') }}
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $rate->status->badgeClass() }}">
                                {{ $rate->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $rate)
                                <a href="{{ route('tax-rates.edit', $rate) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('makeDefault', $rate)
                                @if(! $rate->is_default && $rate->status === \App\Enums\RecordStatus::Active)
                                    <form method="POST" action="{{ route('tax-rates.make-default', $rate) }}"
                                          class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary">Make Default</button>
                                    </form>
                                @endif
                            @endcan
                            @can('delete', $rate)
                                <form method="POST" action="{{ route('tax-rates.destroy', $rate) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this tax rate?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No tax rates.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $taxRates->links() }}</div>
</x-default-layout>
