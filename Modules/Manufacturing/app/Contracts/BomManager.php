<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Manufacturing\Data\BomData;
use Modules\Manufacturing\Models\BillOfMaterials;

interface BomManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function create(BomData $data, int $userId): BillOfMaterials;

    public function update(BillOfMaterials $bom, BomData $data, int $userId): BillOfMaterials;

    public function delete(BillOfMaterials $bom): void;

    public function activate(BillOfMaterials $bom, int $userId): BillOfMaterials;
}
