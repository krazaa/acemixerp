<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\Bank;
use Illuminate\Database\Seeder;

class PakistanBanksSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['ABL', 'Allied Bank Limited', 'ABL'],
            ['AKBL', 'Askari Bank Limited', 'Askari Bank'],
            ['BAHL', 'Bank AL Habib Limited', 'Bank AL Habib'],
            ['BAFL', 'Bank Alfalah Limited', 'Bank Alfalah'],
            ['BIPL', 'BankIslami Pakistan Limited', 'BankIslami'],
            ['BOK', 'The Bank of Khyber', 'BOK'],
            ['BOP', 'The Bank of Punjab', 'BOP'],
            ['DIBPL', 'Dubai Islamic Bank Pakistan Limited', 'Dubai Islamic Bank'],
            ['FABL', 'Faysal Bank Limited', 'Faysal Bank'],
            ['HBL', 'Habib Bank Limited', 'HBL'],
            ['HMB', 'Habib Metropolitan Bank Limited', 'HabibMetro'],
            ['JSBL', 'JS Bank Limited', 'JS Bank'],
            ['MCB', 'MCB Bank Limited', 'MCB'],
            ['MCBISL', 'MCB Islamic Bank Limited', 'MCB Islamic'],
            ['MEBL', 'Meezan Bank Limited', 'Meezan Bank'],
            ['NBP', 'National Bank of Pakistan', 'NBP'],
            ['SCBPL', 'Standard Chartered Bank (Pakistan) Limited', 'Standard Chartered'],
            ['SNBL', 'Soneri Bank Limited', 'Soneri Bank'],
            ['UBL', 'United Bank Limited', 'UBL'],
        ];

        foreach ($banks as [$code, $name, $shortName]) {
            Bank::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'short_name' => $shortName,
                    'status' => RecordStatus::Active,
                ],
            );
        }
    }
}
