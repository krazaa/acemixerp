<div>
    <!-- Always remember that you are absolutely unique. Just like everyone else. - Margaret Mead -->
</div>
@extends('layouts.app')
@section('title', 'Record Attendance')
@section('content')
<h1 class="h3 mb-3">Record Attendance</h1><form method="POST" action="{{ route('attendances.store') }}" class="card"><div class="card-body row g-3">@csrf <div class="col-md-6"><label>Employee</label><select name="employee_id" class="form-select">@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->number }} — {{ $employee->fullName() }}</option>@endforeach</select></div><div class="col-md-6"><label>Date</label><input type="date" name="attendance_date" value="{{ now()->toDateString() }}" class="form-control"></div><div class="col-md-4"><label>Status</label><select name="status" class="form-select"><option>present</option><option>late</option><option>absent</option><option>leave</option></select></div><div class="col-md-4"><label>Check in</label><input type="datetime-local" name="checked_in_at" class="form-control"></div><div class="col-md-4"><label>Check out</label><input type="datetime-local" name="checked_out_at" class="form-control"></div></div><div class="card-footer"><button class="btn btn-primary">Save</button></div></form>
@endsection
