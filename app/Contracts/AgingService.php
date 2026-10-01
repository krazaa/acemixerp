<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

interface AgingService
{
    /**
     * @return Collection<int, object> rows:
     *                                 party_id, party_name, currency_code, current, days_1_30, days_31_60, days_61_90, days_90_plus, total
     */
    public function customerAging(?\DateTimeInterface $asOf = null): Collection;

    public function vendorAging(?\DateTimeInterface $asOf = null): Collection;
}
