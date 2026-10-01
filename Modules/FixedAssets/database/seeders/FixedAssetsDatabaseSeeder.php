<?php

namespace Modules\FixedAssets\Database\Seeders;

use App\Enums\AccountType;
use App\Enums\ItemType;
use App\Enums\RecordStatus;
use App\Models\Account;
use App\Models\Item;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Modules\FixedAssets\Enums\AssetStatus;
use Modules\FixedAssets\Models\Asset;

class FixedAssetsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $fixedAssetHeader = Account::query()->where('code', '1200')->firstOrFail();
        $assetAccount = Account::query()->updateOrCreate(
            ['code' => '1201'],
            [
                'name' => 'Fixed Asset Cost',
                'type' => AccountType::Asset,
                'normal_balance' => AccountType::Asset->normalBalance(),
                'parent_id' => $fixedAssetHeader->id,
                'is_postable' => true,
                'is_cash' => false,
                'is_bank' => false,
                'requires_party' => false,
                'status' => RecordStatus::Active,
            ],
        );
        $accumulatedDepreciation = Account::query()->where('code', '1210')->firstOrFail();
        $depreciationExpense = Account::query()->where('code', '6200')->firstOrFail();
        $vendorId = Vendor::query()->value('id');
        $userId = User::query()->value('id');

        foreach ([
            ['FA-DEMO-000001', 'Office Workstation', '2026-01-15', '185000.0000', '37000.0000', 36, 'Head Office'],
            ['FA-DEMO-000002', 'Milk Delivery Van', '2026-02-10', '3250000.0000', '325000.0000', 60, 'Fleet Yard'],
            ['FA-DEMO-000003', 'Laboratory Analyzer', '2026-03-01', '875000.0000', '87500.0000', 60, 'Quality Lab'],
            ['FA-DEMO-000004', 'Packing Machine', '2026-04-20', '1450000.0000', '145000.0000', 84, 'Production Floor'],
            ['FA-DEMO-000005', 'Office Furniture Set', '2026-05-05', '420000.0000', '42000.0000', 60, 'Head Office'],
        ] as [$number, $name, $date, $cost, $salvage, $life, $location]) {
            $item = Item::query()->updateOrCreate(
                ['code' => "ITEM-{$number}"],
                [
                    'name' => $name,
                    'description' => "Fixed asset item for {$name}.",
                    'item_type' => ItemType::Asset,
                    'cost_price' => $cost,
                    'track_inventory' => false,
                    'is_sellable' => false,
                    'is_purchasable' => true,
                    'status' => RecordStatus::Active,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );

            Asset::query()->updateOrCreate(
                ['asset_number' => $number],
                [
                    'name' => $name,
                    'description' => 'Sample fixed asset for workflow testing.',
                    'item_id' => $item->id,
                    'vendor_id' => $vendorId,
                    'acquisition_date' => $date,
                    'cost' => $cost,
                    'salvage_value' => $salvage,
                    'useful_life_months' => $life,
                    'accumulated_depreciation' => '0.0000',
                    'carrying_amount' => $cost,
                    'status' => AssetStatus::Draft,
                    'location' => $location,
                    'asset_account_id' => $assetAccount->id,
                    'accumulated_depreciation_account_id' => $accumulatedDepreciation->id,
                    'depreciation_expense_account_id' => $depreciationExpense->id,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );
        }
    }
}
