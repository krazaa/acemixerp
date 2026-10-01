<?php

namespace App\Console\Commands;

use App\Contracts\SequenceGenerator;
use App\Contracts\SettingRepository;
use App\Enums\UserStatus;
use App\Models\FinancialYear;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ErpInstallCommand extends Command
{
    protected $signature = 'erp:install
                            {--admin-email= : Administrator email}
                            {--admin-password= : Administrator password}
                            {--force : Skip confirmations}';

    protected $description = 'Initialize the ERP in a production-ready state.';

    public function handle(
        SequenceGenerator $sequences,
        SettingRepository $settings,
    ): int {
        $this->info('ERP initialization started.');

        $this->step(1, 'Environment validation', fn () => $this->validateEnvironment());
        $this->step(2, 'Database validation', fn () => $this->validateDatabase());
        $this->step(3, 'Redis validation', fn () => $this->validateRedis());
        $this->step(4, 'Storage validation', fn () => $this->validateStorage());
        $this->step(5, 'Mail validation', fn () => $this->validateMail());
        $this->step(6, 'APP_KEY validation', fn () => $this->validateAppKey());

        $this->step(7, 'Running migrations', function () {
            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());
        });

        $this->step(8, 'Publishing Spatie permission migrations', function () {
            // Idempotent: publishes only if migrations are missing.
            Artisan::call('vendor:publish', [
                '--provider' => 'Spatie\\Permission\\PermissionServiceProvider',
                '--tag' => 'permission-migrations',
                '--force' => false,
            ]);
        });

        $this->step(9, 'Seeding roles & permissions', function () {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PermissionsSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolesSeeder', '--force' => true]);
        });

        $this->step(10, 'Creating administrator', fn () => $this->createAdmin());

        $this->step(11, 'Creating organization record', fn () => $this->createOrganization());

        $this->step(12, 'Creating financial year', fn () => $this->createFinancialYear());

        $this->step(13, 'Initializing document sequences', fn () => $this->seedSequences($sequences));

        $this->step(14, 'Warming settings cache', fn () => $settings->get('erp.installed_at'));

        $this->step(15, 'Marking installation complete', function () use ($settings) {
            $settings->set('erp.installed_at', now()->toIso8601String(), 'string', 'system');
            $settings->set('erp.version', '1.0.0', 'string', 'system');
        });

        $this->newLine();
        $this->info('ERP installed successfully.');

        return self::SUCCESS;
    }

    private function step(int $n, string $label, callable $fn): void
    {
        $this->line("  [{$n}] {$label}");
        $fn();
    }

    private function validateEnvironment(): void
    {
        if (! extension_loaded('bcmath')) {
            throw new \RuntimeException('bcmath extension is required.');
        }
        if (! extension_loaded('intl')) {
            throw new \RuntimeException('intl extension is required.');
        }
        if (config('database.default') === 'sqlite' && app()->isProduction()) {
            throw new \RuntimeException('SQLite is not permitted in production.');
        }
    }

    private function validateDatabase(): void
    {
        $pdo = DB::connection()->getPdo();
        $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        if (DB::connection()->getDriverName() === 'mysql' && version_compare($version, '8.0.0', '<')) {
            throw new \RuntimeException("MySQL 8.0+ required, got {$version}.");
        }
    }

    private function validateRedis(): void
    {
        try {
            Redis::connection()->ping();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Redis is unreachable: '.$e->getMessage());
        }
    }

    private function validateStorage(): void
    {
        $disk = Storage::disk(config('filesystems.default'));
        $test = 'erp-install-'.Str::random(8).'.txt';
        $disk->put($test, 'ok');
        if ($disk->get($test) !== 'ok') {
            throw new \RuntimeException('Storage write/read test failed.');
        }
        $disk->delete($test);
    }

    private function validateMail(): void
    {
        if (empty(config('mail.from.address'))) {
            throw new \RuntimeException('mail.from.address is not configured.');
        }
    }

    private function validateAppKey(): void
    {
        if (! config('app.key')) {
            throw new \RuntimeException('APP_KEY is missing. Run php artisan key:generate.');
        }
    }

    private function createAdmin(): void
    {
        $email = $this->option('admin-email') ?: $this->ask('Administrator email');
        $password = $this->option('admin-password') ?: $this->secret('Administrator password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid administrator email.');
        }
        if (strlen((string) $password) < 12) {
            throw new \InvalidArgumentException('Administrator password must be at least 12 characters.');
        }

        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'System Administrator',
                'password' => Hash::make((string) $password),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['super-admin']);
        $this->line("       admin: {$user->email}");
    }

    private function createOrganization(): void
    {
        Organization::query()->firstOrCreate(['id' => 1], [
            'name' => config('app.name', 'Enterprise ERP'),
            'currency_code' => 'USD',
            'timezone' => config('app.timezone', 'UTC'),
        ]);
    }

    private function createFinancialYear(): void
    {
        $startMonth = (int) (Organization::query()->value('fiscal_year_start_month') ?? 1);
        $now = now();
        $start = $now->month >= $startMonth
            ? $now->copy()->month($startMonth)->startOfMonth()
            : $now->copy()->subYear()->month($startMonth)->startOfMonth();
        $end = $start->copy()->addYear()->subDay();

        FinancialYear::query()->updateOrCreate(
            ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
            [
                'name' => 'FY '.$start->year.'/'.$end->year,
                'status' => 'open',
                'is_current' => true,
            ],
        );
    }

    private function seedSequences(SequenceGenerator $sequences): void
    {
        $map = [
            'invoice' => 'INV',
            'quotation' => 'QTN',
            'sales_order' => 'SO',
            'delivery' => 'DLV',
            'credit_note' => 'CN',
            'purchase_request' => 'PR',
            'purchase_requisition' => 'PR',
            'rfq' => 'RFQ',
            'purchase_order' => 'PO',
            'goods_receipt' => 'GRN',
            'supplier_invoice' => 'SI',
            'payment' => 'PAY',
            'receipt' => 'REC',
            'journal' => 'JV',
            'stock_transfer' => 'TRF',
            'stock_adjustment' => 'ADJ',
            'expense' => 'EXP',
            'payroll' => 'PRL',
        ];
        foreach ($map as $key => $prefix) {
            $sequences->register($key, $prefix, 6, true);
        }
    }
}
