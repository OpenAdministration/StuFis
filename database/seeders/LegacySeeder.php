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

        \DB::table('haushaltsgruppen')->insert([
            [
                'id' => 1,
                'gruppen_name' => 'Testgruppe Einnahmen',
                'hhp_id' => 1,
                'type' => 0,
            ], [
                'id' => 2,
                'gruppen_name' => 'Testgruppe Ausgaben',
                'hhp_id' => 1,
                'type' => 1,
            ],
        ]);

        \DB::table('haushaltstitel')->insert([
            [
                'titel_name' => 'Einname 1',
                'hhpgruppen_id' => 1,
                'titel_nr' => "1",
                'value' => 1000,
            ], [
                'titel_name' => 'Ausgabe 1',
                'hhpgruppen_id' => 2,
                'titel_nr' => "1",
                'value' => 10000,
            ],
        ]);
    }
}
