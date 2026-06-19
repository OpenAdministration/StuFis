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

        $today = Carbon::today();

        // The demo data holds two fiscal years (April–March) that span three calendar
        // years (2023–2025): the closed budget plan 2023-04-01..2024-03-31 and the open
        // plan 2024-04-01..NULL. Fiscal years are anchored on April, so before April we
        // still belong to the previous one. We pick a whole-year shift so the open plan
        // (von 2024-04-01) contains today, then apply it uniformly to every year.
        $targetOpenYear = $today->month < 4 ? $today->year - 1 : $today->year;
        $delta = $targetOpenYear - 2024;

        // Single-pass shift of the known data years only. The digit-boundary guards keep
        // us from touching years embedded in longer numbers (amounts, IBANs, refs), and a
        // single pass avoids the cascade where a replaced year is matched again.
        $sqlContent = str(Storage::disk('demo')->get('stufis-demo-data.sql'))
            ->replaceMatches(
                '/(?<!\d)(2023|2024|2025)(?!\d)/',
                fn (array $matches): string => (string) ((int) $matches[1] + $delta),
            )
            ->replace('demo__', DB::getTablePrefix());

        DB::unprepared($sqlContent);

        // deleteDirectory (not delete, which only removes files) so a re-seed wipes the
        // whole tree — including runtime-generated PDFs (belege-pdf-v*, zahlungsanweisung-v*)
        // and avoids cp nesting the demo copy inside a surviving auslagen/ directory.
        Storage::deleteDirectory('auslagen');
        Process::run(['cp', '-r', storage_path('demo/auslagen'), storage_path('app/')]);
    }
}
