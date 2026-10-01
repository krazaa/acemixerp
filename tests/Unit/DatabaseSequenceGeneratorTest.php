<?php

declare(strict_types=1);

namespace Tests\Unit\Sequence;

use App\Contracts\SequenceGenerator;
use App\Exceptions\ConcurrencyException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSequenceGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_sequential_numbers(): void
    {
        /** @var SequenceGenerator $gen */
        $gen = app(SequenceGenerator::class);
        $gen->register('test', 'TS', 4, false);

        $this->assertSame('TS-2026-0001', $this->withYear($gen, 2026));
        $this->assertSame('TS-2026-0002', $this->withYear($gen, 2026));
        $this->assertSame('TS-2026-0003', $this->withYear($gen, 2026));
    }

    public function test_yearly_reset(): void
    {
        $gen = app(SequenceGenerator::class);
        $gen->register('yearly', 'YR', 4, true);

        $this->assertSame('YR-2026-0001', $gen->next('yearly', 2026));
        $this->assertSame('YR-2027-0001', $gen->next('yearly', 2027));
        $this->assertSame('YR-2027-0002', $gen->next('yearly', 2027));
    }

    public function test_unregistered_sequence_throws(): void
    {
        $this->expectException(ConcurrencyException::class);
        app(SequenceGenerator::class)->next('never-registered');
    }

    private function withYear(SequenceGenerator $gen, int $year): string
    {
        return $gen->next('test', $year);
    }
}
