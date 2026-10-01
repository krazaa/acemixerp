<?php

declare(strict_types=1);

namespace Modules\FixedAssets\Services;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Exceptions\BusinessRuleException;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Modules\FixedAssets\Enums\AssetStatus;
use Modules\FixedAssets\Enums\AssetTransactionType;
use Modules\FixedAssets\Models\Asset;
use Modules\FixedAssets\Models\AssetTransaction;

final class AssetService
{
    public function __construct(private readonly JournalPoster $journals, private readonly SequenceGenerator $sequences) {}

    public function create(array $data, int $userId): Asset
    {
        return DB::transaction(function () use ($data, $userId) {
            $asset = Asset::query()->create([...$data, 'asset_number' => $this->sequences->next('fixed_asset', (int) now()->format('Y')), 'salvage_value' => $data['salvage_value'] ?? 0, 'carrying_amount' => $data['cost'], 'status' => AssetStatus::Draft, 'created_by' => $userId, 'updated_by' => $userId]);

            return $asset;
        });
    }

    public function capitalize(Asset $asset, array $data, int $userId): Asset
    {
        $this->assertStatus($asset, AssetStatus::Draft);

        return DB::transaction(function () use ($asset, $data, $userId) {
            $journal = $this->journal($asset, $data['transaction_date'], 'Capitalization', [new JournalLineData($asset->asset_account_id, $asset->cost, '0', 'Capitalized cost'), new JournalLineData((int) $data['offset_account_id'], '0', $asset->cost, 'Capitalization offset')], $userId);
            $asset->forceFill(['status' => AssetStatus::Capitalized, 'capitalized_at' => $data['transaction_date'], 'in_service_date' => $asset->in_service_date ?? $data['transaction_date'], 'capitalization_journal_entry_id' => $journal->id, 'updated_by' => $userId])->save();
            $this->record($asset, AssetTransactionType::Capitalization, $data, '0.0000', (string) $asset->carrying_amount, $journal->id, $userId);

            return $asset->fresh();
        });
    }

