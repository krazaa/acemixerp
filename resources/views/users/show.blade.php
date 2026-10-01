<x-default-layout>
@section('title', $user->name)

@section('toolbar-button')

    @can('update', $user)
        <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary">Edit</a>
    @endcan

    @can('create', \App\Models\User::class)
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left fa-sm"></i>  Back</a>
    @endcan

@endsection


<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                        <h3 class="card-title">Profile</h3>
                    </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $user->email }}</dd>
                    <dt class="col-sm-4">Employee #</dt><dd class="col-sm-8">{{ $user->employee_number ?: '—' }}</dd>
                    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $user->phone ?: '—' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-{{ $user->status->badgeClass() }}">
                            {{ $user->status->label() }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Last Login</dt>
                    <dd class="col-sm-8">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</dd>
                </dl>
            </div>
        </div>
    </div>

     @can('manageStatus', $user)
    <div class="col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                        <h3 class="card-title">Change Status</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.change-status', $user) }}" class="row g-2 align-items-end">
                    @csrf @method('PATCH')
                    <div class="col-md-4">
                        <label class="form-label" for="status">New Status</label>
                        <select id="status" name="status" class="form-select">
                            @foreach(\App\Enums\UserStatus::cases() as $s)
                                @if($user->status->canTransitionTo($s))
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                        <button class="btn btn-warning">Update Status</button>

                </form>
            </div>
        </div>
    </div>
    @endcan

    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                 <h3 class="card-title">Roles & Permissions</h3>
                </div>
            <div class="card-body">
                <div class="mb-2">
                    @forelse($user->roles as $role)
                        <span class="badge badge-primary me-1">{{ $role->name }}</span>
                    @empty
                        <span class="text-muted">No roles assigned.</span>
                    @endforelse
                </div>
                <hr>
                <div class="small text-muted">Effective permissions:</div>
                <div>
                    @foreach($user->getAllPermissions()->sortBy('name') as $perm)
                        <span class="badge badge-light border me-1 mb-1">{{ $perm->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
</x-default-layout>
