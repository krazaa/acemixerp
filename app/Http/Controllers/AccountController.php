<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AccountManager;
use App\Enums\AccountType;
use App\Enums\RecordStatus;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(private readonly AccountManager $accounts) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Account::class);

        return view('accounts.index', [
            'accounts' => $this->accounts->paginate(
                $request->only(['search', 'type', 'status', 'postable'])
            ),
            'types' => AccountType::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Account::class);

        return view('accounts.create', [
            'account' => new Account(['status' => RecordStatus::Active, 'is_postable' => true]),
            'parents' => $this->accounts->allPostable(),
            'types' => AccountType::cases(),
            'nextCodeByType' => collect(AccountType::cases())
                ->mapWithKeys(fn ($t) => [$t->value => $this->accounts->nextCodeFor($t)])
                ->all(),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = $this->accounts->create($request->validated());

        return redirect()->route('accounts.index')
            ->with('status', "Account {$account->code} — {$account->name} created.");
    }

    public function edit(Account $account): View
    {
        $this->authorize('update', $account);

        return view('accounts.edit', [
            'account' => $account->load('parent'),
            'parents' => $this->accounts->allPostable()->where('id', '!=', $account->id),
            'types' => AccountType::cases(),
            'locked' => $account->isLocked(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->accounts->update($account, $request->validated());

        return redirect()->route('accounts.index')->with('status', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $this->accounts->delete($account);

        return redirect()->route('accounts.index')->with('status', 'Account deleted.');
    }

    public function changeStatus(Request $request, Account $account): RedirectResponse
    {
        $this->authorize('changeStatus', $account);

        $request->validate(['status' => ['required', 'string']]);

        $this->accounts->changeStatus(
            $account,
            RecordStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Account status updated.');
    }
}
