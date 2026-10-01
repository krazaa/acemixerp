<x-default-layout>
@section('title', 'Journal Entries')

 @section('toolbar-button')
  @can('create', \App\Models\JournalEntry::class)
        <a href="{{ route('journals.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Journal
        </a>
    @endcan
 @endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm"
               placeholder="Number, reference, or description">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\App\Enums\JournalEntryStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-sm btn-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('journals.index') }}" class="btn btn-sm btn-light-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($journals as $j)
                    <tr>
                        <td><code>{{ $j->number }}</code></td>
                        <td>{{ $j->entry_date->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('journals.show', $j) }}" class="text-decoration-none">
                                {{ \Illuminate\Support\Str::limit($j->description, 60) }}
                            </a>
                            @if($j->reference)<div class="text-muted small">Ref: {{ $j->reference }}</div>@endif
                        </td>
                        <td class="text-end">{{ number_format((float) $j->total_debit, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $j->total_credit, 2) }}</td>
                        <td><span class="badge badge-{{ $j->status->badgeClass() }}">{{ $j->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('journals.show', $j) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $j)
                                <a href="{{ route('journals.edit', $j) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No journal entries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $journals->links() }}</div>
</x-default-layout>
