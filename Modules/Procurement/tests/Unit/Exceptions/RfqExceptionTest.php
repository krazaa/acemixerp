<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit\Exceptions;

use App\Exceptions\DomainException;
use Modules\Procurement\Enums\RfqStatus;
use Modules\Procurement\Exceptions\RfqException;
use Modules\Procurement\Models\RequestForQuotation;
use Tests\TestCase;

class RfqExceptionTest extends TestCase
{
    public function test_invalid_transition_returns_a_domain_exception(): void
    {
        $rfq = new RequestForQuotation;
        $rfq->forceFill([
            'number' => 'RFQ-2026-000001',
            'status' => RfqStatus::Issued,
        ]);

        $exception = RfqException::invalidTransition($rfq, 'award');

        $this->assertInstanceOf(DomainException::class, $exception);
        $this->assertSame(
            'RFQ RFQ-2026-000001 cannot award from status Issued.',
            $exception->getMessage(),
        );
        $this->assertSame(422, $exception->httpStatus());
    }
}
