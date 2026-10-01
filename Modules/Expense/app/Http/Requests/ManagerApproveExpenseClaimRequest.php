<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Expense\Models\ExpenseClaim;

class ManagerApproveExpenseClaimRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'line_reviews' => ['required', 'array', 'min:1'],
            'line_reviews.*.id' => ['required', 'integer', 'exists:expense_claim_lines,id', 'distinct'],
            'line_reviews.*.decision' => ['required', 'string', Rule::in(['approved', 'rejected'])],
            'line_reviews.*.deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'line_reviews.*.deduction_reason' => ['nullable', 'string', 'max:2000'],
            'line_reviews.*.rejection_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function authorize(): bool
    {
        $claim = $this->route('claim');

        return $claim instanceof ExpenseClaim
            && $this->user()?->can('managerApprove', $claim) === true;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('line_reviews')) {
                    return;
                }

                $claim = $this->route('claim');

                if (! $claim instanceof ExpenseClaim) {
                    return;
                }

                $reviews = $this->input('line_reviews', []);
                $lineIds = collect($reviews)->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values();
                $claimLines = $claim->lines()->get(['id', 'amount'])->keyBy('id');
                $claimLineIds = $claimLines->keys()->sort()->values();

                if ($claimLineIds->isEmpty() || $lineIds->all() !== $claimLineIds->all()) {
                    $validator->errors()->add('line_reviews', 'Review every expense entry before approving the claim.');
                }

                foreach ($reviews as $index => $review) {
                    if (($review['decision'] ?? null) === 'rejected' && blank($review['rejection_reason'] ?? null)) {
                        $validator->errors()->add("line_reviews.$index.rejection_reason", 'Provide a reason for each rejected entry.');
                    }

                    if (($review['decision'] ?? null) === 'approved' && $claimLines->has((int) $review['id'])) {
                        $deductionAmount = (string) ($review['deduction_amount'] ?? '0');
                        $lineAmount = (string) $claimLines->find((int) $review['id'])->amount;

                        if (bccomp($deductionAmount, $lineAmount, 4) > 0) {
                            $validator->errors()->add("line_reviews.$index.deduction_amount", 'The deduction cannot exceed this entry amount.');
                        }

                        if (bccomp($deductionAmount, '0', 4) > 0 && blank($review['deduction_reason'] ?? null)) {
                            $validator->errors()->add("line_reviews.$index.deduction_reason", 'Provide a reason when entering a deduction.');
                        }
                    }
                }
            },
        ];
    }
}
