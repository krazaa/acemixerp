<?php

declare(strict_types=1);

namespace Modules\Procurement\Actions;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Contracts\RfqManager;
use Modules\Procurement\Data\RfqData;
use Modules\Procurement\Data\RfqLineData;
use Modules\Procurement\Enums\PurchaseRequisitionStatus;
use Modules\Procurement\Exceptions\RfqException;
use Modules\Procurement\Models\PurchaseRequisition;
use Modules\Procurement\Models\PurchaseRequisitionLine;
use Modules\Procurement\Models\RequestForQuotation;

final class ConvertRequisitionToRfq
{
    public function __construct(private readonly RfqManager $rfqs) {}

    /** @param array{vendor_ids: array<int, int|string>, due_date: string, terms?: ?string} $data */
    public function execute(PurchaseRequisition $requisition, array $data, int $userId): RequestForQuotation
    {
        return DB::transaction(function () use ($requisition, $data, $userId): RequestForQuotation {
            $locked = PurchaseRequisition::query()->lockForUpdate()->findOrFail($requisition->id);
            if (! $locked->status->canConvert() || $locked->converted_to_id !== null || $locked->rfqs()->exists()) {
                throw new RfqException('Only an approved requisition that has not already been converted can be converted to an RFQ.');
            }

            $lines = $locked->lines()->lockForUpdate()->get();
            $rfq = $this->rfqs->create(new RfqData(
                issueDate: CarbonImmutable::today(),
                dueDate: CarbonImmutable::parse($data['due_date']),
                purpose: $locked->purpose,
                currencyCode: Organization::current()->currency_code,
                lines: $lines->map(fn (PurchaseRequisitionLine $line, int $position): RfqLineData => new RfqLineData(
                    itemId: $line->item_id,
                    brandId: $line->brand_id,
                    originId: $line->origin_id,
                    unitId: $line->unit_id,
                    quantity: $line->quantity,
                    specification: $line->specification,
                    purchaseRequisitionLineId: $line->id,
                    position: $position,
                ))->all(),
                vendorIds: array_map('intval', $data['vendor_ids']),
                departmentId: $locked->department_id,
                costCenterId: $locked->cost_center_id,
                terms: $data['terms'] ?? null,
                sourceRequisitionIds: [$locked->id],
            ), $userId);

            $locked->forceFill([
                'status' => PurchaseRequisitionStatus::Converted,
                'converted_at' => now(),
                'converted_to_type' => $rfq->getMorphClass(),
                'converted_to_id' => $rfq->id,
                'updated_by' => $userId,
            ])->save();

            return $rfq;
        });
    }
}
