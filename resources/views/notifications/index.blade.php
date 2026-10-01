<x-default-layout>
    @section('title', 'Notifications')
    @section('toolbar-button')
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-primary btn-sm">Mark all as read</button>
        </form>
    @endsection
    <div class="d-flex flex-wrap align-items-center gap-4 mb-5">
        <label class="form-check form-switch mb-0">
            <input id="push-toggle" class="form-check-input" type="checkbox" role="switch" disabled>
            <span class="form-check-label">Browser notifications</span>
        </label>
        <button id="push-test" type="button" class="btn btn-sm btn-light-primary" disabled>Send test notification</button>
        <span id="push-status" class="text-muted small" role="status" aria-live="polite">Checking browser push...</span>
    </div>
    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('notifications.index') }}" class="btn btn-sm {{ request('filter') !== 'unread' ? 'btn-primary' : 'btn-light' }}">All</a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="btn btn-sm {{ request('filter') === 'unread' ? 'btn-primary' : 'btn-light' }}">Unread</a>
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="card">
        <div class="list-group list-group-flush">
            @forelse($notifications as $notification)
                <div class="list-group-item p-5 {{ $notification->read_at ? '' : 'bg-light-primary' }}">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="fs-5 mb-2">{{ $notification->data['title'] ?? 'Notification' }}</h2>
                            <p class="mb-2">{{ $notification->data['message'] ?? '' }}</p>
                            <div class="small text-muted">{{ $notification->created_at->format('d M Y H:i') }} · {{ $notification->read_at ? 'Read' : 'Unread' }}</div>
                        </div>
                        <div class="d-flex gap-2 align-items-start flex-wrap">
                            <form method="POST" action="{{ route('notifications.open', $notification->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary">{{ ($notification->data['category'] ?? '') === 'approval' ? 'View approval details' : 'View details' }}</button>
                            </form>
                            <form method="POST" action="{{ route('notifications.update', $notification->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $notification->read_at ? 'unread' : 'read' }}">
                                <button class="btn btn-sm btn-light">{{ $notification->read_at ? 'Mark unread' : 'Mark read' }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-muted mb-0">{{ request('filter') === 'unread' ? 'You are all caught up.' : 'You have no notifications yet.' }}</p>
            @endforelse
        </div>
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-default-layout>
