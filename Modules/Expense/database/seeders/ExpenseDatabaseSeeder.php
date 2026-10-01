<?php

namespace Modules\Expense\Database\Seeders;

use App\Contracts\SequenceGenerator;
use Illuminate\Database\Seeder;

class ExpenseDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(SequenceGenerator::class)->register('expense_claim', 'EXP');
    }
}
