<?php

namespace App\Http\Controllers;

use App\Jobs\SendFirebaseNotification;
use App\Models\PushSubscription;
use App\Notifications\ApplicationNotification;
use App\Services\FirebaseMessaging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PushSubscriptionController extends Controller
{
    public function config(FirebaseMessaging $firebase): JsonResponse
    {
        return response()->json([
            'enabled' => $firebase->enabled(),
            'firebase' => config('firebase.web'),
            'vapidKey' => config('firebase.vapid_key'),
            'workerUrl' => route('push.worker', [], false),
        ])->header('Cache-Control', 'no-store');
    }

    public function worker(): Response
    {
        return response()->view('notifications.service-worker', ['firebaseConfig' => config('firebase.web')])
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache')
            ->header('Service-Worker-Allowed', '/');
    }

    public function store(Request $request, FirebaseMessaging $firebase): JsonResponse
    {
        abort_unless($firebase->enabled(), 503, 'Browser push is not configured.');
        $data = $request->validate(['token' => ['required', 'string', 'min:20', 'max:4096', 'regex:/^[A-Za-z0-9_:\-]+$/']]);
        $device = $request->cookie('push_device');
        if (! is_string($device) || ! Str::isUuid($device)) {
            $device = (string) Str::uuid();
        }
        DB::transaction(function () use ($request, $data, $device): void {
            $hash = hash('sha256', $data['token']);
            PushSubscription::query()->where('token_hash', $hash)->where('device_id', '!=', $device)->delete();
            PushSubscription::query()->updateOrCreate(['device_id' => $device], [
                'user_id' => $request->user()->id,
                'token' => $data['token'], 'token_hash' => $hash, 'last_seen_at' => now(),
            ]);
        });

        return response()->json(['enabled' => true])
            ->cookie('push_device', $device, 525600, '/', null, $request->isSecure(), true, false, 'lax');
    }

    public function destroy(Request $request): JsonResponse
    {
        PushSubscription::query()->where('user_id', $request->user()->id)
            ->where('device_id', $request->cookie('push_device'))->delete();

        return response()->json(['enabled' => false]);
    }

    public function test(Request $request): JsonResponse
    {
        $subscription = PushSubscription::query()->where('user_id', $request->user()->id)
            ->where('device_id', $request->cookie('push_device'))->firstOrFail();
        $notification = new ApplicationNotification('ACEMIX push test', 'Browser notifications are connected.', route('notifications.index', [], false), 'workflow');
        $notification->id = (string) Str::uuid();
        $request->user()->notify($notification);
        SendFirebaseNotification::dispatch($subscription->id, $request->user()->id, $notification->id);

        return response()->json(['message' => 'Test notification queued.']);
    }
}
