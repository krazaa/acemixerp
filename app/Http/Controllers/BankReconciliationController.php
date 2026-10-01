<?php

namespace App\Http\Controllers;

use App\Contracts\BankAccountManager;
use App\Contracts\BankReconciliationManager;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    public function __construct(
        private readonly BankReconciliationManager $reconciliations,
        private readonly BankAccountManager $bankAccounts,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BankReconciliation::class);

        return view('bank-reconciliation.index', [
            'accounts' => $this->bankAccounts->allActive(),
            'account' => $request->integer('bank_account_id')
                ? BankAccount::query()->findOrFail($request->integer('bank_account_id'))
                : $this->bankAccounts->allActive()->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', BankReconciliation::class);

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'statement_date' => ['required', 'date'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
        ]);

        $account = BankAccount::query()->findOrFail($validated['bank_account_id']);

        $recon = $this->reconciliations->openFor(
            $account,
            Carbon::parse($validated['statement_date']),
            ['amount' => $validated['opening_balance'] ?? 0],
            ['amount' => $validated['closing_balance'] ?? 0],
        );

        return redirect()->route('bank-reconciliation.show', $recon);
    }

    public function show(BankReconciliation $bankReconciliation): View
    {
        $this->authorize('view', $bankReconciliation);

        return view('bank-reconciliation.show', [
            'reconciliation' => $bankReconciliation->load(['bankAccount.bank', 'bankAccount.glAccount', 'statementLines']),
            'unmatchedLedger' => $this->reconciliations->unmatchedLedgerEntries($bankReconciliation),
        ]);
    }

    public function import(Request $request, BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorize('create', BankReconciliation::class);

        $validated = $request->validate([
            'statement_file' => ['required_without:rows', 'file', 'mimes:csv,txt', 'max:5120'],
            'rows' => ['required_without:statement_file', 'array', 'min:1'],
            'rows.*.transaction_date' => ['required', 'date'],
            'rows.*.reference' => ['nullable', 'string', 'max:64'],
            'rows.*.description' => ['required', 'string', 'max:500'],
            'rows.*.debit' => ['nullable', 'numeric'],
            'rows.*.credit' => ['nullable', 'numeric'],
        ]);

        $rows = $request->hasFile('statement_file')
            ? $this->parseStatementCsv($request->file('statement_file'))
            : $validated['rows'];

        $count = $this->reconciliations->importStatementLines($bankReconciliation, $rows);

        return back()->with('status', "{$count} statement line(s) imported.");
    }

    /** @return array<int, array{transaction_date: string, reference: ?string, description: string, debit: string, credit: string}> */
    private function parseStatementCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages(['statement_file' => 'The statement file could not be read.']);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            throw ValidationException::withMessages(['statement_file' => 'The statement file is empty.']);
        }

        $columns = array_flip(array_map(
            fn (string $column): string => strtolower(trim(ltrim($column, "\xEF\xBB\xBF"))),
            $header,
        ));
        $dateColumn = $this->findColumn($columns, ['transaction_date', 'date']);
        $descriptionColumn = $this->findColumn($columns, ['description', 'narration']);
        $debitColumn = $this->findColumn($columns, ['debit', 'withdrawal']);
        $creditColumn = $this->findColumn($columns, ['credit', 'deposit']);
        $referenceColumn = $this->findColumn($columns, ['reference', 'ref', 'transaction_reference']);

        if ($dateColumn === null || $descriptionColumn === null || ($debitColumn === null && $creditColumn === null)) {
            fclose($handle);

            throw ValidationException::withMessages([
                'statement_file' => 'CSV headings must include Date, Description (or Narration), and Debit and/or Credit.',
            ]);
        }

        $rows = [];
        $lineNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if (count(array_filter($row, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if (count($rows) >= 10000) {
                fclose($handle);

                throw ValidationException::withMessages(['statement_file' => 'A statement may contain at most 10,000 rows.']);
            }

            $rawDate = trim((string) ($row[$dateColumn] ?? ''));
            if ($rawDate === '') {
                fclose($handle);

                throw ValidationException::withMessages(['statement_file' => "Date is required on CSV row {$lineNumber}."]);
            }

            try {
                $transactionDate = Carbon::parse($rawDate)->toDateString();
            } catch (\Throwable) {
                fclose($handle);

                throw ValidationException::withMessages(['statement_file' => "Invalid date on CSV row {$lineNumber}."]);
            }

            $debit = $this->parseAmount($debitColumn === null ? null : ($row[$debitColumn] ?? null), $lineNumber);
            $credit = $this->parseAmount($creditColumn === null ? null : ($row[$creditColumn] ?? null), $lineNumber);
            $description = trim((string) ($row[$descriptionColumn] ?? ''));
            if ($description === '') {
                fclose($handle);

                throw ValidationException::withMessages(['statement_file' => "Description is required on CSV row {$lineNumber}."]);
            }
            $reference = $referenceColumn === null
                ? null
                : (trim((string) ($row[$referenceColumn] ?? '')) ?: null);

            $rows[] = [
                'transaction_date' => $transactionDate,
                'reference' => $reference,
                'description' => $description,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }
        fclose($handle);

        if ($rows === []) {
            throw ValidationException::withMessages(['statement_file' => 'The statement file has no transaction rows.']);
        }

        return $rows;
    }

    /** @param array<string, int> $columns */
    private function findColumn(array $columns, array $names): ?int
    {
        foreach ($names as $name) {
            if (array_key_exists($name, $columns)) {
                return $columns[$name];
            }
        }

        return null;
    }

    private function parseAmount(mixed $value, int $lineNumber): string
    {
        $amount = str_replace([',', ' '], '', trim((string) $value));
        if ($amount === '') {
            return '0.0000';
        }
        if (! is_numeric($amount)) {
            throw ValidationException::withMessages(['statement_file' => "Invalid amount on CSV row {$lineNumber}."]);
        }

        return number_format((float) $amount, 4, '.', '');
    }

    public function match(Request $request, BankStatementLine $bankStatementLine): RedirectResponse
    {
        $this->authorize('match', $bankStatementLine->reconciliation);

        $request->validate(['journal_entry_id' => ['required', 'integer', 'exists:journal_entries,id']]);

        $this->reconciliations->match(
            $bankStatementLine,
            $request->integer('journal_entry_id'),
            auth()->id(),
        );

        return back()->with('status', 'Matched.');
    }

    public function unmatch(BankStatementLine $bankStatementLine): RedirectResponse
    {
        $this->authorize('match', $bankStatementLine->reconciliation);

        $this->reconciliations->unmatch($bankStatementLine);

        return back()->with('status', 'Unmatched.');
    }

    public function complete(BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->authorize('complete', $bankReconciliation);

        $this->reconciliations->complete($bankReconciliation, auth()->id());

        return back()->with('status', 'Reconciliation completed.');
    }
}
