<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentTermManager;
use App\Enums\PaymentTermType;
use App\Enums\RecordStatus;
use App\Http\Requests\PaymentTerms\StorePaymentTermRequest;
use App\Http\Requests\PaymentTerms\UpdatePaymentTermRequest;
use App\Models\PaymentTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentTermController extends Controller
{
    public function __construct(
        private readonly PaymentTermManager $paymentTerms,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PaymentTerm::class);

        return view('payment-terms.index', [
            'paymentTerms' => $this->paymentTerms->paginate(
                $request->only(['search', 'status', 'type'])
            ),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PaymentTerm::class);

        return view('payment-terms.create', [
            'paymentTerm' => new PaymentTerm([
                'type' => PaymentTermType::Net,
                'days' => 30,
                'status' => RecordStatus::Active,
            ]),
        ]);
    }

    public function store(StorePaymentTermRequest $request): RedirectResponse
    {
        $term = $this->paymentTerms->create($request->validated());

        return redirect()
            ->route('payment-terms.index')
            ->with('status', "Payment term {$term->name} created.");
    }

    public function edit(PaymentTerm $paymentTerm): View
    {
        $this->authorize('update', $paymentTerm);

        return view('payment-terms.edit', [
            'paymentTerm' => $paymentTerm,
        ]);
    }

    public function update(UpdatePaymentTermRequest $request, PaymentTerm $paymentTerm): RedirectResponse
    {
        $this->paymentTerms->update($paymentTerm, $request->validated());

        return redirect()
            ->route('payment-terms.index')
            ->with('status', 'Payment term updated.');
    }

    public function destroy(PaymentTerm $paymentTerm): RedirectResponse
    {
        $this->authorize('delete', $paymentTerm);

        $this->paymentTerms->delete($paymentTerm);

        return redirect()
            ->route('payment-terms.index')
            ->with('status', 'Payment term deleted.');
    }

    public function changeStatus(Request $request, PaymentTerm $paymentTerm): RedirectResponse
    {
        $this->authorize('changeStatus', $paymentTerm);

        $request->validate(['status' => ['required', 'string']]);

        $this->paymentTerms->changeStatus(
            $paymentTerm,
            RecordStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Payment term status updated.');
    }

    public function makeDefault(PaymentTerm $paymentTerm): RedirectResponse
    {
        $this->authorize('makeDefault', $paymentTerm);

        $this->paymentTerms->makeDefault($paymentTerm);

        return back()->with('status', "{$paymentTerm->name} is now the default payment term.");
    }
}
