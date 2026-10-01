<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit\Routing;

use Tests\TestCase;

class RfqAwardRouteTest extends TestCase
{
    public function test_award_route_includes_the_rfq_and_quotation(): void
    {
        $url = route(
            'procurement.quotations.award',
            ['rfq' => 7, 'quotation' => 2],
            false,
        );

        $this->assertSame('/procurement/rfqs/7/quotations/2/award', $url);
    }
}
