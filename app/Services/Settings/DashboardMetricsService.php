<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Expense\Models\ExpenseClaim;
use Modules\FixedAssets\Models\Asset;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\PayrollRun;
use Modules\Inventory\Models\StockBalance;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\RequestForQuotation;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\VendorInvoice;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;

final class DashboardMetricsService
{
    /** @return array{cards: array, modules: array, queues: array, charts: array} */
    public function forUser(User $user, CarbonImmutable $month, string $currency): array
    {
        $cards = $modules = $queues = $series = [];
        $months = collect(range(5, 0))->map(fn (int $offset): CarbonImmutable => $month->subMonths($offset));
        $charts = ['labels' => $months->map(fn (CarbonImmutable $date): string => $date->format('M Y'))->all(), 'series' => [], 'expenses' => [], 'production' => []];

        if ($user->can('sales.view') && Route::has('sales.sales-invoices.index')) {
            $invoices = SalesInvoice::query()->where('currency_code', $currency)->whereIn('status', ['posted', 'partially_paid', 'paid']);
            $trend = $this->monthlyTotals($invoices, 'invoice_date', 'total', $months);
            $series[] = ['label' => 'Sales invoices', 'data' => $trend, 'color' => '#168570'];
            $cards[] = $this->card('Sales invoiced', end($trend), 'Posted invoices this month', 'sales.sales-invoices.index', 'graph-up-arrow', true);
            $creditTotals = SalesCreditNote::query()->where('status', 'posted')->selectRaw('sales_invoice_id, SUM(total) as credited')->groupBy('sales_invoice_id');
            $receivables = (float) (clone $invoices)->leftJoinSub($creditTotals, 'credits', fn ($join) => $join->on('credits.sales_invoice_id', '=', 'sales_invoices.id'))
                ->selectRaw('COALESCE(SUM(CASE WHEN sales_invoices.total - paid_amount - COALESCE(credits.credited,0) > 0 THEN sales_invoices.total - paid_amount - COALESCE(credits.credited,0) ELSE 0 END),0) as balance')->value('balance');
            $cards[] = $this->card('Receivables', $receivables, 'Current invoice balance', 'sales.sales-invoices.index', 'arrow-down-left', true);
            $counts = $this->statusCounts(SalesOrder::query());
            $modules[] = $this->module('Sales', $counts, 'Sales orders', 'sales.sales-orders.index', 'cart3');
            $queues[] = $this->queue('Sales orders awaiting review', $counts['submitted'] ?? 0, 'sales.sales-orders.index');
        }

        if ($user->can('purchase.view') && Route::has('procurement.supplier-invoices.index')) {
            $invoices = SupplierInvoice::query()->where('currency_code', $currency)->whereIn('status', ['posted', 'partially_paid', 'paid']);
            $trend = $this->monthlyTotals($invoices, 'invoice_date', 'total', $months);
            $series[] = ['label' => 'Supplier invoices', 'data' => $trend, 'color' => '#367bd6'];
            $cards[] = $this->card('Purchases invoiced', end($trend), 'Posted supplier invoices this month', 'procurement.supplier-invoices.index', 'bag-check', true);
            $cards[] = $this->card('Supplier payables', $this->outstanding($invoices), 'Current supplier invoice balance', 'procurement.supplier-invoices.index', 'arrow-up-right', true);
            $counts = $this->statusCounts(PurchaseOrder::query());
            $modules[] = $this->module('Procurement', $counts, 'Purchase orders', 'procurement.purchase-orders.index', 'bag-check');
            $queues[] = $this->queue('Purchase orders awaiting review', $counts['submitted'] ?? 0, 'procurement.purchase-orders.index');
            $queues[] = $this->queue('RFQs receiving quotations', RequestForQuotation::query()->whereIn('status', ['issued', 'receiving'])->count(), 'procurement.rfqs.index');
        }

        if ($user->can('expense.view') && Route::has('expense.index')) {
            $claims = ExpenseClaim::query()->where('currency_code', $currency);
            $trend = $this->monthlyTotals((clone $claims)->where('status', 'reimbursed'), 'expense_date', 'approved_amount', $months);
            $series[] = ['label' => 'Reimbursed claims', 'data' => $trend, 'color' => '#df9131'];
            $cards[] = $this->card('Reimbursed expenses', end($trend), 'By expense date this month', 'expense.index', 'receipt', true);
            $counts = $this->statusCounts($claims);
            $charts['expenses'] = $counts;
            $modules[] = $this->module('Expenses', $counts, 'Expense claims · '.$currency, 'expense.index', 'receipt');
            $queues[] = $this->queue('Expense claims awaiting review', ($counts['submitted'] ?? 0) + ($counts['manager_approved'] ?? 0), 'expense.index');
            $queues[] = $this->queue('Expense claims ready for payment', $counts['ceo_approved'] ?? 0, 'expense.index');
            if (Route::has('expense.vendor-invoices.index')) {
                $vendorCounts = $this->statusCounts(VendorInvoice::query());
                $modules[] = $this->module('Vendor Expenses', $vendorCounts, 'Vendor invoices', 'expense.vendor-invoices.index', 'file-earmark-text');
                $queues[] = $this->queue('Vendor invoices awaiting review', $vendorCounts['submitted'] ?? 0, 'expense.vendor-invoices.index');
            }
        }

        if ($user->can('inventory.view') && Route::has('inventory.dashboard')) {
            $cards[] = $this->card('Stock value', StockBalance::query()->sum('total_value'), 'Current inventory valuation', 'inventory.valuation', 'boxes', true);
            $lowStock = StockBalance::query()->whereHas('item', fn (Builder $query): Builder => $query->where('reorder_level', '>', 0)->whereColumn('stock_balances.quantity', '<=', 'items.reorder_level'))->count();
            $modules[] = ['name' => 'Inventory', 'count' => StockBalance::query()->distinct()->count('item_id'), 'detail' => 'Items with stock records', 'route' => 'inventory.dashboard', 'icon' => 'boxes'];
            $queues[] = $this->queue('Stock balances at reorder level', $lowStock, 'inventory.low-stock');
        }

        if ($user->can('production.view') && Route::has('manufacturing.production-orders.index')) {
            $counts = $this->statusCounts(ProductionOrder::query());
            $charts['production'] = $counts;
            $modules[] = $this->module('Manufacturing', $counts, 'Production orders', 'manufacturing.production-orders.index', 'gear');
            $queues[] = $this->queue('Production orders in progress', $counts['in_progress'] ?? 0, 'manufacturing.production-orders.index');
        }

        if ($user->can('hr.view') && Route::has('hr.index')) {
            $counts = $this->statusCounts(Employee::query());
            $cards[] = $this->card('Active employees', $counts['active'] ?? 0, 'Current workforce', 'employees.index', 'people', false);
            $modules[] = $this->module('Human Resources', $counts, 'Employees', 'hr.index', 'people');
            $queues[] = $this->queue('Leave requests pending', LeaveRequest::query()->where('status', 'pending')->count(), 'leave-requests.index');
        }

        if (($user->can('payroll.approve') || $user->can('payroll.finalize')) && Route::has('payroll-runs.index')) {
            $counts = $this->statusCounts(PayrollRun::query());
            $modules[] = $this->module('Payroll', $counts, 'Payroll runs', 'payroll-runs.index', 'wallet2');
            $queues[] = $this->queue('Payroll runs to finalize', $counts['approved'] ?? 0, 'payroll-runs.index');
        }

        if (($user->can('assets.view') || $user->can('accounting.view')) && Route::has('fixed-assets.index')) {
            $counts = $this->statusCounts(Asset::query());
            $modules[] = $this->module('Fixed Assets', $counts, 'Registered assets', 'fixed-assets.index', 'building');
            $cards[] = $this->card('Asset carrying value', Asset::query()->where('status', 'capitalized')->sum('carrying_amount'), 'Capitalized assets', 'fixed-assets.index', 'building', true);
        }

        if ($user->can('accounting.view')) {
            $cash = DB::table('journal_lines as lines')->join('journal_entries as entries', 'entries.id', '=', 'lines.journal_entry_id')->join('accounts', 'accounts.id', '=', 'lines.account_id')
                ->whereIn('entries.status', ['posted', 'reversed'])->where('entries.currency_code', $currency)->whereDate('entries.entry_date', '<=', today())
                ->where(fn ($query) => $query->where('accounts.is_cash', true)->orWhere('accounts.is_bank', true))
                ->selectRaw('COALESCE(SUM(lines.debit - lines.credit), 0) as balance')->value('balance');
            $cards[] = $this->card('Cash & bank', $cash, 'Posted ledger balance today', 'journals.index', 'bank', true);
            $counts = $this->statusCounts(JournalEntry::query());
            $modules[] = $this->module('Finance', $counts, 'Journal entries', 'journals.index', 'bank');
            $queues[] = $this->queue('Journals awaiting review', $counts['submitted'] ?? 0, 'journals.index');
        }

        if ($user->can('users.view')) {
            $cards[] = $this->card('Active users', User::query()->where('status', 'active')->count(), 'Enabled user accounts', 'users.index', 'person-check', false);
        }

        $charts['series'] = $series;

        return compact('cards', 'modules', 'queues', 'charts');
    }

