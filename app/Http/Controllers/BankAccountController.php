<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AccountManager;
use App\Contracts\BankAccountManager;
use App\Contracts\BankManager;
use App\Enums\BankAccountType;
use App\Enums\RecordStatus;
use App\Http\Requests\BankAccounts\StoreBankAccountRequest;
use App\Http\Requests\BankAccounts\UpdateBankAccountRequest;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function __construct(
        private readonly BankAccountManager $bankAccounts,
        private readonly BankManager $banks,
        private readonly AccountManager $accounts,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BankAccount::class);

        return view('bank-accounts.index', [
            'bankAccounts' => $this->bankAccounts->paginate(
                $request->only(['search', 'status', 'bank_id'])
            ),
            'banks' => $this->banks->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BankAccount::class);

        return view('bank-accounts.create', [
            'bankAccount' => new BankAccount([
                'status' => RecordStatus::Active,
                'account_type' => BankAccountType::Current,
                'currency_code' => Organization::current()->currency_code,
            ]),
            'banks' => $this->banks->allActive(),
            'glAccounts' => $this->assetAccounts(),
        ]);
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        $account = $this->bankAccounts->create($request->validated());

        return redirect()->route('bank-accounts.show', $account)
            ->with('status', "Bank account {$account->name} created.");
    }

    public function show(BankAccount $bankAccount): View
    {
        $this->authorize('view', $bankAccount);

        return view('bank-accounts.show', [
            'bankAccount' => $bankAccount->load(['bank', 'glAccount']),   // ← returns $bankAccount
            'balance' => $this->bankAccounts->currentBalance($bankAccount),
        ]);
    }

    public function edit(BankAccount $bankAccount): View
    {
        $this->authorize('update', $bankAccount);

        return view('bank-accounts.edit', [
            'bankAccount' => $bankAccount,
            'banks' => $this->banks->allActive(),
            'glAccounts' => $this->assetAccounts(),
        ]);
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): RedirectResponse
    {
        $this->bankAccounts->update($bankAccount, $request->validated());

        return redirect()->route('bank-accounts.show', $bankAccount)
            ->with('status', 'Bank account updated.');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('delete', $bankAccount);
        $this->bankAccounts->delete($bankAccount);

        return redirect()->route('bank-accounts.index')->with('status', 'Bank account deleted.');
    }

    public function makeDefault(BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('makeDefault', $bankAccount);
        $this->bankAccounts->makeDefault($bankAccount);

        return back()->with('status', "{$bankAccount->name} is now the default bank account.");
    }

    private function assetAccounts(): Collection
    {
        return Account::query()
            ->where('type', 'asset')
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }
}
