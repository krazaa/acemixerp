<x-default-layout>


@section('title', 'Salary Structures')

@section('toolbar-button')
  <a href="{{ route('salary-structures.create') }}" class="btn btn-sm btn-primary">New Salary Structure</a>
@endsection
    <div class="card">
        <div class="card-body">
        <table class="table mb-0"><thead><tr><th>Code</th><th>Name</th><th class="text-end">Basic Salary</th><th class="text-end">Allowances</th><th class="text-end">Deductions</th><th>Status</th><th></th></tr></thead><tbody>@forelse ($salaryStructures as $salaryStructure)<tr><td><code>{{ $salaryStructure->code }}</code></td><td>{{ $salaryStructure->name }}</td><td class="text-end">{{ number_format((float) $salaryStructure->basic_salary, 4) }}</td><td class="text-end">{{ number_format((float) $salaryStructure->allowances, 4) }}</td><td class="text-end">{{ number_format((float) $salaryStructure->deductions, 4) }}</td><td><span class="badge badge-{{ $salaryStructure->is_active ? 'success' : 'secondary' }}">{{ $salaryStructure->is_active ? 'Active' : 'Inactive' }}</span></td><td class="text-nowrap"><a href="{{ route('salary-structures.show', $salaryStructure) }}" class="btn btn-sm btn-outline-secondary">View</a><a href="{{ route('salary-structures.edit', $salaryStructure) }}" class="btn btn-sm btn-outline-primary">Edit</a></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No salary structures found.</td></tr>@endforelse</tbody></table></div></div>
    <div class="mt-3">{{ $salaryStructures->links() }}</div>
</x-default-layout>