    public function lifecycle(Asset $asset, array $data, int $userId): Asset
    {
        $type = AssetTransactionType::from($data['type']);
        if ($type === AssetTransactionType::Capitalization) {
            return $this->capitalize($asset, $data, $userId);
        }
        $this->assertStatus($asset, AssetStatus::Capitalized);

        return DB::transaction(function () use ($asset, $data, $type, $userId) {
            $before = (string) $asset->carrying_amount;
            $after = $before;
            $journalId = null;
            if ($type === AssetTransactionType::Depreciation) {
                $monthly = bcdiv(bcsub((string) $asset->cost, (string) $asset->salvage_value, 4), (string) $asset->useful_life_months, 4);
                $remaining = bcsub($before, (string) $asset->salvage_value, 4);
                $amount = bccomp($monthly, $remaining, 4) > 0 ? $remaining : $monthly;
                if (bccomp($amount, '0', 4) <= 0) {
                    throw BusinessRuleException::make('This asset is fully depreciated.');
                }
                $journalId = $this->journal($asset, $data['transaction_date'], 'Depreciation', [new JournalLineData($asset->depreciation_expense_account_id, $amount, '0'), new JournalLineData($asset->accumulated_depreciation_account_id, '0', $amount)], $userId)->id;
                $after = bcsub($before, $amount, 4);
                $asset->accumulated_depreciation = bcadd((string) $asset->accumulated_depreciation, $amount, 4);
            } elseif ($type === AssetTransactionType::Transfer) {
                if (empty($data['to_location'])) {
                    throw BusinessRuleException::make('A destination location is required for a transfer.');
                }
                $asset->location = $data['to_location'];
            } elseif ($type === AssetTransactionType::Maintenance) {
                $amount = $this->requiredAmount($data);
                $journalId = $this->journal($asset, $data['transaction_date'], 'Maintenance', [new JournalLineData((int) $data['offset_account_id'], $amount, '0'), new JournalLineData((int) $data['funding_account_id'], '0', $amount)], $userId)->id;
            } elseif ($type === AssetTransactionType::Revaluation) {
                $after = number_format((float) ($data['new_carrying_amount'] ?? -1), 4, '.', '');
                $difference = bcsub($after, $before, 4);
                if (bccomp($difference, '0', 4) === 0 || empty($data['offset_account_id'])) {
                    throw BusinessRuleException::make('A changed carrying amount and revaluation account are required.');
                }
                $absolute = bccomp($difference, '0', 4) > 0 ? $difference : bcmul($difference, '-1', 4);
                $journalId = bccomp($difference, '0', 4) > 0 ? $this->journal($asset, $data['transaction_date'], 'Revaluation gain', [new JournalLineData($asset->asset_account_id, $absolute, '0'), new JournalLineData((int) $data['offset_account_id'], '0', $absolute)], $userId)->id : $this->journal($asset, $data['transaction_date'], 'Revaluation loss', [new JournalLineData((int) $data['offset_account_id'], $absolute, '0'), new JournalLineData($asset->asset_account_id, '0', $absolute)], $userId)->id;
            } else {
                $proceeds = number_format((float) ($data['amount'] ?? 0), 4, '.', '');
                if (empty($data['offset_account_id']) || empty($data['gain_loss_account_id'])) {
                    throw BusinessRuleException::make('Disposal requires proceeds and gain/loss accounts.');
                }
                $gainLoss = bcsub($proceeds, $before, 4);
                $lines = [new JournalLineData($asset->accumulated_depreciation_account_id, (string) $asset->accumulated_depreciation, '0'), new JournalLineData((int) $data['offset_account_id'], $proceeds, '0')];
                $lines[] = bccomp($gainLoss, '0', 4) >= 0 ? new JournalLineData((int) $data['gain_loss_account_id'], '0', $gainLoss) : new JournalLineData((int) $data['gain_loss_account_id'], bcmul($gainLoss, '-1', 4), '0');
                $lines[] = new JournalLineData($asset->asset_account_id, '0', (string) $asset->cost);
                $journalId = $this->journal($asset, $data['transaction_date'], 'Disposal', $lines, $userId)->id;
                $after = '0.0000';
                $asset->status = AssetStatus::Disposed;
            }
            $asset->carrying_amount = $after;
            $asset->updated_by = $userId;
            $asset->save();
            $this->record($asset, $type, $data, $before, $after, $journalId, $userId);

            return $asset->fresh();
        });
    }

    private function journal(Asset $asset, string $date, string $description, array $lines, int $userId): JournalEntry
    {
        return $this->journals->post(new JournalEntryData(new \DateTimeImmutable($date), "Asset {$asset->asset_number} — {$description}", $lines, $asset->asset_number), ['source_type' => Asset::class, 'source_id' => $asset->id, 'user_id' => $userId]);
    }

    private function record(Asset $asset, AssetTransactionType $type, array $data, string $before, string $after, ?int $journalId, int $userId): void
    {
        AssetTransaction::query()->create(['asset_id' => $asset->id, 'type' => $type, 'transaction_date' => $data['transaction_date'], 'amount' => $data['amount'] ?? 0, 'reference' => $data['reference'] ?? null, 'description' => $data['description'] ?? null, 'from_location' => $data['from_location'] ?? null, 'to_location' => $data['to_location'] ?? null, 'carrying_amount_before' => $before, 'carrying_amount_after' => $after, 'journal_entry_id' => $journalId, 'created_by' => $userId]);
    }

    private function assertStatus(Asset $asset, AssetStatus $status): void
    {
        if ($asset->status !== $status) {
            throw BusinessRuleException::make("Asset {$asset->asset_number} must be {$status->label()}.");
        }
    }

    private function requiredAmount(array $data): string
    {
        if (empty($data['offset_account_id']) || empty($data['funding_account_id']) || empty($data['amount']) || (float) $data['amount'] <= 0) {
            throw BusinessRuleException::make('A maintenance expense account, funding account, and amount are required.');
        }

        return number_format((float) $data['amount'], 4, '.', '');
    }
}
