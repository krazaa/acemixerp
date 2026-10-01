<?php

namespace App\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class JournalEntryData
{
    /** @param JournalLineData[] $lines */
    public function __construct(
        public \DateTimeInterface $entryDate,
        public string $description,
        public array $lines,
        public ?string $reference = null,
        public ?string $currencyCode = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $rawLines = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['account_id']))
            ->values();

        $lines = $rawLines
            ->map(fn ($row, $i) => JournalLineData::fromArray($row, $i))
            ->all();

        return new self(
            entryDate: Carbon::parse($request->input('entry_date')),
            description: (string) $request->input('description'),
            lines: $lines,
            reference: $request->input('reference'),
            currencyCode: $request->input('currency_code'),
            notes: $request->input('notes'),
        );
    }

    public function totalDebit(): string
    {
        return $this->sum(fn (JournalLineData $l) => $l->debit);
    }

    public function totalCredit(): string
    {
        return $this->sum(fn (JournalLineData $l) => $l->credit);
    }

    public function isBalanced(): bool
    {
        return bccomp($this->totalDebit(), $this->totalCredit(), 4) === 0;
    }

    private function sum(callable $selector): string
    {
        return array_reduce(
            $this->lines,
            fn (string $carry, JournalLineData $line): string => bcadd($carry, (string) $selector($line), 4),
            '0.0000',
        );
    }
}
