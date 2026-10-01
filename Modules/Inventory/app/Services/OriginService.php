<?php

namespace Modules\Inventory\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Origin;


final class OriginService
{
    private const LINE_TABLES = ['purchase_requisition_lines', 'rfq_lines', 'purchase_order_lines', 'goods_receipt_lines', 'supplier_invoice_lines'];

    public function paginate(array $filters): LengthAwarePaginator
    {
        return Origin::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')->paginate(25)->withQueryString();
    }

    public function create(array $data): Origin
    {
        return DB::transaction(fn (): Origin => Origin::create($data));
    }

    public function update(Origin $origin, array $data): Origin
    {
        return DB::transaction(function () use ($origin, $data): Origin {
            $origin = Origin::query()->lockForUpdate()->findOrFail($origin->id);
            $origin->update($data);

            return $origin;
        });
    }

    public function delete(Origin $origin): void
    {
        DB::transaction(function () use ($origin): void {
            $origin = Origin::query()->lockForUpdate()->findOrFail($origin->id);
            foreach (self::LINE_TABLES as $table) {
                if (DB::table($table)->where('origin_id', $origin->id)->exists()) {
                    throw ValidationException::withMessages(['origin' => 'This prigin is used by a purchasing document. Set it inactive instead.']);
                }
            }
            $origin->delete();
        });
    }
}
