<x-default-layout>
@section('title', 'Users Management')
@section('sub-title', ' Manage system users, roles, statuses, and employee accounts.')

@section('toolbar-button')
    @can('create', \App\Models\User::class)
        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">
                 <i class="mdi mdi-account-plus me-1"></i>
                    Create New User
            </a>
    @endcan

@endsection


	<div class="card">
    <div class="card-header">
    <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-center">

        {{-- Search --}}
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="mdi mdi-magnify"></i>
                </span>

                <input
                    id="users-search"
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    class="form-control"
                    placeholder="Search name, email, employee #"
                    autocomplete="off"
                >
            </div>
        </div>

        {{-- Status --}}
        <div class="col-md-2">
            <select
                id="users-status"
                name="status"
                class="form-select"
            >
                <option value="">All statuses</option>

                @foreach(\App\Enums\UserStatus::cases() as $s)
                    <option
                        value="{{ $s->value }}"
                        @selected(request('status') === $s->value)
                    >
                        {{ $s->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Role --}}
        <div class="col-md-2">
            <select
                id="users-role"
                name="role"
                class="form-select"
            >
                <option value="">All roles</option>

                @foreach($roles as $role)
                    <option
                        value="{{ $role->name }}"
                        @selected(request('role') === $role->name)
                    >
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Filter --}}
        <div class="col-md-3">
            <button type="submit" class="btn btn-light-info w-100">
                <i class="mdi mdi-filter-outline me-1"></i>
                Filter
            </button>
        </div>

    </form>
</div>


			<div class="card-body">
				<div class="table-responsive userlist-table">
					<table class="table table-bordered table-striped table-vcenter text-nowrap mb-0 data-table2">
						<thead>
							<tr>
							   <th>No</th>
							   <th>Name</th>
							   <th>Email</th>
							    <th style="width: 180px;">Roles</th>
							   <th>Action</th>
							</tr>
						</thead>
                        <tbody>
                              @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            @if($user->employee_number)
                                <div class="text-muted small">{{ $user->employee_number }}</div>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                            <td style="width: 180px; max-width: 180px; white-space: normal;">
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                    <span class="badge badge-secondary">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-{{ $user->status->badgeClass() }}">
                                {{ $user->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('view', $user)
                                <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @endcan
                            @can('update', $user)
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
                        </tbody>
					</table>
				</div>
				<div class="mt-3">{{ $users->links() }}</div>
			</div>
		</div>
		</div><!-- COL END -->
	</div>
	<!-- row closed  -->
</div>
<!-- Container closed -->
</div>
<!-- main-content closed -->
</x-default-layout>
