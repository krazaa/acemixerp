<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Jobs\SendFirebaseNotification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Services\FirebaseMessaging;
use Database\Seeders\ExpenseNotificationRolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Expense\Models\ExpenseClaim;
use Modules\Procurement\Models\VendorInvoice;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FirebasePushTest extends TestCase
{
    private string $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['activitylog.enabled' => false]);
        Schema::disableForeignKeyConstraints();
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '0001_01_01_000002_create_jobs_table.php',
            '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
            '2026_09_11_062704_create_permission_tables.php',
            '2026_09_28_163338_add_soft_deletes_to_users_table.php',
            '2026_09_28_162318_create_notifications_table.php',
            '2026_09_29_173044_create_push_subscriptions_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        (require base_path('Modules/Expense/database/migrations/2026_09_19_083154_create_expense_claims_table.php'))->up();
        (require base_path('Modules/Procurement/database/migrations/2026_09_21_112300_create_vendor_invoices_table.php'))->up();
        (require base_path('Modules/Expense/database/migrations/2026_09_29_173047_add_owner_review_to_vendor_invoices_table.php'))->up();
        $this->seed(ExpenseNotificationRolesSeeder::class);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        $this->credentials = tempnam(sys_get_temp_dir(), 'firebase-test-');
        file_put_contents($this->credentials, json_encode([
            'type' => 'service_account', 'project_id' => 'test-project',
            'client_email' => 'test@test-project.iam.gserviceaccount.com', 'private_key' => $privateKey,
        ]));
        config([
            'firebase.enabled' => true, 'firebase.credentials' => $this->credentials,
            'firebase.web.projectId' => 'test-project', 'firebase.vapid_key' => 'test-public-key',
            'firebase.queue_connection' => 'database',
        ]);
        Http::preventStrayRequests();
        $this->withCredentials();
    }

    protected function tearDown(): void
    {
        if (isset($this->credentials)) {
            unlink($this->credentials);
        }
        parent::tearDown();
    }

    public function test_registration_is_authenticated_encrypted_and_deduplicated(): void
    {
        $this->postJson(route('push.store'), ['token' => str_repeat('a', 100)])->assertUnauthorized();
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $this->actingAs($user)->postJson(route('push.store'), ['token' => 'short'])->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->postJson(route('push.store'), ['token' => str_repeat('a', 100), 'user_id' => 999])->assertOk()->assertCookie('push_device');
        $subscription = PushSubscription::firstOrFail();
        $this->assertSame($user->id, $subscription->user_id);
        $this->assertSame(str_repeat('a', 100), $subscription->token);
        $this->assertNotSame($subscription->token, DB::table('push_subscriptions')->value('token'));
        $this->assertArrayNotHasKey('token', $subscription->toArray());
        $this->postJson(route('push.store'), ['token' => str_repeat('a', 100)])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    public function test_disable_is_scoped_to_the_current_user_and_device(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $other = User::factory()->create(['status' => UserStatus::Active]);
        $own = $this->subscription($user);
        $private = $this->subscription($other);
        $this->actingAs($user)->withCookie('push_device', $private->device_id)
            ->deleteJson(route('push.destroy'))->assertOk();
        $this->assertModelExists($private);
        $this->withCookie('push_device', $own->device_id)->deleteJson(route('push.destroy'))->assertOk();
        $this->assertModelMissing($own);
    }

    public function test_test_notification_only_targets_the_signed_in_browser(): void
    {
        Queue::fake([SendFirebaseNotification::class]);
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $own = $this->subscription($user);
        $this->subscription(User::factory()->create(['status' => UserStatus::Active]));
        $this->actingAs($user)->withCookie('push_device', $own->device_id)
            ->postJson(route('push.test'))->assertOk();
        $this->assertSame(1, $user->notifications()->count());
        Queue::assertPushed(SendFirebaseNotification::class, fn ($job) => $job->subscriptionId === $own->id && $job->userId === $user->id);
        Queue::assertPushed(SendFirebaseNotification::class, 1);
    }

    public function test_logout_unsubscribes_only_the_current_browser(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $browser = $this->subscription($user);
        $otherBrowser = $this->subscription($user);
        $this->actingAs($user)->withCookie('push_device', $browser->device_id)->post(route('logout'))->assertRedirect();
        $this->assertGuest();
        $this->assertModelMissing($browser);
        $this->assertModelExists($otherBrowser);
    }

    public function test_owner_sees_device_registration_dialog_in_authenticated_layout(): void
    {
        $owner = User::factory()->create(['status' => UserStatus::Active, 'last_login_at' => now()]);
        $owner->assignRole('owner');

        $this->actingAs($owner)->get(route('notifications.index'))->assertOk()
            ->assertSee('id="push-device-prompt"', false)
            ->assertSee('Register this device')
            ->assertSee('data-push-user="'.$owner->id.'"', false);
    }

    public function test_disabled_and_unverified_users_cannot_register_a_browser(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Disabled]);
        $this->actingAs($user)->postJson(route('push.store'), ['token' => str_repeat('a', 100)])->assertForbidden();
        $user = User::factory()->unverified()->create(['status' => UserStatus::Active]);
        $this->actingAs($user)->postJson(route('push.store'), ['token' => str_repeat('a', 100)])->assertForbidden();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_firebase_check_validates_without_sending_a_real_notification(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-test']),
            'https://fcm.googleapis.com/v1/projects/test-project/messages:send' => Http::response(['name' => 'validation']),
        ]);
        $this->artisan('firebase:check')->assertSuccessful();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'messages:send') && $request['validate_only'] === true);
    }

    public function test_firebase_authentication_failure_does_not_send_a_message_or_expose_secrets(): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);
        $this->artisan('firebase:check')->expectsOutput('Firebase authentication failed (HTTP 400).')->assertFailed();
        Http::assertSentCount(1);
    }

    public function test_service_worker_is_public_but_never_exposes_server_credentials(): void
    {
        $this->get(route('push.worker'))->assertOk()->assertHeader('Content-Type', 'application/javascript')
            ->assertSee('test-project')->assertDontSee('private_key')->assertDontSee($this->credentials);
        $this->actingAs(User::factory()->create(['status' => UserStatus::Active]))->getJson(route('push.config'))->assertOk()
            ->assertJsonPath('enabled', true)->assertJsonMissingPath('credentials');
        config(['firebase.enabled' => false]);
        $this->postJson(route('push.store'), ['token' => str_repeat('a', 100)])->assertServiceUnavailable();
    }

    public function test_expense_transitions_notify_the_requested_roles_and_creator_only(): void
    {
        Queue::fake([SendFirebaseNotification::class]);
        $creator = User::factory()->create(['status' => UserStatus::Active]);
        $manager = User::factory()->create(['status' => UserStatus::Active]);
        $manager->assignRole('operations-manager');
        $owner = User::factory()->create(['status' => UserStatus::Active]);
        $owner->assignRole('owner');
        $disabled = User::factory()->create(['status' => UserStatus::Disabled]);
        $disabled->assignRole('operations-manager');
        foreach ([$creator, $manager, $owner, $disabled] as $user) {
            $this->subscription($user);
        }
        $claim = ExpenseClaim::create([
            'number' => 'EXP-PUSH', 'employee_id' => 1, 'expense_account_id' => 1,
            'expense_date' => today(), 'amount' => 100, 'description' => 'Travel',
            'status' => 'draft', 'created_by' => $creator->id,
        ]);
        $claim->update(['status' => 'submitted']);
        $this->assertTrue(Gate::forUser($manager)->allows('managerApprove', $claim));
        $claim->update(['description' => 'Updated description']);
        $this->assertSame(1, $manager->notifications()->count());
        $this->assertSame(0, $owner->notifications()->count());
        $this->assertSame(0, $disabled->notifications()->count());
        $claim->update(['status' => 'manager_approved']);
        $this->assertSame(1, $owner->notifications()->count());
        $claim->update(['status' => 'rejected', 'rejection_reason' => 'Not approved']);
        $this->assertSame(1, $creator->notifications()->count());
        $this->assertSame('/expenses/'.$claim->id, $creator->notifications()->first()->data['url']);
        Queue::assertPushed(SendFirebaseNotification::class, 3);
    }

    public function test_vendor_invoice_review_endpoints_enforce_roles_and_notify_each_stage(): void
    {
        Queue::fake([SendFirebaseNotification::class]);
        $creator = User::factory()->create(['status' => UserStatus::Active]);
        $creator->givePermissionTo(Permission::findOrCreate('expense.create', 'web'));
        $manager = User::factory()->create(['status' => UserStatus::Active]);
        $manager->assignRole('operations-manager');
        $owner = User::factory()->create(['status' => UserStatus::Active]);
        $owner->assignRole('owner');
        foreach ([$creator, $manager, $owner] as $user) {
            $this->subscription($user);
        }
        $invoice = $this->invoice($creator);
        $this->actingAs($creator)->patch(route('expense.vendor-invoices.submit', $invoice))->assertSessionHasNoErrors();
        $this->assertSame('submitted', $invoice->fresh()->status);
        $this->assertSame(1, $manager->notifications()->count());
        $this->patch(route('expense.vendor-invoices.approve', $invoice))->assertForbidden();
        $this->actingAs($manager)->patch(route('expense.vendor-invoices.approve', $invoice))->assertSessionHasNoErrors();
        $this->assertSame('approved', $invoice->fresh()->status);
        $this->assertSame(1, $owner->notifications()->count());
        $this->patch(route('expense.vendor-invoices.reject', $invoice), ['rejection_reason' => 'Denied'])->assertForbidden();
        $this->actingAs($owner)->patch(route('expense.vendor-invoices.reject', $invoice), ['rejection_reason' => ''])->assertSessionHasErrors('rejection_reason');
        $this->patch(route('expense.vendor-invoices.reject', $invoice), ['rejection_reason' => 'Missing evidence'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vendor_invoices', ['id' => $invoice->id, 'status' => 'rejected', 'rejection_reason' => 'Missing evidence']);
        $this->assertSame(1, $creator->notifications()->count());
        Queue::assertPushed(SendFirebaseNotification::class, 3);
    }

    public function test_both_expense_flows_notify_owner_ceo_and_finance_with_registered_device_push(): void
    {
        Queue::fake([SendFirebaseNotification::class]);
        Role::findOrCreate('ceo', 'web')->givePermissionTo(Permission::findOrCreate('expense.ceo_approve', 'web'));
        Role::findOrCreate('finance-manager', 'web');
        $creator = User::factory()->create(['status' => UserStatus::Active]);
        $manager = User::factory()->create(['status' => UserStatus::Active]);
        $manager->assignRole('operations-manager');
        $owner = User::factory()->create(['status' => UserStatus::Active]);
        $owner->assignRole(['owner', 'ceo']);
        $ceo = User::factory()->create(['status' => UserStatus::Active]);
        $ceo->assignRole('ceo');
        $finance = User::factory()->create(['status' => UserStatus::Active]);
        $finance->assignRole('finance-manager');
        $disabled = User::factory()->create(['status' => UserStatus::Disabled]);
        $disabled->assignRole('finance-manager');
        foreach ([$manager, $owner, $finance, $disabled] as $user) {
            $this->subscription($user);
        }
        $claim = ExpenseClaim::create([
            'number' => 'EXP-FINANCE', 'employee_id' => 1, 'expense_account_id' => 1,
            'expense_date' => today(), 'amount' => 100, 'description' => 'Travel',
            'status' => 'draft', 'created_by' => $creator->id,
        ]);
        $invoice = $this->invoice($creator);

        foreach ([$claim, $invoice] as $record) {
            $record->update(['status' => 'submitted']);
            $record->update(['status' => $record instanceof ExpenseClaim ? 'manager_approved' : 'approved']);
        }
        $this->assertSame(2, $manager->notifications()->count());
        $this->assertSame(2, $owner->notifications()->count());
        $this->assertSame(2, $ceo->notifications()->count());
        $this->assertSame(0, $finance->notifications()->count());

        $claim->update(['status' => 'ceo_approved']);
        $this->actingAs($ceo)->patch(route('expense.vendor-invoices.owner-approve', $invoice))->assertSessionHasNoErrors();
        $this->patch(route('expense.vendor-invoices.owner-approve', $invoice))->assertForbidden();
        $invoice->refresh()->update(['updated_by' => $owner->id]);
        $claim->update(['description' => 'Edited after approval']);

        $this->assertSame(2, $finance->notifications()->count());
        $this->assertSame(0, $disabled->notifications()->count());
        $this->assertSame(0, $creator->notifications()->count());
        $this->assertSame(2, $owner->notifications()->count());
        Queue::assertPushed(SendFirebaseNotification::class, 6);
        Queue::assertPushed(SendFirebaseNotification::class, function ($job) use ($finance): bool {
            return $job->userId === $finance->id && $finance->notifications()->whereKey($job->notificationId)->exists();
        });
        Queue::assertNotPushed(SendFirebaseNotification::class, fn ($job) => in_array($job->userId, [$ceo->id, $disabled->id, $creator->id], true));
    }

    public function test_owner_approval_is_required_before_posting_and_cannot_be_repeated(): void
    {
        $owner = User::factory()->create(['status' => UserStatus::Active]);
        $owner->assignRole('owner');
        $finance = User::factory()->create(['status' => UserStatus::Active]);
        $finance->givePermissionTo(Permission::findOrCreate('supplier_invoice.post', 'web'));
        $invoice = $this->invoice($owner);
        $invoice->update(['status' => 'approved']);
        $this->actingAs($finance)->patch(route('expense.vendor-invoices.post', $invoice))->assertForbidden();
        $this->actingAs($owner)->patch(route('expense.vendor-invoices.owner-approve', $invoice))->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertSame($owner->id, $invoice->owner_approved_by);
        $this->assertNotNull($invoice->owner_approved_at);
        $this->assertTrue(Gate::forUser($finance)->allows('post', $invoice));
        $this->patch(route('expense.vendor-invoices.owner-approve', $invoice))->assertForbidden();
        $this->patch(route('expense.vendor-invoices.reject', $invoice), ['rejection_reason' => 'Too late'])->assertForbidden();
    }

    public function test_rollback_discards_both_database_notification_and_pending_push_job(): void
    {
        $manager = User::factory()->create(['status' => UserStatus::Active]);
        $manager->assignRole('operations-manager');
        $this->subscription($manager);
        $invoice = $this->invoice(User::factory()->create(['status' => UserStatus::Active]));
        DB::beginTransaction();
        $invoice->update(['status' => 'submitted']);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('jobs', 0);
        DB::rollBack();
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('jobs', 0);
        $invoice->refresh()->update(['status' => 'submitted']);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_firebase_signs_oauth_request_caches_token_and_sends_notification_data(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-test', 'expires_in' => 3600]),
            'https://fcm.googleapis.com/v1/projects/test-project/messages:send' => Http::response(['name' => 'message-1']),
        ]);
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $subscription = $this->subscription($user);
        $user->notify(new ApplicationNotification('Approval', 'Review expense', '/expenses/1', 'approval'));
        $job = new SendFirebaseNotification($subscription->id, $user->id, $user->notifications()->first()->id);
        $job->handle(app(FirebaseMessaging::class));
        $job->handle(app(FirebaseMessaging::class));
        Http::assertSentCount(3);
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://oauth2.googleapis.com/token') {
                return false;
            }
            [$header, $claims, $signature] = explode('.', $request['assertion']);
            $credentials = json_decode(file_get_contents($this->credentials), true);
            $key = openssl_pkey_get_details(openssl_pkey_get_private($credentials['private_key']))['key'];

            return openssl_verify($header.'.'.$claims, base64_decode(strtr($signature, '-_', '+/')), $key, OPENSSL_ALGO_SHA256) === 1;
        });
        Http::assertSent(fn ($request) => str_contains($request->url(), 'messages:send')
            && $request->hasHeader('Authorization', 'Bearer access-test')
            && $request['message']['data']['url'] === '/expenses/1'
            && $request['message']['token'] === $subscription->token);
    }

    public function test_unregistered_tokens_are_removed_but_temporary_failures_are_retried(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-test']),
            'https://fcm.googleapis.com/v1/projects/test-project/messages:send' => Http::sequence()
                ->push(['error' => ['details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']]]], 404)
                ->push(['error' => ['status' => 'UNAVAILABLE']], 503),
        ]);
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->notify(new ApplicationNotification('Approval', 'Review', '/expenses/1', 'approval'));
        $notification = $user->notifications()->first();
        $expired = $this->subscription($user);
        (new SendFirebaseNotification($expired->id, $user->id, $notification->id))->handle(app(FirebaseMessaging::class));
        $this->assertModelMissing($expired);
        $valid = $this->subscription($user);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('503');
        (new SendFirebaseNotification($valid->id, $user->id, $notification->id))->handle(app(FirebaseMessaging::class));
    }

    public function test_inactive_users_and_reassigned_devices_do_not_receive_queued_messages(): void
    {
        Http::fake();
        $user = User::factory()->create(['status' => UserStatus::Disabled]);
        $subscription = $this->subscription($user);
        $user->notify(new ApplicationNotification('Private', 'Review', '/expenses/1', 'approval'));
        $job = new SendFirebaseNotification($subscription->id, $user->id, $user->notifications()->first()->id);
        $job->handle(app(FirebaseMessaging::class));
        $subscription->update(['user_id' => User::factory()->create(['status' => UserStatus::Active])->id]);
        $job->handle(app(FirebaseMessaging::class));
        Http::assertNothingSent();
    }

    private function subscription(User $user): PushSubscription
    {
        $token = Str::random(100);

        return PushSubscription::create(['user_id' => $user->id, 'device_id' => (string) Str::uuid(), 'token' => $token, 'token_hash' => hash('sha256', $token), 'last_seen_at' => now()]);
    }

    public function test_vendor_invoice_pdf_is_a_download_and_enforces_view_access(): void
    {
        (require database_path('migrations/2026_01_03_000003_create_vendors_table.php'))->up();
        (require database_path('migrations/2026_01_08_000001_create_accounts_table.php'))->up();
        (require base_path('Modules/Procurement/database/migrations/2026_09_21_113603_create_vendor_invoice_lines_table.php'))->up();
        $creator = User::factory()->create(['status' => UserStatus::Active]);
        $invoice = $this->invoice($creator);
        $invoice->update(['notes' => '<table><tr><td>Freight</td><td>100</td></tr></table>', 'subtotal' => 100, 'tax_total' => 18, 'whttax_total' => 5, 'total' => 113, 'paid_amount' => 20]);
        $invoice->lines()->create(['position' => 1, 'description' => 'Freight', 'line_subtotal' => 100, 'line_tax' => 18, 'line_wht_tax' => 5]);

        $this->get(route('expense.vendor-invoices.pdf', $invoice))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['status' => UserStatus::Active]))
            ->get(route('expense.vendor-invoices.pdf', $invoice))->assertForbidden();
        $response = $this->actingAs($creator)->get(route('expense.vendor-invoices.pdf', $invoice));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertDownload('vi-push.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    private function invoice(User $user): VendorInvoice
    {
        return VendorInvoice::create([
            'number' => 'VI-PUSH', 'vendor_id' => 1, 'vendor_invoice_number' => 'V-100',
            'invoice_date' => today(), 'due_date' => today(), 'billing_month' => now()->format('Y-m'),
            'status' => 'draft', 'created_by' => $user->id,
        ]);
    }
}
