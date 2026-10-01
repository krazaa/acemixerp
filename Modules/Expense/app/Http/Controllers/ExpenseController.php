<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Controllers;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\AccountType;
use App\Enums\SystemAccountRole;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Expense\Enums\ExpenseClaimStatus;
use Modules\Expense\Http\Requests\ManagerApproveExpenseClaimRequest;
use Modules\Expense\Models\ExpenseClaim;
use Modules\Expense\Models\ExpenseEvidence;
use Modules\Expense\Services\ExpenseEvidencePdfHtml;
use Modules\Hr\Models\Employee;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function __construct(private readonly SequenceGenerator $sequences, private readonly JournalPoster $journals, private readonly SystemAccountManager $systemAccounts) {}

    public function index(): View
    {
        return view('expense::index', [
            'claims' => ExpenseClaim::query()
                ->with(['employee:id,first_name,last_name', 'department:id,name'])
                ->orderByDesc('expense_date')
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('expense::create', [
            'employees' => Employee::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'number', 'first_name', 'last_name', 'department_id']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            ...$this->expenseAccountsForForm(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->claimRules(true));

        $lines = array_values($data['lines']);
        $postingAccountIds = $this->resolvePostingAccountIds($lines);
        $employee = Employee::query()->findOrFail($data['employee_id']);
        $claim = DB::transaction(function () use ($data, $lines, $postingAccountIds, $employee, $request): ExpenseClaim {
            $claim = ExpenseClaim::query()->create([
                'number' => $this->sequences->next('expense_claim', (int) now()->format('Y')),
                'employee_id' => $employee->id,
                'department_id' => $data['department_id'] ?? $employee->department_id,
                'expense_account_id' => $postingAccountIds[0],
                'expense_date' => $this->firstExpenseDate($lines),
                'amount' => $this->lineTotal($lines),
                'description' => $data['notes'] ?: 'Expense claim with '.count($lines).' entry line(s).',
                'currency_code' => 'PKR',
                'evidence_text' => $data['evidence_text'] ?? null,
                'status' => ExpenseClaimStatus::Draft,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $this->syncLines($claim, $lines);

            return $claim;
        });

        return redirect()->route('expense.index')->with('status', 'Expense claim saved as draft.');
    }

    public function show(ExpenseClaim $claim): View
    {
        $this->authorize('view', $claim);

        return view('expense::show', [
            'claim' => $claim->load([
                'employee.designation',
                'department.manager',
                'expenseAccount',
                'lines.expenseAccount',
            ]),
            'banks' => BankAccount::query()->active()->orderBy('name')->get(['id', 'name', 'account_number']),
            ...$this->expenseAccountsForForm(),
        ]);
    }

    public function pdf(ExpenseClaim $claim, ExpenseEvidencePdfHtml $evidence): Response
    {
        $this->authorize('view', $claim);
        $claim->load(['employee', 'department', 'lines.expenseAccount', 'creator']);

        return Pdf::loadView('expense::pdf', [
            'claim' => $claim,
            'evidenceHtml' => $evidence->render($claim->evidence_text),
        ])->setPaper('a4')->setOption([
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
        ])->download(Str::slug($claim->number).'.pdf');
    }

    public function edit(ExpenseClaim $claim): View
    {
        $this->authorize('update', $claim);

        return view('expense::edit', [
            'claim' => $claim->load(['employee.designation', 'lines.expenseAccount']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            ...$this->expenseAccountsForForm(),
        ]);
    }

    public function update(ExpenseClaim $claim, Request $request): RedirectResponse
    {
        $this->authorize('update', $claim);

        $data = $request->validate($this->claimRules());
        $lines = array_values($data['lines']);
        $postingAccountIds = $this->resolvePostingAccountIds($lines);
        DB::transaction(function () use ($claim, $data, $lines, $postingAccountIds, $request): void {
            $claim = ExpenseClaim::query()->lockForUpdate()->findOrFail($claim->id);
            $this->authorize('update', $claim);
            $claim->update([
                'department_id' => $data['department_id'] ?? $claim->department_id,
                'evidence_text' => $data['evidence_text'] ?? null,
                'expense_account_id' => $postingAccountIds[0],
                'expense_date' => $this->firstExpenseDate($lines),
                'amount' => $this->lineTotal($lines),
                'description' => $data['notes'] ?: 'Expense claim with '.count($lines).' entry line(s).',
                'updated_by' => $request->user()->id,
            ]);

            $this->syncLines($claim, $lines);
        });

        return redirect()->route('expense.show', $claim)->with('status', 'Draft expense claim updated.');
    }

    public function downloadEvidence(ExpenseClaim $claim, ExpenseEvidence $evidence): BinaryFileResponse
    {
        abort_unless($evidence->expense_claim_id === $claim->id, 404);
        abort_unless(Storage::exists($evidence->path), 404, 'Evidence file is unavailable.');

        return Storage::download($evidence->path, $evidence->original_name);
    }

    public function previewEvidence(ExpenseClaim $claim, ExpenseEvidence $evidence): StreamedResponse
    {
        abort_unless($evidence->expense_claim_id === $claim->id, 404);
        abort_unless(str_starts_with($evidence->mime_type, 'image/') && Storage::exists($evidence->path), 404);

        return Storage::response($evidence->path, $evidence->original_name, ['Content-Disposition' => 'inline']);
    }

    public function submit(ExpenseClaim $claim, Request $request): RedirectResponse
    {
        $this->authorize('submit', $claim);
        DB::transaction(function () use ($claim, $request): void {
            $claim = ExpenseClaim::query()->lockForUpdate()->findOrFail($claim->id);
            $this->authorize('submit', $claim);
            $claim->update(['status' => ExpenseClaimStatus::Submitted, 'updated_by' => $request->user()->id]);
        });

        return back()->with('status', 'Claim submitted for Manager approval.');
    }

    public function managerApprove(ExpenseClaim $claim, ManagerApproveExpenseClaimRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($claim, $data, $request): void {
            $claim = ExpenseClaim::query()->lockForUpdate()->findOrFail($claim->id);
            $this->authorize('managerApprove', $claim);
            $reviewsByLineId = collect($data['line_reviews'])->keyBy(fn (array $review): int => (int) $review['id']);
            $lines = $claim->lines()->lockForUpdate()->get();

            $approvedAmount = '0.0000';
            $rejectionReasons = [];

            foreach ($lines as $line) {
                /** @var array{id: int, decision: string, rejection_reason?: string|null} $review */
                $review = $reviewsByLineId->get($line->id);
                $approved = $review['decision'] === 'approved';
                $reason = $approved ? null : $review['rejection_reason'];
                $deductionAmount = $approved ? (string) ($review['deduction_amount'] ?? '0') : '0.0000';
                $deductionReason = $approved ? ($review['deduction_reason'] ?? null) : null;

                $line->update([
                    'manager_decision' => $review['decision'],
                    'manager_deduction_amount' => $deductionAmount,
                    'manager_deduction_reason' => $deductionReason,
                    'manager_rejection_reason' => $reason,
                    'manager_reviewed_by' => $request->user()->id,
                    'manager_reviewed_at' => now(),
                ]);

                if ($approved) {
                    $approvedAmount = bcadd($approvedAmount, bcsub((string) $line->amount, $deductionAmount, 4), 4);

                    if (bccomp($deductionAmount, '0', 4) > 0) {
                        $rejectionReasons[] = "Entry {$line->line_number} deduction: {$deductionReason}";
                    }
                } else {
                    $rejectionReasons[] = "Entry {$line->line_number}: {$reason}";
                }
            }

            $deductionAmount = bcsub((string) $claim->amount, $approvedAmount, 4);
            $claim->update([
                'status' => ExpenseClaimStatus::ManagerApproved,
                'manager_deduction_amount' => $deductionAmount,
                'manager_deduction_reason' => $rejectionReasons === [] ? null : implode("\n", $rejectionReasons),
                'approved_amount' => $approvedAmount,
                'manager_approved_by' => $request->user()->id,
                'manager_approved_at' => now(),
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', 'Manager approved the claim. It is ready for CEO approval.');
    }

    public function ceoApprove(ExpenseClaim $claim, Request $request): RedirectResponse
    {
        $this->authorize('ceoApprove', $claim);
        DB::transaction(function () use ($claim, $request): void {
            $claim = ExpenseClaim::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($claim->id);
            $this->authorize('ceoApprove', $claim);
            $payable = $this->systemAccounts->resolve(SystemAccountRole::EmployeeExpensePayable);
            abort_if($payable === null, 422, 'Employee Expense Payable system account must be mapped before CEO approval.');
            $clearing = $this->systemAccounts->resolve(SystemAccountRole::ExpenseClaimsClearing);
            abort_if($clearing === null, 422, 'Expense Claims Clearing system account must be mapped before CEO approval.');

            $approvedAmount = $claim->approvedAmount();
            abort_if(bccomp($approvedAmount, '0', 4) <= 0, 422, 'At least one expense line must be approved before CEO approval.');

            $journalLines = [new JournalLineData(
                accountId: $clearing->id,
                debit: $approvedAmount,
                credit: '0.0000',
                memo: "Expense claim clearing {$claim->number}",
                departmentId: $claim->department_id,
                employeeId: $claim->employee_id,
            ), new JournalLineData(
                accountId: $payable->id,
                debit: '0.0000',
                credit: $approvedAmount,
                memo: "Expense claim payable {$claim->number}",
                departmentId: $claim->department_id,
                employeeId: $claim->employee_id,
            )];

            $journal = $this->journals->post(new JournalEntryData(
                entryDate: now(),
                description: "Expense Claim {$claim->number}",
                lines: $journalLines,
                reference: $claim->number,
                currencyCode: $claim->currency_code,
            ), [
                'source_type' => ExpenseClaim::class,
                'source_id' => $claim->id,
                'user_id' => $request->user()->id,
            ]);

            $claim->update([
                'status' => ExpenseClaimStatus::CeoApproved,
                'ceo_approved_by' => $request->user()->id,
                'ceo_approved_at' => now(),
                'journal_entry_id' => $journal->id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', 'CEO approved the claim and posted Expense Claims Clearing and Employee Expense Payable entries to GL.');
    }

    public function ceoReject(ExpenseClaim $claim, Request $request): RedirectResponse
    {
        $this->authorize('ceoReject', $claim);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        DB::transaction(function () use ($claim, $request, $data): void {
            $claim = ExpenseClaim::query()->lockForUpdate()->findOrFail($claim->id);
            $this->authorize('ceoReject', $claim);
            $claim->update([
                'status' => ExpenseClaimStatus::Rejected,
                'rejection_reason' => $data['rejection_reason'],
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', 'CEO rejected the claim.');
    }

    public function reimburse(ExpenseClaim $claim, Request $request): RedirectResponse
    {
        $this->authorize('reimburse', $claim);
        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'expense_account_ids' => ['required', 'array'],
            'expense_account_ids.*' => ['required', 'integer', 'exists:accounts,id'],
        ]);
        DB::transaction(function () use ($claim, $request, $data): void {
            $claim = ExpenseClaim::query()->lockForUpdate()->findOrFail($claim->id);
            $this->authorize('reimburse', $claim);
            $bank = BankAccount::query()->active()->findOrFail($data['bank_account_id']);
            $payable = $this->systemAccounts->resolve(SystemAccountRole::EmployeeExpensePayable);
            abort_if($payable === null, 422, 'Employee Expense Payable system account must be mapped before reimbursement.');
            $clearing = $this->systemAccounts->resolve(SystemAccountRole::ExpenseClaimsClearing);
            abort_if($clearing === null, 422, 'Expense Claims Clearing system account must be mapped before reimbursement.');

            $claim->loadMissing('lines');
            $approvedAmount = $claim->approvedAmount();
            abort_if(bccomp($approvedAmount, '0', 4) <= 0, 422, 'The claim has no approved amount to reimburse.');
            $journalLines = [
                new JournalLineData($payable->id, $approvedAmount, '0.0000', "Expense payable settlement: {$claim->number}", departmentId: $claim->department_id, employeeId: $claim->employee_id),
                new JournalLineData($bank->gl_account_id, '0.0000', $approvedAmount, "Bank payment: {$claim->number}", departmentId: $claim->department_id, employeeId: $claim->employee_id),
            ];

            $usesExpenseClaimsClearing = $claim->journalEntry()
                ->whereHas('lines', fn ($query) => $query->where('account_id', $clearing->id))
                ->exists();

            if ($usesExpenseClaimsClearing) {
                $approvedLineIds = $claim->lines
                    ->where('manager_decision', 'approved')
                    ->pluck('id')
                    ->map(fn (int $id): int => $id)
                    ->sort()
                    ->values();
                $selectedAccountIds = collect($data['expense_account_ids'])
                    ->mapWithKeys(fn (int|string $accountId, int|string $lineId): array => [(int) $lineId => (int) $accountId]);

                abort_if(
                    $selectedAccountIds->keys()->map(fn (int $id): int => $id)->sort()->values()->all() !== $approvedLineIds->all(),
                    422,
                    'Select a posting Expense account for every approved expense entry.',
                );
                $this->resolvePostingAccountIds(
                    $selectedAccountIds
                        ->map(fn (int $accountId): array => ['expense_account_id' => $accountId])
                        ->all(),
                );
                $expenseLines = $this->approvedExpenseLines($claim, $selectedAccountIds->all());
                abort_if($expenseLines === [] || bccomp($approvedAmount, '0', 4) <= 0, 422, 'At least one expense line must be approved before reimbursement.');

                $journalLines[] = new JournalLineData(
                    $clearing->id,
                    '0.0000',
                    $approvedAmount,
                    "Expense claim clearing release: {$claim->number}",
                    departmentId: $claim->department_id,
                    employeeId: $claim->employee_id,
                );

                foreach ($expenseLines as $expenseLine) {
                    $journalLines[] = new JournalLineData(
                        accountId: $expenseLine['account_id'],
                        debit: $expenseLine['amount'],
                        credit: '0.0000',
                        memo: "Expense claim {$claim->number}, entry {$expenseLine['line_number']}",
                        departmentId: $claim->department_id,
                        employeeId: $claim->employee_id,
                    );
                }
            }

            $this->journals->post(new JournalEntryData(now(), "Expense reimbursement {$claim->number}", $journalLines, ($data['payment_reference'] ?? null) ?: $claim->number, $claim->currency_code), ['source_type' => ExpenseClaim::class, 'source_id' => $claim->id, 'user_id' => $request->user()->id]);
            if (isset($selectedAccountIds)) {
                foreach ($claim->lines as $line) {
                    if ($line->manager_decision === 'approved') {
                        $line->update(['expense_account_id' => $selectedAccountIds[$line->id]]);
                    }
                }
            }
            $claim->update(['status' => ExpenseClaimStatus::Reimbursed, 'reimbursement_bank_account_id' => $bank->id, 'payment_reference' => $data['payment_reference'] ?? null, 'reimbursed_at' => now(), 'reimbursed_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        });

        return back()->with('status', 'Reimbursement payment posted.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function claimRules(bool $includeEmployee = false): array
    {
        $rules = [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.expense_date' => ['required', 'date'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.reference' => ['nullable', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'numeric', 'gt:0'],
            'evidence_text' => ['nullable', 'string', 'max:10000'],
        ];

        if (! $includeEmployee) {
            unset($rules['employee_id']);
        }

        return $rules;
    }

    /**
     * @param  array<int, array{expense_date: string, expense_account_id: int|string, reference?: string|null, amount: int|float|string}>  $lines
     */
    private function syncLines(ExpenseClaim $claim, array $lines): void
    {
        $claim->lines()->delete();

        foreach (array_values($lines) as $index => $line) {
            $claim->lines()->create([
                'line_number' => $index + 1,
                'expense_date' => $line['expense_date'],
                'expense_account_id' => $line['expense_account_id'],
                'reference' => $line['reference'] ?? null,
                'amount' => $line['amount'],
            ]);
        }
    }

    /**
     * @param  array<int, array{amount: int|float|string}>  $lines
     */
    private function lineTotal(array $lines): string
    {
        return array_reduce(
            $lines,
            fn (string $total, array $line): string => bcadd($total, (string) $line['amount'], 4),
            '0.0000',
        );
    }

    /**
     * @param  array<int, array{expense_date: string}>  $lines
     */
    private function firstExpenseDate(array $lines): string
    {
        return collect($lines)->min('expense_date');
    }

    /**
     * @param  array<int, int>  $expenseAccountIdsByLine
     * @return array<int, array{account_id: int, amount: string, line_number: int}>
     */
    private function approvedExpenseLines(ExpenseClaim $claim, array $expenseAccountIdsByLine = []): array
    {
        $entries = [];

        foreach ($claim->lines as $line) {
            if ($line->manager_decision !== 'approved') {
                continue;
            }

            $amount = bcsub((string) $line->amount, (string) $line->manager_deduction_amount, 4);
            if (bccomp($amount, '0', 4) <= 0) {
                continue;
            }

            $expenseAccountId = $expenseAccountIdsByLine[$line->id] ?? $line->expense_account_id;
            $entries[] = [
                'account_id' => (int) $expenseAccountId,
                'amount' => $amount,
                'line_number' => $line->line_number,
            ];
        }

        return $entries;
    }

    /**
     * @return array{expenseAccounts: Collection<int, Account>}
     */
    private function expenseAccountsForForm(): array
    {
        $accounts = Account::query()
            ->active()
            ->postable()
            ->ofType(AccountType::Expense)
            ->orderBy('code')
            ->get(['id', 'parent_id', 'code', 'name', 'is_postable']);

        return [
            'expenseAccounts' => $accounts->values(),
        ];
    }

    /**
     * @param  array<int, array{expense_account_id: int|string}>  $lines
     * @return array<int, int>
     */
    private function resolvePostingAccountIds(array $lines): array
    {
        $accountIds = collect($lines)
            ->pluck('expense_account_id')
            ->filter()
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        $accounts = Account::query()
            ->postable()
            ->ofType(AccountType::Expense)
            ->whereKey($accountIds)
            ->get(['id'])
            ->keyBy('id');

        abort_if(
            $accounts->count() !== $accountIds->count(),
            422,
            'Each expense entry must use an active, postable Expense account. Header accounts such as 6000 — Operating Expenses cannot receive journal postings.',
        );

        return array_map(fn (array $line): int => (int) $line['expense_account_id'], $lines);
    }
}
