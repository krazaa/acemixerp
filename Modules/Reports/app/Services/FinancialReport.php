<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use App\Enums\AccountType;
use App\Enums\JournalEntryStatus;
use App\Enums\SystemAccountRole;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class FinancialReport
{
    /** @return Collection<int, stdClass> */
    public function partyBalances(bool $payables, string $asOf, ?string $currency, ?int $partyId): Collection
    {
        $table = $payables ? 'vendors' : 'customers';
        $column = $payables ? 'vendor_id' : 'customer_id';
        $accountColumn = $payables ? 'ap_account_id' : 'ar_account_id';
        $role = $payables ? SystemAccountRole::AccountsPayable : SystemAccountRole::AccountsReceivable;

        return $this->postedLines($asOf, $currency)
            ->leftJoin($table.' as parties', 'parties.id', '=', 'lines.'.$column)
            ->where(function (Builder $query) use ($role, $accountColumn): void {
                $query->whereIn('lines.account_id', DB::table('system_accounts')->select('account_id')->where('role', $role->value))
                    ->orWhereColumn('lines.account_id', 'parties.'.$accountColumn);
            })
            ->when($partyId, fn (Builder $query): Builder => $query->where('lines.'.$column, $partyId))
            ->select('entries.currency_code', 'parties.id as party_id', 'parties.code', 'parties.name')
            ->selectRaw('SUM(lines.debit) as debit, SUM(lines.credit) as credit')
            ->groupBy('entries.currency_code', 'parties.id', 'parties.code', 'parties.name')
            ->orderBy('entries.currency_code')->orderBy('parties.code')->orderBy('parties.id')
            ->get()
            ->map(function (stdClass $row) use ($payables): stdClass {
                $row->balance = $payables
                    ? bcsub((string) $row->credit, (string) $row->debit, 4)
                    : bcsub((string) $row->debit, (string) $row->credit, 4);

                return $row;
            })
            ->filter(fn (stdClass $row): bool => bccomp($row->balance, '0', 4) !== 0)
            ->values();
    }

    /** @return Collection<int, stdClass> */
    public function trialBalance(string $asOf, ?string $currency): Collection
    {
        return $this->postedLines($asOf, $currency)
            ->join('accounts', 'accounts.id', '=', 'lines.account_id')
            ->select('entries.currency_code', 'accounts.id', 'accounts.code', 'accounts.name')
            ->selectRaw('SUM(lines.debit) as debit_total, SUM(lines.credit) as credit_total')
            ->groupBy('entries.currency_code', 'accounts.id', 'accounts.code', 'accounts.name')
            ->orderBy('entries.currency_code')->orderBy('accounts.code')->orderBy('accounts.id')
            ->get()
            ->map(function (stdClass $row): stdClass {
                $balance = bcsub((string) $row->debit_total, (string) $row->credit_total, 4);
                $row->debit = bccomp($balance, '0', 4) > 0 ? $balance : '0.0000';
                $row->credit = bccomp($balance, '0', 4) < 0 ? bcsub('0', $balance, 4) : '0.0000';

                return $row;
            });
    }

    /**
     * @return Collection<string, array{sections: array<string, Collection<int, stdClass>>, asset: string, liability: string, equity: string, earnings: string, liabilities_and_equity: string, difference: string}>
     */
    public function balanceSheet(string $asOf, ?string $currency): Collection
    {
        $rows = $this->postedLines($asOf, $currency)
            ->join('accounts', 'accounts.id', '=', 'lines.account_id')
            ->select('entries.currency_code', 'accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->selectRaw('SUM(lines.debit) as debit, SUM(lines.credit) as credit')
            ->groupBy('entries.currency_code', 'accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('entries.currency_code')->orderBy('accounts.code')->orderBy('accounts.id')
            ->get();

        return $rows->groupBy('currency_code')->map(function (Collection $rows): array {
            $sections = ['asset' => collect(), 'liability' => collect(), 'equity' => collect()];
            $totals = ['asset' => '0.0000', 'liability' => '0.0000', 'equity' => '0.0000', 'earnings' => '0.0000'];

            foreach ($rows as $row) {
                $type = AccountType::from($row->type);
                $row->balance = $type === AccountType::Asset
                    ? bcsub((string) $row->debit, (string) $row->credit, 4)
                    : bcsub((string) $row->credit, (string) $row->debit, 4);

                if ($type->isProfitAndLoss()) {
                    $totals['earnings'] = bcadd($totals['earnings'], $row->balance, 4);
                } else {
                    $totals[$type->value] = bcadd($totals[$type->value], $row->balance, 4);
                    if (bccomp($row->balance, '0', 4) !== 0) {
                        $sections[$type->value]->push($row);
                    }
                }
            }

            $totals['equity'] = bcadd($totals['equity'], $totals['earnings'], 4);
            $liabilitiesAndEquity = bcadd($totals['liability'], $totals['equity'], 4);

            return [
                'sections' => $sections,
                ...$totals,
                'liabilities_and_equity' => $liabilitiesAndEquity,
                'difference' => bcsub($totals['asset'], $liabilitiesAndEquity, 4),
            ];
        });
    }

    /**
     * @param  Collection<int, stdClass>  $rows
     * @return Collection<string, array{rows: Collection<int, stdClass>, debit: string, credit: string, balance: string}>
     */
    public function currencyGroups(Collection $rows): Collection
    {
        return $rows->groupBy('currency_code')->map(function (Collection $rows): array {
            $totals = ['debit' => '0.0000', 'credit' => '0.0000', 'balance' => '0.0000'];
            foreach ($rows as $row) {
                foreach (array_keys($totals) as $field) {
                    $totals[$field] = bcadd($totals[$field], (string) ($row->{$field} ?? '0'), 4);
                }
            }

            return ['rows' => $rows, ...$totals];
        });
    }

    /** @return Collection<int, stdClass> */
    public function parties(bool $payables): Collection
    {
        return DB::table($payables ? 'vendors' : 'customers')
            ->select('id', 'code', 'name')->orderBy('name')->orderBy('id')->get();
    }

    private function postedLines(string $asOf, ?string $currency): Builder
    {
        return DB::table('journal_lines as lines')
            ->join('journal_entries as entries', 'entries.id', '=', 'lines.journal_entry_id')
            ->whereIn('entries.status', [JournalEntryStatus::Posted->value, JournalEntryStatus::Reversed->value])
            ->where('entries.entry_date', '<=', $asOf)
            ->when($currency, fn (Builder $query): Builder => $query->where('entries.currency_code', $currency));
    }
}
