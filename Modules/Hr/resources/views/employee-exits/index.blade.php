@extends('layouts.app')
@section('title', 'Employee Exits')
@section('content')
<div class="d-flex justify-content-between mb-3"><h1 class="h3">Employee Exits</h1><a href="{{ route('employee-exits.create') }}" class="btn btn-primary">Process Exit</a></div><div class="card"><table class="table mb-0"><thead><tr><th>Employee</th><th>Date</th><th>Reason</th></tr></thead><tbody>@forelse($exits as $exit)<tr><td>{{ $exit->employee?->fullName() }}</td><td>{{ $exit->exit_date->format('Y-m-d') }}</td><td>{{ $exit->reason }}</td></tr>@empty<tr><td colspan="3" class="text-center">No employee exits.</td></tr>@endforelse</tbody></table></div>
@endsection
