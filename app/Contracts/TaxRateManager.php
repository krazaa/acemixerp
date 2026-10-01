<?php

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Enums\TaxRateComponent;
use App\Models\TaxRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TaxRateManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(?TaxRateComponent $component = null): Collection;

    public function create(array $data): TaxRate;

    public function update(TaxRate $taxRate, array $data): TaxRate;

    public function delete(TaxRate $taxRate): void;

    public function changeStatus(TaxRate $taxRate, RecordStatus $status): TaxRate;

    public function makeDefault(TaxRate $taxRate): TaxRate;
}
