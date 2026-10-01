<x-default-layout>
@section('title', 'New Payroll Run')


<form method="POST" action="{{ route('payroll-runs.store') }}" class="card"><div class="card-body row g-3">@csrf <div class="col-md-6"><label>Period Start</label><input type="date" name="period_start" class="form-control" required></div><div class="col-md-6"><label>Period End</label><input type="date" name="period_end" class="form-control" required></div></div><div class="card-footer"><button class="btn btn-primary">Generate Draft Payroll</button></div></form>
</x-default-layout>
