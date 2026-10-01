<?php

namespace Modules\Procurement\Contracts;

use Modules\Procurement\Data\VendorQuotationData;
use Modules\Procurement\Models\RequestForQuotation;
use Modules\Procurement\Models\VendorQuotation;

interface VendorQuotationManager
{
    public function record(RequestForQuotation $rfq, VendorQuotationData $data, int $userId): VendorQuotation;

    public function submit(VendorQuotation $quotation, int $userId): VendorQuotation;

    public function award(RequestForQuotation $rfq, VendorQuotation $quotation, int $userId): VendorQuotation;

    public function comparison(RequestForQuotation $rfq): array;
}
