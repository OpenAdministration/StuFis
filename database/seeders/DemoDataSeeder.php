<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Storage;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (App::isProduction() && config('stufis.realm') !== 'demo') {
            throw new \InvalidArgumentException('Realm is not demo but we are in production, aborting for your safety');
        }

        $sqlContent = str(Storage::disk('demo')->get('stufis-demo-data.sql'));
        $today = Carbon::today();
        $biggerYear = $today->year - 1;
        $smallerYear = $biggerYear - 1;

        $sqlContent = $sqlContent
            ->replace('2024', $biggerYear)
            ->replace('2023', $smallerYear);

        $sqlContent = $sqlContent->replace('demo__', DB::getTablePrefix());

        DB::unprepared($sqlContent);

        Storage::delete('auslagen');
        Process::run(['cp', '-r', storage_path('demo/auslagen'), storage_path('app/')]);
    }
}
