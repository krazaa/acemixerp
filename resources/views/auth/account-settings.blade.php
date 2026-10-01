<x-default-layout>

    @section('title', 'Account Settings')

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Account Settings</h3>
                        <a href="{{ route('password.change.edit') }}" class="btn btn-light btn-sm">Change Password</a>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('account.settings.update') }}">
                            @csrf

                            <div class="mb-4">
                                <label for="name" class="form-label">Full Name</label>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                >
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                >
                            </div>

                            <div class="rounded bg-light p-4 mb-4">
                                <div class="fw-semibold mb-2">Account Summary</div>
                                <div class="text-muted">Last updated: {{ optional($user->updated_at)->format('d M Y h:i A') ?? 'N/A' }}</div>
                                <div class="text-muted">Member since: {{ optional($user->created_at)->format('d M Y') ?? 'N/A' }}</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('dashboard') }}" class="btn btn-light">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-default-layout>
