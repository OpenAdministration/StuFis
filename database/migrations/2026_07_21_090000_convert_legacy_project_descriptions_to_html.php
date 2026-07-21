<?php

use App\Support\LegacyDescription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * One-time backfill: convert legacy plain-text project descriptions to HTML.
     *
     * Old `projekte.beschreibung` values were plain text with "\n" line breaks.
     * Since the 4.4 rewrite the field is edited with flux:editor and rendered as
     * raw HTML, so bare newlines collapse and the line breaks are lost in both
     * the editor and the view. This rewrites plain-text rows as whitelist-safe
     * HTML. Rows that already contain HTML are skipped; their ids are logged so
     * any that only *look* like tags (e.g. "grant to <group>") can be reviewed.
     */
    public function up(): void
    {
        $converted = 0;
        $skipped = [];

        DB::table('projekte')
            ->whereNotNull('beschreibung')
            ->where('beschreibung', '!=', '')
            ->chunkById(200, function ($rows) use (&$converted, &$skipped): void {
                foreach ($rows as $row) {
                    $value = (string) $row->beschreibung;

                    if (! LegacyDescription::isPlainText($value)) {
                        $skipped[] = $row->id;

                        continue;
                    }

                    DB::table('projekte')
                        ->where('id', $row->id)
                        ->update(['beschreibung' => LegacyDescription::toHtml($value)]);

                    $converted++;
                }
            });

        Log::info('Legacy project description backfill complete.', [
            'converted' => $converted,
            'skipped_non_empty' => count($skipped),
            'skipped_ids' => $skipped,
        ]);
    }

    /**
     * Irreversible by design: the original plain-text line breaks cannot be
     * faithfully reconstructed from the generated HTML. Take a DB backup before
     * running. No-op so rolling back later migrations does not fail here.
     */
    public function down(): void
    {
        //
    }
};
