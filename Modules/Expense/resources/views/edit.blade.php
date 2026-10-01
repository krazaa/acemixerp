<x-default-layout>
@section('title', 'Edit Expense Claim - '.$claim->number  )
@section('sub-title', 'Draft claims can be changed before submission.')

@section('toolbar-button')
<a href="{{ route('expense.show', $claim) }}" class="btn btn-sm btn-outline-secondary">Back</a>
@endsection


    <form method="POST" action="{{ route('expense.update', $claim) }}">
        @csrf @method('PATCH')
        @include('expense::_form', ['claim' => $claim, 'employees' => collect(), 'departments' => $departments, 'expenseAccounts' => $expenseAccounts])
        <div class="d-flex justify-content-end gap-2 mt-3"><a href="{{ route('expense.show', $claim) }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Save Draft Changes</button></div>
    </form>
</x-default-layout>
