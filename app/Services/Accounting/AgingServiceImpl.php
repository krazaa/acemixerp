<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Contracts\AgingService as AgingServiceContract;
use App\Data\AgingBucket;
use App\Enums\JournalEntryStatus;
use App\Enums\SystemAccountRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AgingServiceImpl implements AgingServiceContract
{
    public function customerAging(?\DateTimeInterface $asOf = null): Collection
    {
        return $this->agingFor(false, $asOf);
    }

    public function vendorAging(?\DateTimeInterface $asOf = null): Collection
    {
        return $this->agingFor(true, $asOf);
    }

    /** @return Collection<int, object> */
    private function agingFor(bool $payables, ?\DateTimeInterface $asOf): Collection
    {
        $asOf = ($asOf ? CarbonImmutable::parse($asOf) : CarbonImmutable::now())->startOfDay();
        $table = $payables ? 'vendors' : 'customers';
        $column = $payables ? 'vendor_id' : 'customer_id';
        $accountColumn = $payables ? 'ap_account_id' : 'ar_account_id';
        $role = $payables ? SystemAccountRole::AccountsPayable : SystemAccountRole::AccountsReceivable;

        $rows = DB::table('journal_lines as lines')
            ->join('journal_entries as entries', 'entries.id', '=', 'lines.journal_entry_id')
            ->leftJoin($table.' as parties', 'parties.id', '=', 'lines.'.$column)
            ->whereIn('entries.status', [JournalEntryStatus::Posted->value, JournalEntryStatus::Reversed->value])
            ->where('entries.entry_date', '<=', $asOf->toDateString())
            ->where(function (Builder $query) use ($role, $accountColumn): void {
                $query->whereIn('lines.account_id', DB::table('system_accounts')->select('account_id')->where('role', $role->value))
                    ->orWhereColumn('lines.account_id', 'parties.'.$accountColumn);
            })
            ->select('parties.id as party_id', 'parties.name as party_name', 'entries.currency_code', 'entries.entry_date')
            ->selectRaw('SUM(lines.debit) as debit, SUM(lines.credit) as credit')
            ->groupBy('parties.id', 'parties.name', 'entries.currency_code', 'entries.entry_date')
            ->orderBy('entries.currency_code')->orderBy('parties.name')->orderBy('parties.id')
            ->get();

        $buckets = AgingBucket::standard();
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->currency_code.':'.($row->party_id ?? 'unassigned');
            $grouped[$key] ??= [
                'party_id' => $row->party_id,
                'party_name' => $row->party_name ?? 'Unassigned party',
                'currency_code' => $row->currency_code,
                'buckets' => array_fill(0, count($buckets), '0.0000'),
                'total' => '0.0000',
            ];
            $balance = $payables
                ? bcsub((string) $row->credit, (string) $row->debit, 4)
                : bcsub((string) $row->debit, (string) $row->credit, 4);
            $days = (int) CarbonImmutable::parse($row->entry_date)->startOfDay()->diffInDays($asOf, false);
            foreach ($buckets as $index => $bucket) {
                if ($bucket->contains($days)) {
                    $grouped[$key]['buckets'][$index] = bcadd($grouped[$key]['buckets'][$index], $balance, 4);
                    break;
                }
            }
            $grouped[$key]['total'] = bcadd($grouped[$key]['total'], $balance, 4);
        }

        return collect($grouped)
            ->filter(fn (array $row): bool => bccomp($row['total'], '0', 4) !== 0)
            ->map(fn (array $row): object => (object) [
                'party_id' => $row['party_id'],
                'party_name' => $row['party_name'],
                'currency_code' => $row['currency_code'],
                'current' => $row['buckets'][0],
                'days_1_30' => $row['buckets'][1],
                'days_31_60' => $row['buckets'][2],
                'days_61_90' => $row['buckets'][3],
                'days_90_plus' => $row['buckets'][4],
                'total' => $row['total'],
            ])->values();
    }
}