    private function outstanding(Builder $query): float
    {
        return (float) (clone $query)->selectRaw('COALESCE(SUM(CASE WHEN total > paid_amount THEN total - paid_amount ELSE 0 END), 0) as balance')->value('balance');
    }

    /** @return array<string, int> */
    private function statusCounts(Builder $query): array
    {
        return $query->toBase()->select('status')->selectRaw('COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')->map(fn ($count): int => (int) $count)->all();
    }

    /** @param Collection<int, CarbonImmutable> $months
     * @return array<int, float>
     */
    private function monthlyTotals(Builder $query, string $dateColumn, string $amountColumn, Collection $months): array
    {
        $query = clone $query;
        $query->whereBetween($dateColumn, [$months->first()->toDateString(), $months->last()->endOfMonth()->toDateString()]);
        foreach ($months as $index => $month) {
            $query->selectRaw("COALESCE(SUM(CASE WHEN {$dateColumn} >= ? AND {$dateColumn} <= ? THEN {$amountColumn} ELSE 0 END), 0) as month_{$index}", [$month->toDateString(), $month->endOfMonth()->toDateString()]);
        }
        $row = $query->toBase()->first();

        return $months->keys()->map(fn (int $index): float => (float) $row->{'month_'.$index})->all();
    }

    /** @return array{label: string, value: float, detail: string, route: string, icon: string, money: bool} */
    private function card(string $label, int|float|string|null $value, string $detail, string $route, string $icon, bool $money): array
    {
        return ['label' => $label, 'value' => (float) $value, 'detail' => $detail, 'route' => $route, 'icon' => $icon, 'money' => $money];
    }

