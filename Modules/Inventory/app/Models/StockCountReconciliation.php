<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Support\Collection;

/**
 * Read-only projection that summarises the financial impact of a stock count.
 * Not persisted — instantiated on demand from a StockCount instance.
 *
 * Usage:
 *   $reconciliation = StockCountReconciliation::for($count);
 *   $reconciliation->lines();       // collection of per-line summaries
 *   $reconciliation->totalVarianceValue();  // signed money impact
 */
final class StockCountReconciliation
{
    public function __construct(
        private readonly StockCount $count,
    ) {}

    public static function for(StockCount $count): self
    {
        return new self($count->loadMissing('lines.item'));
    }

    /** @return Collection<int, object> */
    public function lines(): Collection
    {
        return $this->count->lines->map(fn (StockCountLine $line) => (object) [
            'item_id' => $line->item_id,
            'item_code' => $line->item?->code,
            'item_name' => $line->item?->name,
            'system_quantity' => (string) $line->system_quantity,
            'counted_quantity' => (string) $line->counted_quantity,
            'variance' => (string) $line->variance,
            'unit_cost' => (string) $line->unit_cost,
            'variance_value' => $line->varianceValue(),
        ]);
    }

    /** Signed total money impact of the count. */
    public function totalVarianceValue(): string
    {
        return (string) $this->count->lines
            ->reduce(fn (string $carry, StockCountLine $line) => bcadd($carry, $line->varianceValue(), 4), '0.0000');
    }

    public function overageCount(): int
    {
        return $this->count->lines->filter(fn ($l) => $l->hasVariance() && bccomp((string) $l->variance, '0', 4) > 0)->count();
    }

    public function shortfallCount(): int
    {
        return $this->count->lines->filter(fn ($l) => $l->hasVariance() && bccomp((string) $l->variance, '0', 4) < 0)->count();
    }
}
