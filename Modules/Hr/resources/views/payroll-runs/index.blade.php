<<x-default-layout>
@section('title', 'Payroll Runs')

@section('toolbar-button')
    <a href="{{ route('payroll-runs.create') }}" class="btn btn-sm btn-primary">New Payroll Run</a>
@endsection

<div class="card">
<div class="card-body">
    <table class="table mb-0">
        <thead>
            <tr class="fw-bold fs-6 text-gray-800">
            <th>Number</th>
            <th>Period</th>
            <th>Employees</th>
            <th class="text-end">Gross</th>
            <th class="text-end">Deductions & Tax</th>
            <th class="text-end">Net Pay</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($payrollRuns as $run)
        <tr>
            <td>{{ $run->number }}</td
                ><td>{{ $run->period_start->format('d/m/Y') }} – {{ $run->period_end->format('d/m/Y') }}</td>
                <td>{{ $run->lines_count }}</td>
                <td class="text-end">{{ number_format((float) $run->gross_total, 2) }}</td>
                <td class="text-end">{{ number_format((float) $run->deduction_total, 2) }}</td>
                <td class="text-end">{{ number_format((float) $run->net_total, 2) }}</td>
                <td>{{ ucfirst($run->status) }}</td>
                <td>
                    @if($run->status === 'draft')<form method="POST" action="{{ route('payroll-runs.approve', $run) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-success">Approve</button>
                    </form>
                    @elseif($run->status === 'approved')<form method="POST" action="{{ route('payroll-runs.finalize', $run) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-primary">Finalize</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No payroll runs.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
</x-default-layout>
