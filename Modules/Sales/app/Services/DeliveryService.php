<?php

namespace Modules\Sales\Services;

use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Data\StockMovementData;
use App\Enums\StockMovementType;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Contracts\DeliveryManager;
use Modules\Sales\Data\DeliveryData;
use Modules\Sales\Data\DeliveryLineData;
use Modules\Sales\Data\DispatchDeliveryData;
use Modules\Sales\Enums\DeliveryStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Exceptions\DeliveryException;
use Modules\Sales\Models\Delivery;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

final class DeliveryService implements DeliveryManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly StockLedger $stockLedger,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Delivery::query()
            ->with(['customer:id,code,name', 'salesOrder:id,number', 'warehouse:id,code,name'])
            ->withCount('lines')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['customer_id'] ?? null, fn ($q, $c) => $q->where('customer_id', $c))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('delivery_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('delivery_date', '<=', $d))
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(DeliveryData $data, int $userId): Delivery
    {
        if (count($data->lines) === 0) {
            throw DeliveryException::noLines();
        }

        $order = SalesOrder::query()->findOrFail($data->salesOrderId);

        if (! $order->status->acceptsDelivery()) {
            throw DeliveryException::orderNotDeliverable($order);
        }

        if (! $order->warehouse_id) {
            throw DeliveryException::orderMissingWarehouse($order);
        }

        return DB::transaction(function () use ($data, $order, $userId) {
            $delivery = Delivery::query()->create([
                'number' => $this->sequences->next('delivery', (int) $data->deliveryDate->format('Y')),
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'warehouse_id' => $order->warehouse_id,
                'delivery_date' => $data->deliveryDate,
                'expected_date' => $data->expectedDate,
                'reference' => $data->reference,
                'carrier' => $data->carrier,
                'vendor_id' => $data->vendorId,
                'tracking_number' => $data->trackingNumber,
                'shipping_address' => $data->shippingAddress,
                'notes' => $data->notes,
                'status' => DeliveryStatus::Draft,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $delivery->lines()->create($line->toArray());
            }

            return $delivery->fresh(['lines.item', 'salesOrder', 'customer']);
        });
    }

    public function createFromSalesOrder(SalesOrder $order, int $userId): Delivery
    {
        if (! $order->status->acceptsDelivery()) {
            throw DeliveryException::orderNotDeliverable($order);
        }

        // Pre-populate with the full open quantities.
        $lines = $order->lines->map(function ($line, int $i) {
            if (bccomp((string) $line->openQuantity(), '0', 4) <= 0) {
                return null;
            }

            return [
                'sales_order_line_id' => $line->id,
                'item_id' => $line->item_id,
                'unit_id' => $line->unit_id,
                'quantity' => $line->openQuantity(),
                'unit_price' => (string) $line->unit_price,
                'notes' => null,
                'position' => $i,
            ];
        })->filter()->values()->all();

        return $this->create(new DeliveryData(
            salesOrderId: $order->id,
            deliveryDate: now(),
            lines: array_map(
                fn ($row, $i) => DeliveryLineData::fromArray($row, $i),
                $lines,
                array_keys($lines),
            ),
            shippingAddress: $order->customer?->shippingAddress()?->oneLine(),
        ), $userId);
    }

    public function update(Delivery $delivery, DeliveryData $data, int $userId): Delivery
    {
        if (! $delivery->status->isEditable()) {
            throw DeliveryException::notEditable($delivery);
        }

        return DB::transaction(function () use ($delivery, $data, $userId) {
            $delivery->lines()->delete();

            foreach ($data->lines as $line) {
                $delivery->lines()->create($line->toArray());
            }

            $delivery->fill([
                'delivery_date' => $data->deliveryDate,
                'expected_date' => $data->expectedDate,
                'reference' => $data->reference,
                'carrier' => $data->carrier,
                'tracking_number' => $data->trackingNumber,
                'shipping_address' => $data->shippingAddress,
                'notes' => $data->notes,
                'updated_by' => $userId,
            ])->save();

            return $delivery->fresh(['lines.item', 'salesOrder']);
        });
    }

    public function pick(Delivery $delivery, int $userId): Delivery
    {
        if ($delivery->hasBeenDispatched()) {
            throw DeliveryException::alreadyDispatched($delivery);
        }

        if (! $delivery->status->canPick()) {
            throw DeliveryException::invalidTransition($delivery, 'pick');
        }

        // Validate each line: quantity must not exceed the SO line's open quantity.
        foreach ($delivery->lines as $line) {
            $soLine = $line->salesOrderLine;
            if (bccomp((string) $line->quantity, $soLine->openQuantity(), 4) > 0) {
                throw DeliveryException::overDelivery(
                    $soLine->item?->name ?? 'Item',
                    $soLine->openQuantity(),
                    (string) $line->quantity,
                );
            }
        }

        $delivery->forceFill([
            'status' => DeliveryStatus::Picked,
            'picked_at' => now(),
            'picked_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $delivery->fresh();
    }

    /**
     * Dispatch the delivery: record stock movements and advance the SO.
     */
    // public function dispatch(Delivery $delivery, DispatchDeliveryData $data, int $userId): Delivery
    // {
    //     if ($delivery->hasBeenDispatched()) {
    //         throw DeliveryException::alreadyDispatched($delivery);
    //     }

    //     if (! $delivery->status->canDispatch()) {
    //         throw DeliveryException::invalidTransition($delivery, 'dispatch');
    //     }

    //     if ($delivery->lines()->count() === 0) {
    //         throw DeliveryException::noLines();
    //     }

    //     return DB::transaction(function () use ($delivery, $data, $userId) {
    //         /** @var Delivery $locked */
    //         $locked = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);

    //         if ($locked->hasBeenDispatched()) {
    //             throw DeliveryException::alreadyDispatched($locked);
    //         }

    //         /** @var SalesOrder $order */
    //         $order = SalesOrder::query()->lockForUpdate()->findOrFail($locked->sales_order_id);

    //         $movements = [];
    //         $dispatchAt = now();

    //         foreach ($locked->lines as $line) {
    //             /** @var SalesOrderLine $soLine */
    //             $soLine = SalesOrderLine::query()
    //                 ->lockForUpdate()
    //                 ->findOrFail($line->sales_order_line_id);

    //             if (bccomp((string) $line->quantity, $soLine->openQuantity(), 4) > 0) {
    //                 throw DeliveryException::overDelivery(
    //                     $soLine->item?->name ?? 'Item',
    //                     $soLine->openQuantity(),
    //                     (string) $line->quantity,
    //                 );
    //             }

    //             // Only stock items move through the ledger.
    //             $item = $line->item;
    //             if ($item && $item->item_type->tracksInventory()) {
    //                 $movements[] = new StockMovementData(
    //                     itemId: $line->item_id,
    //                     warehouseId: $locked->warehouse_id,
    //                     quantity: bcmul((string) $line->quantity, '-1', 4),  // outbound
    //                     type: StockMovementType::Sale,
    //                     occurredAt: $dispatchAt,
    //                     reference: $locked->number,
    //                     notes: "Delivery {$locked->number}",
    //                     sourceType: Delivery::class,
    //                     sourceId: $locked->id,
    //                 );
    //             }

    //             // Advance the SO line's delivered quantity.
    //             $soLine->forceFill([
    //                 'delivered_quantity' => bcadd(
    //                     (string) $soLine->delivered_quantity,
    //                     (string) $line->quantity,
    //                     4,
    //                 ),
    //             ])->save();
    //         }

    //         // Record all movements in one batch.
    //         if (! empty($movements)) {
    //             $this->stockLedger->recordMany($movements);
    //         }

    //         $locked->forceFill([
    //             'status' => DeliveryStatus::Dispatched,
    //             'vehicle_number' => $data->vehicleNumber,
    //             'driver_name' => $data->driverName,
    //             'driver_contact_number' => $data->driverContactNumber,
    //             'driver_cnic' => $data->driverCnic,
    //             'bilty_number' => $data->biltyNumber,
    //             'dispatched_at' => $dispatchAt,
    //             'dispatched_by' => $userId,
    //             'updated_by' => $userId,
    //         ])->save();

    //         // Recompute the SO status.
    //         $order->refresh()->load('lines');
    //         if ($order->isFullyDelivered()) {
    //             $order->forceFill(['status' => SalesOrderStatus::Delivered])->save();
    //         } elseif ($order->isPartiallyDelivered()) {
    //             $order->forceFill(['status' => SalesOrderStatus::PartiallyDelivered])->save();
    //         }

    //         return $locked->fresh(['lines.item', 'salesOrder']);
    //     });
    // }

    public function dispatch(Delivery $delivery, int $userId, array $dispatchDetails = []): Delivery
    {
        if (! $delivery->status->canDispatch()) {
            throw DeliveryException::invalidTransition($delivery, 'dispatch');
        }

        if ($delivery->lines()->count() === 0) {
            throw DeliveryException::noLines();
        }

        return DB::transaction(function () use ($delivery, $userId, $dispatchDetails) {
            /** @var Delivery $locked */
            $locked = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);

            /** @var SalesOrder $order */
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($locked->sales_order_id);

            $movements = [];
            $dispatchAt = now();

            foreach ($locked->lines as $line) {
                /** @var SalesOrderLine $soLine */
                $soLine = SalesOrderLine::query()
                    ->lockForUpdate()
                    ->findOrFail($line->sales_order_line_id);

                if (bccomp((string) $line->quantity, $soLine->openQuantity(), 4) > 0) {
                    throw DeliveryException::overDelivery(
                        $soLine->item?->name ?? 'Item',
                        $soLine->openQuantity(),
                        (string) $line->quantity,
                    );
                }

                $item = $line->item;
                if ($item && $item->item_type->tracksInventory()) {
                    $movements[] = new StockMovementData(
                        itemId: $line->item_id,
                        warehouseId: $locked->warehouse_id,
                        quantity: bcmul((string) $line->quantity, '-1', 4),
                        type: StockMovementType::Sale,
                        occurredAt: $dispatchAt,
                        reference: $locked->number,
                        notes: "Delivery {$locked->number}",
                        sourceType: Delivery::class,
                        sourceId: $locked->id,
                    );
                }

                $soLine->forceFill([
                    'delivered_quantity' => bcadd(
                        (string) $soLine->delivered_quantity,
                        (string) $line->quantity,
                        4,
                    ),
                ])->save();
            }

            if (! empty($movements)) {
                $this->stockLedger->recordMany($movements);
            }

            $locked->forceFill([
                'status' => DeliveryStatus::Dispatched,
                'dispatched_at' => $dispatchAt,
                'dispatched_by' => $userId,
                'vehicle_number' => $dispatchDetails['vehicle_number'] ?? $locked->vehicle_number,
                'driver_name' => $dispatchDetails['driver_name'] ?? $locked->driver_name,
                'driver_contact' => $dispatchDetails['driver_contact'] ?? $locked->driver_contact,
                'driver_cnic' => $dispatchDetails['driver_cnic'] ?? $locked->driver_cnic,
                'bilty_number' => $dispatchDetails['bilty_number'] ?? $locked->bilty_number,
                'updated_by' => $userId,
            ])->save();

            $order->refresh()->load('lines');
            if ($order->isFullyDelivered()) {
                $order->forceFill(['status' => SalesOrderStatus::Delivered])->save();
            } elseif ($order->isPartiallyDelivered()) {
                $order->forceFill(['status' => SalesOrderStatus::PartiallyDelivered])->save();
            }

            return $locked->fresh(['lines.item', 'salesOrder']);
        });
    }

    public function markDelivered(Delivery $delivery, int $userId): Delivery
    {
        if (! $delivery->status->canMarkDelivered() && ! $delivery->hasBeenDispatched()) {
            throw DeliveryException::invalidTransition($delivery, 'mark delivered');
        }

        $delivery->forceFill([
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => now(),
            'delivered_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $delivery->fresh();
    }

    public function close(Delivery $delivery, int $userId): Delivery
    {
        if (! $delivery->status->canClose()) {
            throw DeliveryException::invalidTransition($delivery, 'close');
        }

        $delivery->forceFill([
            'status' => DeliveryStatus::Closed,
            'updated_by' => $userId,
        ])->save();

        return $delivery->fresh();
    }

    public function cancel(Delivery $delivery, int $userId, ?string $reason = null): Delivery
    {
        if (! $delivery->status->canCancel()) {
            throw DeliveryException::invalidTransition($delivery, 'cancel');
        }

        $delivery->forceFill([
            'status' => DeliveryStatus::Cancelled,
            'notes' => $reason
                ? trim(($delivery->notes ?? '')."\nCancelled: ".$reason)
                : $delivery->notes,
            'updated_by' => $userId,
        ])->save();

        return $delivery->fresh();
    }

    public function delete(Delivery $delivery): void
    {
        if ($delivery->status !== DeliveryStatus::Draft) {
            throw DeliveryException::cannotDelete($delivery);
        }
        DB::transaction(fn () => $delivery->delete());
    }

    public function allTransportProviders(): Collection
    {
        return Vendor::query()
            ->transportProviders()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }
}
