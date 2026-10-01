<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AccountManager;
use App\Contracts\JournalManager;
use App\Contracts\JournalPoster;
use App\Data\JournalEntryData;
use App\Http\Requests\Journals\ReverseJournalEntryRequest;
use App\Http\Requests\Journals\StoreJournalEntryRequest;
use App\Http\Requests\Journals\UpdateJournalEntryRequest;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Department;
use App\Models\JournalEntry;
use App\Models\Organization;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Hr\Models\Employee;

class JournalEntryController extends Controller
{
    public function __construct(
        private readonly JournalManager $journals,
        private readonly JournalPoster $poster,
        private readonly AccountManager $accounts,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', JournalEntry::class);

        return view('journals.index', [
            'journals' => $this->journals->paginate(
                $request->only(['search', 'status', 'from', 'to'])
            ),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', JournalEntry::class);

        return view('journals.create', $this->formDependencies() + [
            'entry' => new JournalEntry([
                'entry_date' => now()->toDateString(),
                'currency_code' => Organization::current()->currency_code,
            ]),
        ]);
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $entry = $this->journals->createDraft(
            JournalEntryData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('journals.show', $entry)
            ->with('status', "Journal {$entry->number} created as draft.");
    }

    public function show(JournalEntry $journal): View
    {
        $this->authorize('view', $journal);

        return view('journals.show', [
            'entry' => $journal->load([
                'lines.account', 'lines.costCenter', 'lines.department',
                'lines.customer', 'lines.vendor', 'lines.employee',
                'creator', 'submitter', 'approver', 'poster',
                'period', 'reversedBy', 'reverses',
            ]),
        ]);
    }

    public function edit(JournalEntry $journal): View
    {
        $this->authorize('update', $journal);

        return view('journals.edit', $this->formDependencies() + [
            'entry' => $journal->load('lines'),
        ]);
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journal): RedirectResponse
    {
        $this->journals->update($journal, JournalEntryData::fromRequest($request));

        return redirect()->route('journals.show', $journal)
            ->with('status', 'Journal updated.');
    }

    public function destroy(JournalEntry $journal): RedirectResponse
    {
        $this->authorize('delete', $journal);

        $this->journals->delete($journal);

        return redirect()->route('journals.index')
            ->with('status', 'Journal deleted.');
    }

    public function submit(JournalEntry $journal): RedirectResponse
    {
        $this->authorize('submit', $journal);

        $this->journals->submit($journal, auth()->id());

        return back()->with('status', 'Journal submitted for approval.');
    }

    public function approve(JournalEntry $journal): RedirectResponse
    {
        $this->authorize('approve', $journal);

        $this->journals->approve($journal, auth()->id());

        return back()->with('status', 'Journal approved.');
    }

    public function reject(Request $request, JournalEntry $journal): RedirectResponse
    {
        $this->authorize('reject', $journal);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->journals->reject($journal, auth()->id(), $request->string('reason')->toString());

        return back()->with('status', 'Journal rejected.');
    }

    public function post(JournalEntry $journal): RedirectResponse
    {
        $this->authorize('post', $journal);

        $this->poster->postExisting($journal, auth()->id());

        return back()->with('status', "Journal {$journal->number} posted.");
    }

    public function reverse(ReverseJournalEntryRequest $request, JournalEntry $journal): RedirectResponse
    {
        $reversal = $this->poster->reverse(
            $journal,
            auth()->id(),
            $request->string('reason')->toString(),
        );

        return redirect()->route('journals.show', $reversal)
            ->with('status', "Reversal {$reversal->number} created.");
    }

    public function cancel(Request $request, JournalEntry $journal): RedirectResponse
    {
        $this->authorize('cancel', $journal);

        $this->journals->cancel($journal, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Journal cancelled.');
    }

    /** @return array<string, mixed> */
    private function formDependencies(): array
    {
        return [
            'accounts' => $this->accounts->allPostable(),
            'costCenters' => CostCenter::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'departments' => Department::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'customers' => Customer::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'employees' => Employee::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
        ];
    }
}
