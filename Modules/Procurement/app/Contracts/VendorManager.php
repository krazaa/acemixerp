<?php

declare(strict_types=1);

namespace Modules\Procurement\Contracts;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface VendorManager
{
    /**
     * Paginated vendor list with optional filters:
     * search, status, category_id.
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * All active vendors — used for transactional dropdowns.
     */
    public function allActive(): Collection;

    /**
     * Only active vendors flagged as transport providers — used in the
     * delivery dispatch flow.
     */
    public function allTransportProviders(): Collection;

    public function create(array $data): Vendor;

    public function update(Vendor $vendor, array $data): Vendor;

    public function delete(Vendor $vendor): void;

    public function changeStatus(Vendor $vendor, VendorStatus $status): Vendor;

    /**
     * Generate the next vendor code via the shared sequence generator.
     * Format: VEND-2026-00001
     */
    public function nextCode(): string;
}
