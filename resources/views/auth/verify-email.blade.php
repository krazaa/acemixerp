<x-auth-layout>
    @section('title', 'Verify your email')

    <div class="text-center mb-10">
        <h1 class="text-gray-900 fw-bolder mb-3">Verify your email address</h1>
        <p class="text-gray-600">Check your inbox for a verification link sent to <strong>{{ auth()->user()->email }}</strong>. Open the link to verify your account.</p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success" role="status">A new verification link has been sent to your email address.</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <p class="text-gray-600 mb-6">If you cannot find the email, check your spam folder or request another link.</p>

    <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
        @csrf
        <button type="submit" class="btn btn-primary w-100">Resend verification email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-light w-100">Log out</button>
    </form>
</x-auth-layout>
