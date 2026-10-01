@extends('layouts.app')
@section('title', 'New Leave Request')
@section('content')
<h1 class="h3 mb-3">New Leave Request</h1><form method="POST" action="{{ route('leave-requests.store') }}" class="card"><div class="card-body row g-3">@csrf <div class="col-md-6"><label>Employee</label><select name="employee_id" class="form-select" required>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->number }} — {{ $employee->fullName() }}</option>@endforeach</select></div><div class="col-md-3"><label>Start</label><input name="start_date" type="date" class="form-control" required></div><div class="col-md-3"><label>End</label><input name="end_date" type="date" class="form-control" required></div><div class="col-12"><label>Reason</label><textarea name="reason" class="form-control" required></textarea></div></div><div class="card-footer"><button class="btn btn-primary">Submit</button></div></form>
@endsection
