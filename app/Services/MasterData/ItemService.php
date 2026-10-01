<?php

namespace App\Services\MasterData;

use App\Contracts\ItemManager;
use App\Contracts\SequenceGenerator;
use App\Enums\ItemType;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ItemService implements ItemManager
{
    private const IMAGE_DISK = 'local';

    private const IMAGE_DIR = 'items/images';

    public function __construct(private readonly SequenceGenerator $sequences) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Item::query()
            ->with(['category:id,code,name', 'unit:id,code,name'])
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['item_type'] ?? null, fn ($q, $t) => $q->where('item_type', $t))
            ->when($filters['category_id'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allSellable(): Collection
    {
        return Item::query()->sellable()->orderBy('name')
            ->get(['id', 'code', 'sku', 'name', 'selling_price', 'unit_id']);
    }

    public function allPurchasable(): Collection
    {
        return Item::query()->purchasable()->orderBy('name')
            ->get(['id', 'code', 'sku', 'name', 'cost_price', 'unit_id']);
    }

    public function allPurchasablestock(): Collection
    {
        return Item::query()->purchasable()->orderBy('name')
            ->get(['id', 'code', 'sku', 'name', 'cost_price', 'unit_id']);
    }

    public function create(array $data, ?UploadedFile $image = null): Item
    {
        return DB::transaction(function () use ($data, $image) {
            $itemType = ItemType::from($data['item_type'] ?? 'stock');

            $item = Item::query()->create([
                'code' => $data['code'] ?? $this->nextCode(),
                'sku' => $data['sku'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'item_type' => $itemType,
                'category_id' => $data['category_id'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'tax_rate_id' => $data['tax_rate_id'] ?? null,
                'is_tax_exempt' => $data['is_tax_exempt'] ?? false,
                'cost_price' => $data['cost_price'] ?? 0,
                'selling_price' => $data['selling_price'] ?? 0,
                'minimum_selling_price' => $data['minimum_selling_price'] ?? null,
                'track_inventory' => $itemType->tracksInventory()
                                            ? ($data['track_inventory'] ?? true)
                                            : false,
                'reorder_level' => $data['reorder_level'] ?? 0,
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'maximum_stock' => $data['maximum_stock'] ?? null,
                'allow_negative_stock' => $data['allow_negative_stock'] ?? false,
                'track_batch' => $data['track_batch'] ?? false,
                'track_serial' => $data['track_serial'] ?? false,
                'track_expiry' => $data['track_expiry'] ?? false,
                'is_sellable' => $itemType->isSellable() ? ($data['is_sellable'] ?? true) : false,
                'is_purchasable' => $itemType->isPurchasable() ? ($data['is_purchasable'] ?? true) : false,
                'status' => $data['status'] ?? RecordStatus::Active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if ($image) {
                $item->image_path = $this->storeImage($image);
                $item->save();
            }

            return $item->fresh(['category', 'unit']);
        });
    }

    public function update(Item $item, array $data, ?UploadedFile $image = null): Item
    {
        return DB::transaction(function () use ($item, $data, $image) {
            $itemType = isset($data['item_type'])
                ? ItemType::from($data['item_type'])
                : $item->item_type;

            $payload = [
                'sku' => $data['sku'] ?? $item->sku,
                'barcode' => $data['barcode'] ?? $item->barcode,
                'name' => $data['name'] ?? $item->name,
                'description' => $data['description'] ?? $item->description,
                'item_type' => $itemType,
                'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $item->category_id,
                'unit_id' => array_key_exists('unit_id', $data) ? $data['unit_id'] : $item->unit_id,
                'tax_rate_id' => array_key_exists('tax_rate_id', $data) ? $data['tax_rate_id'] : $item->tax_rate_id,
                'is_tax_exempt' => $data['is_tax_exempt'] ?? $item->is_tax_exempt,
                'cost_price' => $data['cost_price'] ?? $item->cost_price,
                'selling_price' => $data['selling_price'] ?? $item->selling_price,
                'minimum_selling_price' => array_key_exists('minimum_selling_price', $data)
                                            ? $data['minimum_selling_price']
                                            : $item->minimum_selling_price,
                'track_inventory' => $itemType->tracksInventory()
                                            ? ($data['track_inventory'] ?? $item->track_inventory)
                                            : false,
                'reorder_level' => $data['reorder_level'] ?? $item->reorder_level,
                'minimum_stock' => $data['minimum_stock'] ?? $item->minimum_stock,
                'maximum_stock' => array_key_exists('maximum_stock', $data) ? $data['maximum_stock'] : $item->maximum_stock,
                'allow_negative_stock' => $data['allow_negative_stock'] ?? $item->allow_negative_stock,
                'track_batch' => $data['track_batch'] ?? $item->track_batch,
                'track_serial' => $data['track_serial'] ?? $item->track_serial,
                'track_expiry' => $data['track_expiry'] ?? $item->track_expiry,
                'is_sellable' => $itemType->isSellable()
                                            ? ($data['is_sellable'] ?? $item->is_sellable)
                                            : false,
                'is_purchasable' => $itemType->isPurchasable()
                                            ? ($data['is_purchasable'] ?? $item->is_purchasable)
                                            : false,
                'updated_by' => Auth::id(),
            ];

            if ($image) {
                $newPath = $this->storeImage($image);
                if ($item->image_path && Storage::disk(self::IMAGE_DISK)->exists($item->image_path)) {
                    Storage::disk(self::IMAGE_DISK)->delete($item->image_path);
                }
                $payload['image_path'] = $newPath;
            }

            $item->fill($payload)->save();

            return $item->fresh(['category', 'unit']);
        });
    }

    public function delete(Item $item): void
    {
        // Phase 4+ will block deletion when sales/PO/inventory references exist.
        DB::transaction(fn () => $item->delete());
    }

    public function changeStatus(Item $item, RecordStatus $status): Item
    {
        if (! $item->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition item status from {$item->status->value} to {$status->value}."
            );
        }

        $item->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();

        return $item->fresh();
    }

    public function nextCode(): string
    {
        return $this->sequences->next('item', (int) now()->format('Y'));
    }

    private function storeImage(UploadedFile $image): string
    {
        $ext = strtolower($image->getClientOriginalExtension() ?: 'jpg');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
        $filename = Str::uuid()->toString().'.'.$ext;

        Storage::disk(self::IMAGE_DISK)->putFileAs(
            self::IMAGE_DIR, $image, $filename, ['visibility' => 'private'],
        );

        return self::IMAGE_DIR.'/'.$filename;
    }
}
