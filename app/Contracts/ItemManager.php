<?php

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface ItemManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allSellable(): Collection;

    public function allPurchasable(): Collection;
    public function allPurchasablestock(): Collection;

    public function create(array $data, ?UploadedFile $image = null): Item;

    public function update(Item $item, array $data, ?UploadedFile $image = null): Item;

    public function delete(Item $item): void;

    public function changeStatus(Item $item, RecordStatus $status): Item;

    public function nextCode(): string;
}
