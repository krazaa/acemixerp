<?php

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\PaymentTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PaymentTermManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function create(array $data): PaymentTerm;

    public function update(PaymentTerm $term, array $data): PaymentTerm;

    public function delete(PaymentTerm $term): void;

    public function changeStatus(PaymentTerm $term, RecordStatus $status): PaymentTerm;

    public function makeDefault(PaymentTerm $term): PaymentTerm;
}
