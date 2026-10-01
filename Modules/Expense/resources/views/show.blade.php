<x-default-layout>
    @section('title', $claim->number)

    @section('sub-title')
        <div class="text-muted mt-1">
            {{ $claim->employee?->fullName() }}
            · {{ $claim->employee?->designation?->name ?? 'No designation assigned' }}
            · Expense claim

            <span class="badge badge-primary">
                {{ str($claim->status->value)->replace('_', ' ')->title()->replace('Ceo', 'CEO') }}
            </span>
        </div>
    @endsection

    @section('toolbar-button')
        <a href="{{ route('expense.pdf', $claim) }}" class="btn btn-sm btn-light-primary">Download PDF</a>
        @can('update', $claim)
            <a href="{{ route('expense.edit', $claim) }}"
               class="btn btn-sm btn-outline-primary">
                Edit Draft
            </a>
        @endcan

        @can('submit', $claim)
            <form method="POST" action="{{ route('expense.submit', $claim) }}" class="d-inline">
                @csrf
                @method('PATCH')

                <button type="submit" class="btn btn-sm btn-primary">
                    Submit for Approval
                </button>
            </form>
        @endcan

        @can('ceoApprove', $claim)
            <form method="POST" action="{{ route('expense.ceo-approve', $claim) }}" class="d-inline">
                @csrf
                @method('PATCH')

                <button type="submit" class="btn btn-sm btn-success">
                    CEO Approve
                </button>
            </form>
        @endcan

        <a href="{{ route('expense.index') }}"
           class="btn btn-sm btn-light-secondary">
            Back
        </a>
    @endsection

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Session error --}}
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- =========================================================
        SUMMARY CARDS
    ========================================================== --}}
    <div class="row g-3 mb-3">
        {{-- Claimed Amount --}}
        @php
            $hasDeductionReason = filled($claim->manager_deduction_reason);
        @endphp

        <div class=" @if($hasDeductionReason) col-md-4 @else col-md-6 @endif">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">CLAIMED AMOUNT</div>

                    <div class="fs-3 fw-semibold
                        {{ $claim->status->value === 'reimbursed'
                            ? 'text-success'
                            : ($claim->status->value === 'draft'
                                ? 'text-warning'
                                : 'text-danger') }}">
                        {{ $claim->currency_code }}
                        {{ number_format((float) $claim->amount, 2) }}
                    </div>

                    <div class="small text-muted">
                        {{ $claim->expense_date->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Approved Amount --}}
        <div class="@if($hasDeductionReason) col-md-4 @else col-md-6 @endif">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">APPROVED AMOUNT</div>

                    <div class="fs-3 fw-semibold text-success">
                        {{ $claim->currency_code }}
                        {{ number_format((float) $claim->approvedAmount(), 2) }}
                    </div>

                    <div class="small text-muted">
                        Approved amount or claimed amount before review
                    </div>
                </div>
            </div>
        </div>

