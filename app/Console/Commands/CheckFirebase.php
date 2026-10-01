<?php

namespace App\Console\Commands;

use App\Services\FirebaseMessaging;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('firebase:check')]
#[Description('Validate Firebase credentials and FCM permission without delivering a notification')]
class CheckFirebase extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FirebaseMessaging $firebase): int
    {
        try {
            $firebase->check();
            $this->info('Firebase authentication and FCM validation succeeded. No notification was sent.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception instanceof \RuntimeException ? $exception->getMessage() : 'Firebase validation failed. Check connectivity and configuration.');

            return self::FAILURE;
        }
    }
}
