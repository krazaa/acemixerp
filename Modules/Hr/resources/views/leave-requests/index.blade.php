@extends('layouts.app')
@section('title', 'Leave Requests')
@section('content')
<div class="d-flex justify-content-between mb-3"><h1 class="h3">Leave Requests</h1><a href="{{ route('leave-requests.create') }}" class="btn btn-primary">New Leave Request</a></div>
<div class="card"><table class="table mb-0"><thead><tr><th>Employee</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th></th></tr></thead><tbody>@forelse($leaveRequests as $leaveRequest)<tr><td>{{ $leaveRequest->employee?->fullName() }}</td><td>{{ $leaveRequest->start_date->format('Y-m-d') }}</td><td>{{ $leaveRequest->end_date->format('Y-m-d') }}</td><td>{{ $leaveRequest->days }}</td><td>{{ ucfirst($leaveRequest->status) }}</td><td>@if($leaveRequest->status === 'pending')<form method="POST" action="{{ route('leave-requests.approve', $leaveRequest) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Approve</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="text-center">No leave requests.</td></tr>@endforelse</tbody></table></div><div class="mt-3">{{ $leaveRequests->links() }}</div>
@endsection
