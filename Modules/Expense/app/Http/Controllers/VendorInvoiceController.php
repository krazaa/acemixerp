<?php

namespace Modules\Expense\Http\Controllers;

use App\Contracts\SequenceGenerator;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\DocumentSequence;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Expense\Services\ExpenseEvidencePdfHtml;
use Modules\Expense\Services\VendorInvoiceWorkflowService;
use Modules\Procurement\Models\VendorInvoice;

final class VendorInvoiceController extends Controller
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly VendorInvoiceWorkflowService $workflow,
    ) {}

    public function index(): View
    {
        return view('expense::vendor-invoices.index', [
            'invoices' => VendorInvoice::query()->with('vendor:id,code,name')->latest('invoice_date')->latest('id')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('expense::vendor-invoices.create', [
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'expenseAccounts' => Account::query()->active()->postable()->ofType(AccountType::Expense)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function show(VendorInvoice $vendorInvoice): View
    {
        Gate::authorize('view', $vendorInvoice);

        return view('expense::vendor-invoices.show', [
            'invoice' => $vendorInvoice->load(['vendor', 'lines.debitAccount', 'journalEntry', 'paymentAllocations.payment.journalEntry']),
            'expenseAccounts' => Account::query()->active()->postable()->ofType(AccountType::Expense)->orderBy('code')->get(['id', 'code', 'name']),
            'bankAccounts' => BankAccount::query()->active()->whereNotNull('gl_account_id')->orderBy('name')->get(['id', 'name', 'account_number']),
        ]);
    }

    public function pdf(VendorInvoice $vendorInvoice, ExpenseEvidencePdfHtml $html): Response
    {
        Gate::authorize('view', $vendorInvoice);

        return Pdf::loadView('expense::vendor-invoices.pdf', [
            'invoice' => $vendorInvoice->load(['vendor', 'lines.debitAccount']),
            'notesHtml' => $html->render($vendorInvoice->notes),
        ])->setPaper('a4')->setOption([
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
        ])->download(Str::slug($vendorInvoice->number).'.pdf');
    }

    public function edit(VendorInvoice $vendorInvoice): View
    {
        abort_unless($vendorInvoice->status === 'draft', 403, 'Only draft vendor invoices can be edited.');
        $vendorInvoice->load('lines');

        return view('expense::vendor-invoices.edit', [
            'invoice' => $vendorInvoice,
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'expenseAccounts' => Account::query()->active()->postable()->ofType(AccountType::Expense)->orderBy('code')->get(['id', 'code', 'name']),
            'taxRate' => $this->rateFor($vendorInvoice->tax_total, $vendorInvoice->subtotal),
            'whtRate' => $this->rateFor(
                $vendorInvoice->whttax_total,
                bcadd((string) $vendorInvoice->subtotal, (string) $vendorInvoice->tax_total, 4),
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedInvoiceData($request);
        DocumentSequence::query()->firstOrCreate(
            ['key' => 'vendor_invoice'],
            ['prefix' => 'VI', 'pattern' => '{prefix}-{year}-{number}', 'padding' => 6, 'reset_yearly' => true, 'current_value' => 0],
        );
        $invoice = VendorInvoice::query()->create([
            'number' => $this->sequences->next('vendor_invoice', (int) now()->format('Y')),
            ...$data,
            'status' => 'draft',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $this->syncLine($invoice, $data['tax_rate'], $data['debit_account_id']);

        return redirect()->route('expense.vendor-invoices.index')->with('status', "Vendor invoice {$invoice->number} saved as draft.");
    }

    public function update(Request $request, VendorInvoice $vendorInvoice): RedirectResponse
    {
        abort_unless($vendorInvoice->status === 'draft', 403, 'Only draft vendor invoices can be edited.');

        $data = $this->validatedInvoiceData($request);
        $vendorInvoice->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);
        $this->syncLine($vendorInvoice, $data['tax_rate'], $data['debit_account_id']);

        return redirect()->route('expense.vendor-invoices.show', $vendorInvoice)
            ->with('status', "Vendor invoice {$vendorInvoice->number} updated.");
    }

    public function submit(VendorInvoice $vendorInvoice, Request $request): RedirectResponse
    {
        Gate::authorize('submit', $vendorInvoice);
        $this->workflow->submit($vendorInvoice, $request->user()->id);

        return back()->with('status', 'Vendor invoice submitted for approval.');
    }

    public function approve(VendorInvoice $vendorInvoice, Request $request): RedirectResponse
    {
        Gate::authorize('approve', $vendorInvoice);
        $this->workflow->approve($vendorInvoice, $request->user()->id);

        return back()->with('status', 'Vendor invoice sent for approval.');
    }

    public function ownerApprove(VendorInvoice $vendorInvoice, Request $request): RedirectResponse
    {
        Gate::authorize('ownerApprove', $vendorInvoice);
        $this->workflow->ownerReview($vendorInvoice, $request->user()->id);

        return back()->with('status', 'Approved the vendor invoice.');
    }

    public function reject(VendorInvoice $vendorInvoice, Request $request): RedirectResponse
    {
        Gate::authorize('reject', $vendorInvoice);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:3', 'max:2000']]);
        $this->workflow->ownerReview($vendorInvoice, $request->user()->id, $data['rejection_reason']);

        return back()->with('status', 'Vendor invoice rejected.');
    }

    public function post(VendorInvoice $vendorInvoice, Request $request): RedirectResponse
    {
        Gate::authorize('post', $vendorInvoice);
        $data = $request->validate([
            'debit_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);

        if (! empty($data['debit_account_id'])) {
            $vendorInvoice->lines()->updateOrCreate(
                ['position' => 1],
                ['debit_account_id' => $data['debit_account_id']],
            );
        }

        $invoice = $this->workflow->post(
            $vendorInvoice,
            $request->user()->id,
        );

        return back()->with('status', "Vendor invoice posted to GL as {$invoice->journalEntry?->number}.");
    }

    public function pay(VendorInvoice $vendorInvoice, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank_transfer,cheque,credit_card,online_gateway,other'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'reference' => ['nullable', 'string', 'max:128'],
        ]);

        $payment = $this->workflow->pay(
            $vendorInvoice,
            number_format((float) $data['amount'], 4, '.', ''),
            $data['payment_method'],
            $data['bank_account_id'] ?? null,
            $data['reference'] ?? null,
            $request->user()->id,
        );

        return back()->with('status', "Vendor payment {$payment->number} posted to GL as {$payment->journalEntry?->number}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedInvoiceData(Request $request): array
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'vendor_invoice_number' => ['required', 'string', 'max:64'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'billing_month' => ['required', 'date_format:Y-m'],
            'debit_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'wht_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $subtotal = number_format((float) $data['subtotal'], 4, '.', '');
        $tax = bcmul($subtotal, bcdiv((string) $data['tax_rate'], '100', 8), 2);
        $taxInclusiveAmount = bcadd($subtotal, $tax, 4);
        $wht = bcmul($taxInclusiveAmount, bcdiv((string) $data['wht_rate'], '100', 8), 2);
        $total = bcsub($taxInclusiveAmount, $wht, 4);

        return [
            'vendor_id' => $data['vendor_id'],
            'vendor_invoice_number' => $data['vendor_invoice_number'],
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'],
            'billing_month' => $data['billing_month'],
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'whttax_total' => $wht,
            'total' => $total,
            'debit_account_id' => $data['debit_account_id'],
            'tax_rate' => $data['tax_rate'],
            'wht_rate' => $data['wht_rate'],
            'notes' => $data['notes'],
        ];
    }

    private function syncLine(VendorInvoice $invoice, string|int|float $taxRate, int $debitAccountId): void
    {
        $invoice->lines()->updateOrCreate(
            ['position' => 1],
            [
                'line_subtotal' => $invoice->subtotal,
                'tax_rate' => $taxRate,
                'debit_account_id' => $debitAccountId,
                'line_tax' => $invoice->tax_total,
                'line_total' => bcadd((string) $invoice->subtotal, (string) $invoice->tax_total, 4),
                'line_wht_tax' => $invoice->whttax_total,
                'line_total_wht_tax' => $invoice->total,
            ],
        );
    }

    private function rateFor(string|int|float|null $amount, string|int|float|null $subtotal): string
    {
        if ((float) $subtotal === 0.0) {
            return '0';
        }

        return number_format(((float) $amount / (float) $subtotal) * 100, 2, '.', '');
    }
}
