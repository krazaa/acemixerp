<?php

namespace Modules\Procurement\Services;

use App\Contracts\SequenceGenerator;
use App\Exceptions\BusinessRuleException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Contracts\PurchaseRequisitionManager;
use Modules\Procurement\Data\PurchaseRequisitionData;
use Modules\Procurement\Enums\PurchaseRequisitionStatus;
use Modules\Procurement\Models\PurchaseRequisition;

final class PurchaseRequisitionService implements PurchaseRequisitionManager
{
    /**
     * Create a new purchase requisition service.
     */
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly LineBrandService $brands,
        private readonly LineOriginService $origin,

    ) {}

    /**
     * Paginate purchase requisitions using the supplied filters.
     */
    public function paginate(
        array $filters = [],
        int $perPage = 25,
    ): LengthAwarePaginator {
        return PurchaseRequisition::query()
            ->with([
                'requester:id,name',
                'department:id,name',
                'approver:id,name',
            ])
            ->withCount('lines')
            ->search($filters['search'] ?? null)
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status),
            )
            ->when(
                $filters['department_id'] ?? null,
                fn ($query, $departmentId) => $query->where(
                    'department_id',
                    $departmentId,
                ),
            )
            ->when(
                $filters['from'] ?? null,
                fn ($query, $date) => $query->whereDate(
                    'requested_date',
                    '>=',
                    $date,
                ),
            )
            ->when(
                $filters['to'] ?? null,
                fn ($query, $date) => $query->whereDate(
                    'requested_date',
                    '<=',
                    $date,
                ),
            )
            ->orderByDesc('requested_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new purchase requisition with its lines.
     */
    public function create(
        PurchaseRequisitionData $data,
        int $userId,
    ): PurchaseRequisition {
        $this->validateLines($data);

        return DB::transaction(function () use ($data, $userId): PurchaseRequisition {
            $purchaseRequisition = PurchaseRequisition::query()->create([
                'number' => $this->sequences->next(
                    'purchase_requisition',
                    (int) $data->requestedDate->format('Y'),
                ),
                'requested_date' => $data->requestedDate,
                'required_date' => $data->requiredDate,
                'department_id' => $data->departmentId,
                'warehouse_id' => $data->warehouseId,
                'cost_center_id' => $data->costCenterId,
                'requested_by' => $data->requestedBy ?? $userId,
                'purpose' => $data->purpose,
                'status' => PurchaseRequisitionStatus::Draft,
                'notes' => $data->notes,
                'total_estimated' => $data->totalEstimated(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $purchaseRequisition->lines()->create(
                    array_replace($line->toArray(), ['brand_id' => $this->brands->resolve($line->brandId, $line->position)]),
                );
            }

            return $purchaseRequisition->fresh([
                'lines.item',
                'lines.unit',
                'requester',
            ]);
        });
    }

    /**
     * Update a draft purchase requisition and replace its lines.
     */
    public function update(
        PurchaseRequisition $pr,
        PurchaseRequisitionData $data,
        int $userId,
    ): PurchaseRequisition {
        if (! $pr->isEditable()) {
            throw BusinessRuleException::make(
                "Purchase requisition {$pr->number} is {$pr->status->label()} and cannot be edited.",
            );
        }

        $this->validateLines($data);

        return DB::transaction(function () use (
            $pr,
            $data,
            $userId,
        ): PurchaseRequisition {
            $pr->lines()->delete();

            foreach ($data->lines as $line) {
                $pr->lines()->create(
                    array_replace($line->toArray(), ['brand_id' => $this->brands->resolve($line->brandId, $line->position)]),
                );
            }

            $pr->fill([
                'requested_date' => $data->requestedDate,
                'required_date' => $data->requiredDate,
                'department_id' => $data->departmentId,
                'warehouse_id' => $data->warehouseId,
                'cost_center_id' => $data->costCenterId,
                'requested_by' => $data->requestedBy ?? $pr->requested_by,
                'purpose' => $data->purpose,
                'notes' => $data->notes,
                'total_estimated' => $data->totalEstimated(),
                'updated_by' => $userId,
            ])->save();

            return $pr->fresh([
                'lines.item',
                'lines.unit',
            ]);
        });
    }

    /**
     * Submit a draft purchase requisition for approval.
     */
    public function submit(
        PurchaseRequisition $pr,
        int $userId,
    ): PurchaseRequisition {
        if (! $pr->status->canSubmit()) {
            throw BusinessRuleException::make(
                "Purchase requisition {$pr->number} cannot be submitted from status {$pr->status->label()}.",
            );
        }

        if (! $pr->lines()->exists()) {
            throw BusinessRuleException::make(
                'Cannot submit a requisition with no lines.',
            );
        }

        $pr->forceFill([
            'status' => PurchaseRequisitionStatus::Submitted,
            'submitted_at' => now(),
            'submitted_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $pr->fresh();
    }

    /**
     * Move a submitted requisition into review.
     */
    public function startReview(
        PurchaseRequisition $pr,
        int $userId,
    ): PurchaseRequisition {
        if ($pr->status !== PurchaseRequisitionStatus::Submitted) {
            throw BusinessRuleException::make(
                'Only submitted requisitions can enter review.',
            );
        }

        $pr->forceFill([
            'status' => PurchaseRequisitionStatus::UnderReview,
            'updated_by' => $userId,
        ])->save();

        return $pr->fresh();
    }

    /**
     * Approve a purchase requisition.
     */
    public function approve(
        PurchaseRequisition $pr,
        int $userId,
    ): PurchaseRequisition {
        if (! $pr->status->canApprove()) {
            throw BusinessRuleException::make(
                "Purchase requisition {$pr->number} is not in an approvable status.",
            );
        }

        $pr->forceFill([
            'status' => PurchaseRequisitionStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $pr->fresh();
    }

    /**
     * Reject a purchase requisition with a mandatory reason.
     */
    public function reject(
        PurchaseRequisition $pr,
        int $userId,
        string $reason,
    ): PurchaseRequisition {
        $reason = trim($reason);

        if ($reason === '') {
            throw BusinessRuleException::make(
                'A rejection reason is required.',
            );
        }

        if (! $pr->status->canApprove()) {
            throw BusinessRuleException::make(
                'Only submitted or under-review requisitions can be rejected.',
            );
        }

        $pr->forceFill([
            'status' => PurchaseRequisitionStatus::Rejected,
            'rejected_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'updated_by' => $userId,
        ])->save();

        return $pr->fresh();
    }

    /**
     * Cancel a purchase requisition.
     */
    public function cancel(
        PurchaseRequisition $pr,
        int $userId,
        ?string $reason = null,
    ): PurchaseRequisition {
        if (! $pr->status->canCancel()) {
            throw BusinessRuleException::make(
                "Purchase requisition {$pr->number} cannot be cancelled from status {$pr->status->label()}.",
            );
        }

        $reason = $reason !== null
            ? trim($reason)
            : null;

        $pr->forceFill([
            'status' => PurchaseRequisitionStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $userId,
            'cancellation_reason' => $reason,
            'updated_by' => $userId,
        ])->save();

        return $pr->fresh();
    }

    /**
     * Close an approved or converted purchase requisition.
     */
    public function close(
        PurchaseRequisition $pr,
        int $userId,
    ): PurchaseRequisition {
        if (! in_array(
            $pr->status,
            [
                PurchaseRequisitionStatus::Approved,
                PurchaseRequisitionStatus::Converted,
            ],
            true,
        )) {
            throw BusinessRuleException::make(
                'Only approved or converted requisitions can be closed.',
            );
        }

        $pr->forceFill([
            'status' => PurchaseRequisitionStatus::Closed,
            'closed_at' => now(),
            'closed_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $pr->fresh();
    }

    /**
     * Delete a draft purchase requisition.
     */
    public function delete(
        PurchaseRequisition $pr,
    ): void {
        if ($pr->status !== PurchaseRequisitionStatus::Draft) {
            throw BusinessRuleException::make(
                'Only draft requisitions can be deleted. Cancel submitted requisitions instead.',
            );
        }

        DB::transaction(
            fn (): ?bool => $pr->delete(),
        );
    }

    /**
     * Validate purchase requisition lines.
     */
    private function validateLines(
        PurchaseRequisitionData $data,
    ): void {
        if ($data->lines === []) {
            throw BusinessRuleException::make(
                'A purchase requisition must have at least one line.',
            );
        }
    }
}
