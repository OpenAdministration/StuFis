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
    /**
     * Whole-year shift applied to the demo data so the open budget plan (von 2024-04-01) contains
     * today. Fiscal years are April-anchored, so before April we still belong to the previous one.
     * Shared with DemoBudgetSeeder so the new budget plans line up with the dump's booking dates.
     */
    public static function yearShiftDelta(): int
    {
        $today = Carbon::today();
        $targetOpenYear = $today->month < 4 ? $today->year - 1 : $today->year;

        return $targetOpenYear - 2024;
    }

    public function run(): void
    {
        if (App::isProduction() && config('stufis.realm') !== 'demo') {
            throw new \InvalidArgumentException('Realm is not demo but we are in production, aborting for your safety');
        }

        $delta = self::yearShiftDelta();

        // Single-pass shift of the known data years only. The digit-boundary guards keep
        // us from touching years embedded in longer numbers (amounts, IBANs, refs), and a
        // single pass avoids the cascade where a replaced year is matched again.
        $sqlContent = str(Storage::disk('demo')->get('stufis-demo-data.sql'))
            ->replaceMatches(
                '/(?<!\d)(2023|2024|2025)(?!\d)/',
                fn (array $matches): string => (string) ((int) $matches[1] + $delta),
            )
            ->replace('demo__', DB::getTablePrefix());

        // NB: do not wrap in DB::transaction — the dump is a multi-statement batch (incl.
        // SET FOREIGN_KEY_CHECKS) run via DB::unprepared, which triggers an implicit commit
        // and makes the outer transaction fail at commit ("no active transaction").
        DB::unprepared($sqlContent);

        // deleteDirectory (not delete, which only removes files) so a re-seed wipes the
        // whole tree — including runtime-generated PDFs (belege-pdf-v*, zahlungsanweisung-v*)
        // and avoids cp nesting the demo copy inside a surviving auslagen/ directory.
        Storage::deleteDirectory('auslagen');
        Process::run(['cp', '-r', storage_path('demo/auslagen'), storage_path('app/')]);

    }
}