    /** @param array<string, int> $counts
     * @return array{name: string, count: int, detail: string, route: string, icon: string}
     */
    private function module(string $name, array $counts, string $detail, string $route, string $icon): array
    {
        return ['name' => $name, 'count' => array_sum($counts), 'detail' => $detail, 'route' => $route, 'icon' => $icon];
    }

    /** @return array{label: string, count: int, route: string} */
    private function queue(string $label, int $count, string $route): array
    {
        return compact('label', 'count', 'route');
    }

    /** @return Collection<int, array{at: string, description: string, causer: ?string, event: string}> */
    public function recentActivity(User $user, int $limit = 10): Collection
    {
        if (! $user->can('audit.view')) {
            return collect();
        }

        return DB::table('activity_log')->leftJoin('users', function ($join): void {
            $join->on('users.id', '=', 'activity_log.causer_id')->where('activity_log.causer_type', (new User)->getMorphClass());
        })->orderByDesc('activity_log.created_at')->orderByDesc('activity_log.id')->limit($limit)
            ->get(['activity_log.created_at as at', 'activity_log.description', 'activity_log.event', 'users.name as causer'])
            ->map(fn ($row): array => ['at' => (string) $row->at, 'description' => (string) $row->description, 'event' => (string) ($row->event ?? ''), 'causer' => $row->causer]);
    }
}
