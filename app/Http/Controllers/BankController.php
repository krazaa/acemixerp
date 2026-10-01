<?php

namespace App\Http\Controllers;

use App\Contracts\BankManager;
use App\Enums\RecordStatus;
use App\Http\Requests\Banks\StoreBankRequest;
use App\Http\Requests\Banks\UpdateBankRequest;
use App\Models\Bank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankController extends Controller
{
    public function __construct(private readonly BankManager $banks) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Bank::class);

        return view('banks.index', [
            'banks' => $this->banks->paginate($request->only(['search', 'status'])),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Bank::class);

        return view('banks.create', [
            'bank' => new Bank(['status' => RecordStatus::Active, 'country' => 'US']),
        ]);
    }

    public function store(StoreBankRequest $request): RedirectResponse
    {
        $bank = $this->banks->create($request->validated());

        return redirect()->route('banks.show', $bank)
            ->with('status', "Bank {$bank->name} created.");
    }

    public function show(Bank $bank): View
    {
        $this->authorize('view', $bank);

        return view('banks.show', [
            'bank' => $bank->load(['addresses', 'creator', 'updater']),
        ]);
    }

    public function edit(Bank $bank): View
    {
        $this->authorize('update', $bank);

        return view('banks.edit', ['bank' => $bank->load('addresses')]);
    }

    public function update(UpdateBankRequest $request, Bank $bank): RedirectResponse
    {
        $this->banks->update($bank, $request->validated());

        return redirect()->route('banks.show', $bank)->with('status', 'Bank updated.');
    }

    public function destroy(Bank $bank): RedirectResponse
    {
        $this->authorize('delete', $bank);
        $this->banks->delete($bank);

        return redirect()->route('banks.index')->with('status', 'Bank deleted.');
    }

    public function changeStatus(Request $request, Bank $bank): RedirectResponse
    {
        $this->authorize('changeStatus', $bank);
        $request->validate(['status' => ['required', 'string']]);
        $this->banks->changeStatus($bank, RecordStatus::from((string) $request->input('status')));

        return back()->with('status', 'Bank status updated.');
    }
}
