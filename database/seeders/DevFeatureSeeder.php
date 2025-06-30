<?php

namespace Database\Seeders;

use App\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DevFeatureSeeder extends Seeder
{
    public function run(): void
    {
        FiscalYear::create([
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addYear()->subDay(),
        ]);
    }
}
