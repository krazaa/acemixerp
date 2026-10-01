<x-default-layout>

@section('title', $salaryStructure->name)

@section('sub-title')
    <code>{{ $salaryStructure->code }}</code>
@endsection

@section('toolbar-button')
    <div class="d-flex flex-wrap gap-2">

        {{-- Edit --}}
        <a href="{{ route('salary-structures.edit', $salaryStructure) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="fa fa-edit me-1"></i>
            Edit
        </a>

        {{-- Delete --}}
        <form method="POST"
              action="{{ route('salary-structures.destroy', $salaryStructure) }}"
              onsubmit="return confirm('Delete this salary structure?');"
              class="d-inline">
            @csrf
            @method('DELETE')

            <button type="submit"
                    class="btn btn-sm btn-outline-danger"
                    @disabled($salaryStructure->employees_count > 0)>
                <i class="fa fa-trash me-1"></i>
                Delete
            </button>
        </form>

        {{-- Back --}}
        <a href="{{ route('salary-structures.index') }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i>
            Back
        </a>

    </div>
@endsection


{{-- Delete Restriction --}}
@if ($salaryStructure->employees_count > 0)
    <div class="alert alert-info d-flex align-items-center mb-3">
        <i class="fa fa-info-circle me-2"></i>

        <div>
            This salary structure is assigned to
            <strong>{{ $salaryStructure->employees_count }}</strong>
            employee(s), so it cannot be deleted.
        </div>
    </div>
@endif


{{-- Salary Structure Details --}}
<div class="card">

    <div class="card-header">
        <h3 class="card-title mb-0">
            Salary Structure Details
        </h3>
    </div>

    <div class="card-body">

        <dl class="row mb-0">

            {{-- Status --}}
            <dt class="col-12 col-sm-3">
                Status
            </dt>

            <dd class="col-12 col-sm-9">
                @if ($salaryStructure->is_active)
                    <span class="badge badge-success">
                        Active
                    </span>
                @else
                    <span class="badge badge-secondary">
                        Inactive
                    </span>
                @endif
            </dd>


            {{-- Basic Salary --}}
            <dt class="col-12 col-sm-3">
                Basic Salary
            </dt>

            <dd class="col-12 col-sm-9">
                {{ number_format((float) $salaryStructure->basic_salary, 2) }}
            </dd>


            {{-- Allowances --}}
            <dt class="col-12 col-sm-3">
                Allowances
            </dt>

            <dd class="col-12 col-sm-9">
                {{ number_format((float) $salaryStructure->allowances, 2) }}
            </dd>


            {{-- Deductions --}}
            <dt class="col-12 col-sm-3">
                Deductions
            </dt>

            <dd class="col-12 col-sm-9">
                {{ number_format((float) $salaryStructure->deductions, 2) }}
            </dd>


            {{-- Net Salary --}}
            <dt class="col-12 col-sm-3 fw-bold">
                Net Salary
            </dt>

            <dd class="col-12 col-sm-9 fw-bold">
                {{ number_format(
                    (float) $salaryStructure->basic_salary
                    + (float) $salaryStructure->allowances
                    - (float) $salaryStructure->deductions,
                    2
                ) }}
            </dd>

        </dl>

    </div>
</div>

</x-default-layout>
