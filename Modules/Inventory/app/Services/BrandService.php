<?php

namespace Modules\Inventory\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Brand;

final class BrandService
{
    private const LINE_TABLES = ['purchase_requisition_lines', 'rfq_lines', 'purchase_order_lines', 'goods_receipt_lines', 'supplier_invoice_lines'];

    public function paginate(array $filters): LengthAwarePaginator
    {
        return Brand::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')->paginate(25)->withQueryString();
    }

    public function create(array $data): Brand
    {
        return DB::transaction(fn (): Brand => Brand::create($data));
    }

    public function update(Brand $brand, array $data): Brand
    {
        return DB::transaction(function () use ($brand, $data): Brand {
            $brand = Brand::query()->lockForUpdate()->findOrFail($brand->id);
            $brand->update($data);

            return $brand;
        });
    }

    public function delete(Brand $brand): void
    {
        DB::transaction(function () use ($brand): void {
            $brand = Brand::query()->lockForUpdate()->findOrFail($brand->id);
            foreach (self::LINE_TABLES as $table) {
                if (DB::table($table)->where('brand_id', $brand->id)->exists()) {
                    throw ValidationException::withMessages(['brand' => 'This brand is used by a purchasing document. Set it inactive instead.']);
                }
            }
            $brand->delete();
        });
    }
}
