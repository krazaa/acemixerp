<div>
    <!-- Simplicity is the ultimate sophistication. - Leonardo da Vinci -->
</div>
@extends('layouts.app')
@section('title', 'Attendance')
@section('content')
<div class="d-flex justify-content-between mb-3"><h1 class="h3">Attendance</h1><a class="btn btn-primary" href="{{ route('attendances.create') }}">Record Attendance</a></div><div class="card"><table class="table mb-0"><thead><tr><th>Date</th><th>Employee</th><th>Status</th><th>Check In</th><th>Check Out</th></tr></thead><tbody>@forelse($attendances as $attendance)<tr><td>{{ $attendance->attendance_date->format('Y-m-d') }}</td><td>{{ $attendance->employee?->fullName() }}</td><td>{{ ucfirst($attendance->status) }}</td><td>{{ $attendance->checked_in_at?->format('H:i') }}</td><td>{{ $attendance->checked_out_at?->format('H:i') }}</td></tr>@empty<tr><td colspan="5" class="text-center">No attendance records.</td></tr>@endforelse</tbody></table></div>{{ $attendances->links() }}
@endsection
