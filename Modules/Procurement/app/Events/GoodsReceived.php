<?php

namespace Modules\Procurement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Procurement\Models\GoodsReceipt;

class GoodsReceived
{
    use Dispatchable;

    public function __construct(public readonly GoodsReceipt $receipt) {}
}
