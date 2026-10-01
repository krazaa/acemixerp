@auth
<div class="app-navbar-item ms-1 ms-md-4">
    <button type="button" class="btn btn-icon btn-light position-relative" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" aria-label="Notifications: {{ $unreadNotificationCount }} unread">
        <i class="bi bi-bell fs-3"></i>
        @if($unreadNotificationCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill badge-danger">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
        @endif
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column w-350px" data-kt-menu="true" id="kt_menu_notifications">
        <div class="p-5 border-bottom d-flex justify-content-between align-items-center">
            <h3 class="fs-5 mb-0">Notifications</h3>
            <span class="badge badge-primary">{{ $unreadNotificationCount }} unread</span>
        </div>
        <div class="scroll-y mh-325px">
            @forelse($recentNotifications as $notification)
                <form method="POST" action="{{ route('notifications.open', $notification->id) }}" class="border-bottom">
                    @csrf
                    <button type="submit" class="btn text-start w-100 p-5 {{ $notification->read_at ? '' : 'bg-light-primary' }}">
                        <span class="d-block fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</span>
                        <span class="d-block small text-muted mt-1">{{ $notification->data['message'] ?? '' }}</span>
                        <span class="d-block small text-muted mt-2">{{ $notification->created_at->diffForHumans() }}</span>
                        <span class="d-block small text-primary mt-2">{{ ($notification->data['category'] ?? '') === 'approval' ? 'View approval details' : 'View details' }}</span>
                    </button>
                </form>
            @empty
                <p class="text-muted text-center p-6 mb-0">You have no notifications yet.</p>
            @endforelse
        </div>
        <div class="p-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-light-primary">View all</a>
            @if($unreadNotificationCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-sm btn-light">Mark all read</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endauth