{{-- Manager review --}}
@if(filled($claim->manager_rejection_reason) || filled($claim->manager_deduction_reason))
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">

                <div class="text-muted small mb-3">
                    Manager Review
                </div>

                {{-- Deduction --}}
                @if(filled($claim->manager_deduction_reason))
                    <div class="mb-3">
                        <div class="fs-5 fw-semibold">
                            {{ $claim->currency_code }}
                            {{ number_format((float) $claim->manager_deduction_amount, 2) }}
                        </div>

                        <div class="small text-danger">
                            <strong>Deduction Reason:</strong><br>
                            {{ $claim->manager_deduction_reason }}<br>
                        </div>
                    </div>
                @endif

                {{-- Rejection --}}
                @if(filled($claim->manager_rejection_reason))
                    <div>
                        <div class="small text-danger">
                            <strong>Rejection Reason:</strong>
                             {{ $claim->line->reference ?? 'N/A' }}
                            <br>
                            {{ $claim->manager_rejection_reason }}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endif
    </div>

    {{-- =========================================================
        CLAIM DETAILS / APPROVAL PROGRESS / CEO REJECTION
    ========================================================== --}}
    <div class="row g-3 mb-3">

        {{-- Expense Details --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Expense Details
                    </h3>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">

                        <dt class="col-sm-5 text-muted">
                            Employee Designation
                        </dt>
                        <dd class="col-sm-7">
                            {{ $claim->employee?->designation?->name ?? 'Not assigned' }}
                        </dd>

                        <dt class="col-sm-5 text-muted">
                            Department Manager
                        </dt>
                        <dd class="col-sm-7">
                            {{ $claim->department?->manager?->name
                                ?? 'Not assigned — super-admin review required' }}
                        </dd>

                        <dt class="col-sm-5 text-muted">
                            Description
                        </dt>
                        <dd class="col-sm-7 mb-0">
                            {{ $claim->description ?: '—' }}
                        </dd>

                    </dl>
                </div>

            </div>
        </div>

        {{-- Approval Progress --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Approval Progress
                    </h3>
                </div>

                <div class="card-body small">

                    {{-- Submitted --}}
                    <div class="mb-3">
                        <span class="badge badge-{{
                            $claim->status->value === 'draft'
                                ? 'secondary'
                                : 'success'
                        }} me-2">
                            1
                        </span>

                        Submitted

                        @if ($claim->status->value !== 'draft')
                            <span class="text-success ms-1">
                                ✓
                            </span>
                        @else
                            <span class="text-muted ms-1">
                                Not submitted
                            </span>
                        @endif
                    </div>

                    {{-- Manager Approval --}}
                    <div class="mb-3">
                        <span class="badge badge-{{
                            $claim->hasManagerApproval()
                                ? 'success'
                                : 'secondary'
                        }} me-2">
                            2
                        </span>

                        Manager Approval

                        <span class="text-muted ms-1">
                            {{ $claim->hasManagerApproval()
                                ? $claim->manager_approved_at?->format('d M Y, h:i A')
                                : 'Not reviewed' }}
                        </span>
                    </div>

                    {{-- CEO Approval --}}
                    <div class="mb-3">
                        <span class="badge badge-{{
                            $claim->ceo_approved_at !== null
                                ? 'success'
                                : 'secondary'
                        }} me-2">
                            3
                        </span>

                        CEO Approval

                        <span class="text-muted ms-1">
                            {{ $claim->ceo_approved_at
                                ? $claim->ceo_approved_at->format('d M Y, h:i A')
                                : 'Not approved' }}
                        </span>
                    </div>

                    {{-- Reimbursement --}}
                    <div>
                        <span class="badge badge-{{
                            $claim->reimbursed_at !== null
                                ? 'success'
                                : 'secondary'
                        }} me-2">
                            4
                        </span>

                        Finance Reimbursement

                        <span class="text-muted ms-1">
                            {{ $claim->reimbursed_at
                                ? $claim->reimbursed_at->format('d M Y, h:i A')
                                : 'Not reimbursed' }}
                        </span>
                    </div>

                </div>
            </div>
        </div>

        {{-- CEO Rejection --}}
        @can('ceoReject', $claim)
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header">
                        <h3 class="card-title text-danger mb-0">
                            CEO Rejection
                        </h3>
                    </div>

                    <div class="card-body">
                        <form method="POST"
                              action="{{ route('expense.ceo-reject', $claim) }}">

                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="rejection_reason" class="form-label">
                                    Rejection reason
                                </label>

                                <textarea
                                    id="rejection_reason"
                                    name="rejection_reason"
                                    class="form-control"
                                    rows="4"
                                    maxlength="2000"
                                    required
                                    placeholder="Enter rejection reason">{{ old('rejection_reason') }}</textarea>

                                @error('rejection_reason')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="btn btn-light-danger w-100">
                                Reject Claim
                            </button>

                        </form>
                    </div>

                </div>
            </div>
        @endcan

    </div>

    {{-- CEO rejection reason --}}
    @if ($claim->status->value === 'rejected' && $claim->rejection_reason)
        <div class="alert alert-danger mt-3">
            <strong>CEO rejection reason:</strong>
            {{ $claim->rejection_reason }}
        </div>
    @endif

    {{-- =========================================================
        EXPENSE ENTRIES
    ========================================================== --}}
    <div class="card border-0 shadow-sm mb-3">

        <div class="card-header">
            <h3 class="card-title mb-0">
                Expense Entries
            </h3>

            <div class="card-toolbar">
                <span class="text-muted small">
                    {{ $claim->lines->count() ?: 1 }} line(s)
                </span>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead>
                        <tr class="fw-bold fs-6 text-gray-800">
                            <th>#</th>
                            <th>Date</th>
                            <th>Expense Account</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($claim->lines as $line)
                            <tr>
                                <td>
                                    {{ $line->line_number }}
                                </td>

                                <td>
                                    {{ $line->expense_date->format('d M Y') }}
                                </td>

                                <td>
                                    {{ $line->expenseAccount?->code ?? '—' }}
                                    —
                                    {{ $line->expenseAccount?->name ?? 'No account' }}
                                </td>

                                <td>
                                    {{ $line->reference ?: '—' }}
                                </td>

                                <td class="text-end">
                                    {{ number_format((float) $line->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            {{-- Legacy/single-line claim --}}
                            <tr>
                                <td>1</td>

                                <td>
                                    {{ $claim->expense_date->format('d M Y') }}
                                </td>

                                <td>
                                    {{ $claim->expenseAccount?->code ?? '—' }}
                                    —
                                    {{ $claim->expenseAccount?->name ?? 'No account' }}
                                </td>

                                <td>
                                    {{ $claim->description ?: '—' }}
                                </td>

                                <td class="text-end">
                                    {{ number_format((float) $claim->amount, 2) }}
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                    <tfoot>

                        <tr>
                            <th colspan="4" class="text-end">
                                Claim Total
                            </th>

                            <th class="text-end">
                                {{ $claim->currency_code }}
                                {{ number_format((float) $claim->amount, 2) }}
                            </th>
                        </tr>

                        <tr>
                            <th colspan="5" class="text-center lh-lg fw-bold">
                                @php
                                    $formatter = class_exists(\NumberFormatter::class)
                                        ? new \NumberFormatter('en', \NumberFormatter::SPELLOUT)
                                        : null;
                                @endphp

                                @if ($formatter)
                                    {{ ucfirst($formatter->format($claim->amount)) }}
                                    {{ strtolower($claim->currency_code) }}
                                    only.
                                @else
                                    {{ $claim->currency_code }}
                                    {{ number_format((float) $claim->amount, 2) }}
                                    only.
                                @endif
                            </th>
                        </tr>

                    </tfoot>

                </table>

            </div>
        </div>

    </div>

    {{-- =========================================================
        MANAGER REVIEW
    ========================================================== --}}
    @can('managerApprove', $claim)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    Manager Review
                </h3>
            </div>

            <form method="POST"
                  action="{{ route('expense.manager-approve', $claim) }}">

                @csrf
                @method('PATCH')

                <div class="card-body border-bottom">

                    <div class="alert alert-info mb-0">
                        Select <strong>Approve</strong> or
                        <strong>Reject</strong> for every entry.
                        A rejection reason is required for rejected entries.
                        The approved total is sent to the CEO.
                    </div>

                    @error('line_reviews')
                        <div class="text-danger small mt-2">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                @if ($claim->lines->isNotEmpty())

                    <div class="table-responsive">

                        <table class="table table-bordered align-middle mb-0">

                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800">
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Expense Account</th>
                                    <th>Reference</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Deduction</th>
                                    <th>Deduction Reason</th>
                                    <th class="text-center">Approve</th>
                                    <th class="text-center">Reject</th>
                                    <th>Rejection Reason</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($claim->lines as $index => $line)

                                    @php
                                        $oldReview = old("line_reviews.$index", []);
                                    @endphp

                                    <tr>

                                        <td>
                                            {{ $line->line_number }}

                                            <input
                                                type="hidden"
                                                name="line_reviews[{{ $index }}][id]"
                                                value="{{ $line->id }}">
                                        </td>

                                        <td>
                                            {{ $line->expense_date->format('d M Y') }}
                                        </td>

                                        <td>
                                            {{ $line->expenseAccount?->code ?? '—' }}
                                            —
                                            {{ $line->expenseAccount?->name ?? 'No account' }}
                                        </td>

                                        <td>
                                            {{ $line->reference ?: '—' }}
                                        </td>

                                        <td class="text-end">
                                            {{ number_format((float) $line->amount, 2) }}
                                        </td>

                                        {{-- Deduction --}}
                                        <td>
                                            <input
                                                type="number"
                                                name="line_reviews[{{ $index }}][deduction_amount]"
                                                min="0"
                                                max="{{ $line->amount }}"
                                                step="0.01"
                                                value="{{ $oldReview['deduction_amount'] ?? 0 }}"
                                                class="form-control form-control-sm text-end
                                                    @error("line_reviews.$index.deduction_amount")
                                                        is-invalid
                                                    @enderror"
                                                aria-label="Deduction for entry {{ $line->line_number }}">

                                            @error("line_reviews.$index.deduction_amount")
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </td>

                                        {{-- Deduction reason --}}
                                        <td>
                                            <input
                                                type="text"
                                                name="line_reviews[{{ $index }}][deduction_reason]"
                                                value="{{ $oldReview['deduction_reason'] ?? '' }}"
                                                maxlength="2000"
                                                class="form-control form-control-sm
                                                    @error("line_reviews.$index.deduction_reason")
                                                        is-invalid
                                                    @enderror"
                                                placeholder="Required if deducted">

                                            @error("line_reviews.$index.deduction_reason")
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </td>

                                        {{-- Approve --}}
                                        <td class="text-center">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="line_reviews[{{ $index }}][decision]"
                                                value="approved"
                                                @checked(($oldReview['decision'] ?? 'approved') === 'approved')
                                                required
                                                aria-label="Approve entry {{ $line->line_number }}">
                                        </td>

                                        {{-- Reject --}}
                                        <td class="text-center">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="line_reviews[{{ $index }}][decision]"
                                                value="rejected"
                                                @checked(($oldReview['decision'] ?? 'approved') === 'rejected')
                                                required
                                                aria-label="Reject entry {{ $line->line_number }}">
                                        </td>

                                        {{-- Rejection reason --}}
                                        <td>
                                            <input
                                                type="text"
                                                name="line_reviews[{{ $index }}][rejection_reason]"
                                                value="{{ $oldReview['rejection_reason'] ?? '' }}"
                                                maxlength="2000"
                                                class="form-control form-control-sm
                                                    @error("line_reviews.$index.rejection_reason")
                                                        is-invalid
                                                    @enderror"
                                                placeholder="Required if rejected">

                                            @error("line_reviews.$index.rejection_reason")
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @else

                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            This claim has no expense lines to review.
                        </div>
                    </div>

                @endif

                <div class="card-body d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">
                        Complete Manager Review
                    </button>
                </div>

            </form>

        </div>
    @endcan

    {{-- =========================================================
        EVIDENCE
    ========================================================== --}}
    <div class="card border-0 shadow-sm mb-3">

        <div class="card-header">
            <h3 class="card-title mb-0">
                Evidence Details
            </h3>
        </div>

        <div class="card-body">

            {{-- Text/HTML evidence --}}
            @if ($claim->evidence_text)

                @if (strip_tags($claim->evidence_text) === $claim->evidence_text)

                    <div class="mb-3"
                         style="white-space: pre-wrap; overflow-wrap: anywhere;">
                        {{ $claim->evidence_text }}
                    </div>

                @else

                  {!! $claim->evidence_text !!}

                @endif

            @elseif ($claim->evidences->isEmpty())

                <div class="text-muted">
                    No evidence provided.
                </div>

            @endif



        </div>

    </div>

    {{-- =========================================================
        FINANCE REIMBURSEMENT
    ========================================================== --}}
    @can('reimburse', $claim)

        @if ($claim->status !== \Modules\Expense\Enums\ExpenseClaimStatus::Reimbursed)

            @php
                $approvedLines = $claim->lines
                    ->where('manager_decision', 'approved');
            @endphp

            <div class="card border-0 shadow-sm mb-3">

                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Finance Reimbursement
                    </h3>
                </div>

                <div class="card-body">

                    <form method="POST"
                          action="{{ route('expense.reimburse', $claim) }}">

                        @csrf
                        @method('PATCH')

                        <div class="row g-3">

                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    Choose the final posting Expense Account
                                    for each approved entry. This does not
                                    change the claim amount or the approval
                                    decision.
                                </div>
                            </div>

                            @forelse ($approvedLines as $line)

                                <div class="col-md-6">

                                    <label
                                        class="form-label"
                                        for="expense_account_{{ $line->id }}">

                                        Entry {{ $line->line_number }}
                                        ·
                                        {{ number_format(
                                            (float) $line->amount -
                                            (float) $line->manager_deduction_amount,
                                            2
                                        ) }}
                                        PKR

                                    </label>

                                    <select
                                        id="expense_account_{{ $line->id }}"
                                        name="expense_account_ids[{{ $line->id }}]"
                                        class="form-select
                                            @error("expense_account_ids.$line->id")
                                                is-invalid
                                            @enderror" data-control="select2"
                                        required>

                                        <option value="">
                                            Select posting expense account
                                        </option>

                                        @foreach ($expenseAccounts as $account)

                                            <option
                                                value="{{ $account->id }}"
                                                @selected(
                                                    (int) old(
                                                        "expense_account_ids.$line->id",
                                                        $line->expense_account_id
                                                    ) === $account->id
                                                )>

                                                {{ $account->code }}
                                                —
                                                {{ $account->name }}

                                            </option>

                                        @endforeach

                                    </select>

                                    @error("expense_account_ids.$line->id")
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>

                            @empty

                                <div class="col-12">
                                    <div class="alert alert-warning mb-0">
                                        No approved expense entries are
                                        available for reimbursement.
                                    </div>
                                </div>

                            @endforelse

                            {{-- Bank --}}
                            <div class="col-md-5">

                                <label
                                    class="form-label"
                                    for="bank_account_id">
                                    Payment bank / cash account

                                </label>

                                <select
                                    id="bank_account_id"
                                    name="bank_account_id"
                                    class="form-select
                                        @error('bank_account_id')
                                            is-invalid
                                        @enderror"
                                    required>

                                    <option value="">
                                        Select payment bank account
                                    </option>

                                    @foreach ($banks as $bank)

                                        <option
                                            value="{{ $bank->id }}"
                                            @selected(
                                                (int) old('bank_account_id') === $bank->id
                                            )>

                                            {{ $bank->name }}
                                            —
                                            {{ $bank->account_number }}

                                        </option>

                                    @endforeach

                                </select>

                                @error('bank_account_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            {{-- Payment reference --}}
                            <div class="col-md-4">

                                <label
                                    class="form-label"
                                    for="payment_reference">

                                    Payment reference

                                </label>

                                <input
                                    id="payment_reference"
                                    name="payment_reference"
                                    value="{{ old('payment_reference') }}"
                                    maxlength="255"
                                    class="form-control"
                                    placeholder="Cheque or transfer reference">

                            </div>

                            {{-- Submit --}}
                            <div class="col-md-3 d-flex align-items-end">

                                <button
                                    type="submit"
                                    class="btn btn-primary w-100"
                                    @disabled($approvedLines->isEmpty())>

                                    Post Reimbursement

                                </button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        @endif

    @endcan

</x-default-layout>
