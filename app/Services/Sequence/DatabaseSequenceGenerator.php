<?php

namespace App\Services\Sequence;

use App\Contracts\SequenceGenerator;
use App\Exceptions\ConcurrencyException;
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

final class DatabaseSequenceGenerator implements SequenceGenerator
{
    public function next(string $key, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($key, $year) {
            /** @var DocumentSequence $seq */
            $seq = DocumentSequence::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                throw new ConcurrencyException("Sequence [{$key}] is not registered.");
            }

            // Yearly reset
            if ($seq->reset_yearly && $seq->last_reset_year !== $year) {
                $seq->current_value = 0;
                $seq->last_reset_year = $year;
            }

            $seq->current_value += 1;
            $seq->save();

            return $this->format($seq, $year);
        }, attempts: 3);
    }

    public function peek(string $key, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $seq = DocumentSequence::query()->where('key', $key)->firstOrFail();

        return $this->format($seq, $year, preview: true);
    }

    public function register(string $key, string $prefix, int $padding = 6, bool $resetYearly = true): void
    {
        DocumentSequence::query()->firstOrCreate(
            ['key' => $key],
            [
                'prefix' => $prefix,
                'pattern' => '{prefix}-{year}-{number}',
                'padding' => $padding,
                'reset_yearly' => $resetYearly,
                'current_value' => 0,
                'last_reset_year' => null,
            ],
        );
    }

    private function format(DocumentSequence $seq, int $year, bool $preview = false): string
    {
        $value = $preview ? $seq->current_value + 1 : $seq->current_value;

        return strtr($seq->pattern, [
            '{prefix}' => $seq->prefix,
            '{year}' => (string) $year,
            '{number}' => str_pad((string) $value, $seq->padding, '0', STR_PAD_LEFT),
        ]);
    }
}
