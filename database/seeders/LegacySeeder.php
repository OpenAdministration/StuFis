<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LegacySeeder extends Seeder
{
    public function run()
    {
        \DB::table('haushaltsplan')->insert([
            'von' => now()->dayOfYear(1)->format('d-m-y'),
            'bis' => now()->dayOfYear(now()->dayOfYear)->format('d-m-y'),
            'state' => 'final',
        ]);
    }
}
