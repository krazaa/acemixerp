<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['filter' => ['nullable', 'in:all,unread']]);

        return view('notifications.index', [
            'notifications' => $request->user()->notifications()
                ->when($request->input('filter') === 'unread', fn ($query) => $query->whereNull('read_at'))
                ->orderByDesc('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function update(Request $request, string $notification): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:read,unread']]);
        $record = $request->user()->notifications()->findOrFail($notification);
        $data['status'] === 'read' ? $record->markAsRead() : $record->markAsUnread();

        return back()->with('status', 'Notification updated.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();
        $url = $record->data['url'] ?? '';
        if (! is_string($url) || ! str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return redirect()->route('notifications.index');
        }

        return redirect($url);
    }
}
