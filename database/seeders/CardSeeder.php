<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CardNumbers;
class CardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CardNumbers::destroy(['is_enabled' => 1]);
        CardNumbers::create([
            'card_number_name' => 'امیر حسین کریمی کفشگری',
            'card_number' => '6277-6013-3431-9629',
            'card_number_bank' => 'پست بانک',
        ]);
    }
}
