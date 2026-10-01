<?php

namespace App\Contracts;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface VendorManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function allTransportProviders(): Collection;

    public function allSupplier(): Collection;

    public function create(array $data): Vendor;

    public function update(Vendor $vendor, array $data): Vendor;

    public function delete(Vendor $vendor): void;

    public function changeStatus(Vendor $vendor, VendorStatus $status): Vendor;

    public function nextCode(): string;
}
