@extends('layouts.app')
@section('title', 'Process Employee Exit')
@section('content')
<h1 class="h3 mb-3">Process Employee Exit</h1><form method="POST" action="{{ route('employee-exits.store') }}" class="card"><div class="card-body row g-3">@csrf <div class="col-md-6"><label>Employee</label><select name="employee_id" class="form-select" required>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->number }} — {{ $employee->fullName() }}</option>@endforeach</select></div><div class="col-md-6"><label>Exit Date</label><input type="date" name="exit_date" class="form-control" required></div><div class="col-12"><label>Reason</label><input name="reason" class="form-control" required></div><div class="col-12"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div></div><div class="card-footer"><button class="btn btn-danger">Process Exit</button></div></form>
@endsection
