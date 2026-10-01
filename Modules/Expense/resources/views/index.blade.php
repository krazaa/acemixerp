<x-default-layout>

@section('title', 'Expense Claims')
@section('sub-title', 'Employee expenses requiring manager and CEO approval before reimbursement.')

@section('toolbar-button')
    <a href="{{ route('expense.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Claim</a>
@endsection

    <div class="card border-0 shadow-sm">
        <div class="card-body">
        <div class="table-responsive">
            <table class="table table-row-bordered mb-5">
                <thead>
                    <tr class="fw-bold fs-6 text-gray-800">
                        <th class="table-sort-desc">Claim</th>
                        <th class="table-sort-asc">Employee</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                        <tr>
                            <td><a href="{{ route('expense.show', $claim) }}"><code>{{ $claim->number }}</code></a></td>
                            <td>{{ $claim->employee?->fullName() }}</td>
                            <td>{{ $claim->department?->name ?? '—' }}</td>
                            <td>{{ $claim->expense_date->format('d/m/Y') }}</td>
                            @php
                                $amountClass = match ($claim->status->value) {
                                    'reimbursed' => 'text-success',
                                    'draft'      => 'text-warning',
                                    default      => 'text-danger',
                                };
                            @endphp
                            <td class="text-end {{ $amountClass }} fw-semibold">
                                <a href="{{ route('expense.show', $claim) }}" class="{{ $amountClass }}">
                                    {{ $claim->currency_code }} {{ number_format((float) $claim->amount, 2) }}
                                </a>
                            <td>{{ str($claim->status->value)->replace('_', ' ')->title() }}</td>
                           <td class="text-center">
                                <a href="{{ route('expense.show', $claim) }}" class="text-dark" title="View Expense">
                                    <i class="fa fa-eye "></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No expense claims have been submitted yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
    <div class="mt-3">{{ $claims->links() }}</div>
</x-default-layout>
