<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\SystemAccountRole;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Hr\Http\Requests\StorePayrollRunRequest;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\PayrollRun;
use Modules\Hr\Services\PakistanSalaryTaxCalculator;

class PayrollRunController extends Controller
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly PakistanSalaryTaxCalculator $taxCalculator,
        private readonly JournalPoster $journalPoster,
        private readonly SystemAccountManager $systemAccounts,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('hr::payroll-runs.index', ['payrollRuns' => PayrollRun::query()->withCount('lines')->latest('period_end')->paginate()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        abort_unless(auth()->user()?->can('payroll.approve'), 403);

        return view('hr::payroll-runs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePayrollRunRequest $request): RedirectResponse
    {
        $run = DB::transaction(function () use ($request): PayrollRun {
            $run = PayrollRun::query()->create(['number' => $this->sequences->next('payroll'), 'period_start' => $request->date('period_start'), 'period_end' => $request->date('period_end'), 'status' => 'draft']);
            $gross = '0.0000';
            $deductions = '0.0000';
            $taxYear = $request->date('period_end')->month <= 6
                ? $request->date('period_end')->year
                : $request->date('period_end')->year + 1;

            foreach (Employee::query()->where('status', 'active')->with(['contracts', 'salaryStructure'])->get() as $employee) {
                $contract = $employee->contracts->firstWhere('is_current', true);
                if (! $contract) {
                    continue;
                }

                $basicSalary = (string) $contract->monthly_salary;
                $allowances = (string) ($employee->salaryStructure?->allowances ?? '0.0000');
                $otherDeductions = (string) ($employee->salaryStructure?->deductions ?? '0.0000');
                $grossPay = bcadd($basicSalary, $allowances, 4);
                $taxDeduction = $this->taxCalculator->monthlyTax($grossPay, $taxYear);
                $netPay = bcsub(bcsub($grossPay, $otherDeductions, 4), $taxDeduction, 4);

                $run->lines()->create([
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'allowances' => $allowances,
                    'deductions' => $otherDeductions,
                    'tax_deduction' => $taxDeduction,
                    'gross_pay' => $grossPay,
                    'net_pay' => $netPay,
                ]);
                $gross = bcadd($gross, $grossPay, 4);
                $deductions = bcadd($deductions, bcadd($otherDeductions, $taxDeduction, 4), 4);
            }
            $run->update(['gross_total' => $gross, 'deduction_total' => $deductions, 'net_total' => bcsub($gross, $deductions, 4)]);

            return $run;
        });

        return redirect()->route('payroll-runs.index')->with('status', "Payroll {$run->number} created.");
    }

    public function approve(PayrollRun $payrollRun): RedirectResponse
    {
        abort_unless(auth()->user()?->can('payroll.approve'), 403);
        $payrollRun->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back();
    }

    public function finalize(PayrollRun $payrollRun): RedirectResponse
    {
        abort_unless(auth()->user()?->can('payroll.finalize'), 403);

        DB::transaction(function () use ($payrollRun): void {
            /** @var PayrollRun $locked */
            $locked = PayrollRun::query()->lockForUpdate()->findOrFail($payrollRun->id);

            if ($locked->status !== 'approved') {
                throw BusinessRuleException::make("Payroll {$locked->number} must be approved before finalization.");
            }

            $expenseAccount = $this->systemAccounts->resolve(SystemAccountRole::PayrollExpense);
            $payableAccount = $this->systemAccounts->resolve(SystemAccountRole::PayrollPayable);

            if (! $expenseAccount || ! $payableAccount) {
                throw BusinessRuleException::make('Map the Payroll Expense and Payroll Payable system accounts before finalizing payroll.');
            }

            $journal = $this->journalPoster->post(new JournalEntryData(
                entryDate: $locked->period_end,
                description: "Payroll accrual {$locked->number}",
                lines: [
                    new JournalLineData($expenseAccount->id, (string) $locked->net_total, '0.0000', "Payroll expense — {$locked->number}"),
                    new JournalLineData($payableAccount->id, '0.0000', (string) $locked->net_total, "Payroll payable — {$locked->number}"),
                ],
                reference: $locked->number,
            ), ['source_type' => PayrollRun::class, 'source_id' => $locked->id, 'user_id' => auth()->id()]);

            $locked->update(['status' => 'finalized', 'journal_entry_id' => $journal->id, 'finalized_by' => auth()->id(), 'finalized_at' => now()]);
        });

        return back();
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('hr::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('hr::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
