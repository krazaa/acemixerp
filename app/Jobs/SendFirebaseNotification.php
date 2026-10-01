<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Services\FirebaseMessaging;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class SendFirebaseNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 40;

    public array $backoff = [15, 60, 300];

    public function __construct(public int $subscriptionId, public int $userId, public string $notificationId)
    {
        $this->onConnection(config('firebase.queue_connection'));
        $this->onQueue(config('firebase.queue'));
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseMessaging $firebase): void
    {
        if (! $firebase->enabled()) {
            return;
        }
        $subscription = PushSubscription::query()->with('user')->where('user_id', $this->userId)->find($this->subscriptionId);
        if (! $subscription?->user?->isActive()) {
            return;
        }
        $notification = $subscription->user->notifications()->find($this->notificationId);
        if (! $notification) {
            return;
        }
        $response = $firebase->send($subscription->token, [
            'title' => (string) ($notification->data['title'] ?? 'ACEMIX'),
            'body' => (string) ($notification->data['message'] ?? 'You have a new notification.'),
            'url' => (string) ($notification->data['url'] ?? '/notifications'),
            'notification_id' => $this->notificationId,
        ]);
        if ($response->successful()) {
            return;
        }
        $code = collect($response->json('error.details', []))->firstWhere('@type', 'type.googleapis.com/google.firebase.fcm.v1.FcmError')['errorCode'] ?? null;
        if ($code === 'UNREGISTERED') {
            PushSubscription::query()->whereKey($subscription->id)->where('token_hash', $subscription->token_hash)->delete();

            return;
        }
        $error = new RuntimeException('Firebase delivery failed (HTTP '.$response->status().', '.($code ?? $response->json('error.status', 'unknown')).').');
        if (in_array($response->status(), [400, 403, 404], true)) {
            $this->fail($error);

            return;
        }

        throw $error;
    }
}
