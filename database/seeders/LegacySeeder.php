<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LegacySeeder extends Seeder
{
    public function run()
    {
        \DB::table('haushaltsplan')->insert([
            'von' => now()->dayOfYear(1)->format('Y-m-d'),
            'bis' => now()->lastOfYear()->format('Y-m-d'),
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
                'titel_name' => 'Einnahme 1',
                'hhpgruppen_id' => 1,
                'titel_nr' => "E1",
                'value' => 1000,
            ], [
                'titel_name' => 'Ausgabe 1',
                'hhpgruppen_id' => 2,
                'titel_nr' => "A1",
                'value' => 10000,
            ],
        ]);

        \DB::table('konto_type')->insert([
            'name' => 'Cash',
            'short' => 'C',
        ]);
        // insert does not take 0 as id
        \DB::table('konto_type')->where('short', '=', 'C')->update([
            'id' => 0,
        ]);
    }
}
