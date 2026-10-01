<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\Organization;
use App\Services\Settings\DashboardMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardMetricsService $metrics,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')]]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $data['month'] ?? now()->format('Y-m'));
        $organization = Organization::current();

        return view('dashboard', [
            'organization' => $organization,
            'month' => $month,
            'financialYear' => $this->currentFinancialYear(),
            'dashboard' => $this->metrics->forUser($user, $month, $organization->currency_code),
            'recentActivity' => $this->metrics->recentActivity($user, limit: 10),
        ]);
    }

    private function currentFinancialYear(): ?FinancialYear
    {
        $financialYearId = cache()->remember(
            'financial_year.current_id',
            now()->addMinutes(10),
            fn (): ?int => FinancialYear::query()->where('is_current', true)->value('id'),
        );

        return $financialYearId ? FinancialYear::query()->find($financialYearId) : null;
    }
}
