<x-default-layout>

    @section('title', 'Change Password')

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Change Password</h3>
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

                        <form method="POST" action="{{ route('password.change.update') }}">
                            @csrf

                            <div class="mb-4">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input
                                    id="current_password"
                                    type="password"
                                    name="current_password"
                                    class="form-control"
                                    required
                                    autocomplete="current-password"
                                >
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">New Password</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                >
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    class="form-control"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                >
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('dashboard') }}" class="btn btn-light">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-default-layout>
