<?php

namespace App\Console\Commands;

use App\Models\FintsInstitute;
use App\Support\Fints\InstituteListParser;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UpdateFintsInstitutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stufis:fints-institutes-update
        {--url= : Override the configured bank list source}
        {--file= : Read a local blz.properties instead of downloading}
        {--dry-run : Report what would change without writing}
        {--prune : Delete institutes that no longer exist upstream}
        {--min-entries=1000 : Refuse lists shorter than this as implausible}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the public FinTS bank list (hbci4java blz.properties) into the fints_institutes table';

    /**
     * Rows per upsert. The full list is ~4000 institutes.
     */
    private const int CHUNK_SIZE = 500;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $source = $this->option('file') ?: ($this->option('url') ?: config('stufis.fints.institute_list_url'));

        $contents = $this->option('file')
            ? $this->readFile((string) $this->option('file'))
            : $this->download((string) $source);

        if ($contents === null) {
            return self::FAILURE;
        }

        $parser = new InstituteListParser;
        $upstream = $parser->parse($contents);

        $this->line(sprintf('Gelesen: %d Institute aus %s', count($upstream), $source));
        if ($parser->skipped > 0) {
            $this->comment(sprintf('%d unbrauchbare Zeilen übersprungen.', $parser->skipped));
        }

        // A truncated download or an HTML error page would otherwise wipe the table.
        $minEntries = (int) $this->option('min-entries');
        if (count($upstream) < $minEntries) {
            $this->error(sprintf(
                'Nur %d Institute gefunden, erwartet mindestens %d - Quelle sieht unvollständig aus, Abbruch.',
                count($upstream),
                $minEntries,
            ));

            return self::FAILURE;
        }

        $existing = FintsInstitute::query()
            ->get(['blz', ...FintsInstitute::SYNCED_FIELDS])
            ->keyBy('blz');

        $new = [];
        $changed = [];
        foreach ($upstream as $blz => $institute) {
            $current = $existing->get($blz);

            if ($current === null) {
                $new[$blz] = $institute;

                continue;
            }

            foreach (FintsInstitute::SYNCED_FIELDS as $field) {
                if ($current->{$field} !== $institute[$field]) {
                    $changed[$blz] = $institute;

                    break;
                }
            }
        }

        $vanished = $existing->keys()->diff(array_keys($upstream));

        $this->newLine();
        $this->table(['', 'Institute'], [
            ['neu', count($new)],
            ['geändert', count($changed)],
            ['unverändert', count($upstream) - count($new) - count($changed)],
            ['nicht mehr in der Liste', $vanished->count()],
        ]);

        $this->reportChanges($changed, $existing);

        if ($this->option('dry-run')) {
            $this->comment('--dry-run: nichts geschrieben.');

            return self::SUCCESS;
        }

        $syncedAt = Date::now();
        $this->persist($upstream, $syncedAt);

        if ($vanished->isNotEmpty()) {
            $this->handleVanished($vanished);
        }

        $this->info(sprintf('Bankenliste aktualisiert (Stand %s).', $syncedAt->format('d.m.Y H:i')));

        return self::SUCCESS;
    }

    private function readFile(string $path): ?string
    {
        if (! is_readable($path)) {
            $this->error("Datei nicht lesbar: $path");

            return null;
        }

        return (string) file_get_contents($path);
    }

    private function download(string $url): ?string
    {
        if ($url === '') {
            $this->error('Keine Quelle konfiguriert (stufis.fints.institute_list_url).');

            return null;
        }

        $this->line("Lade $url ...");

        try {
            $response = Http::timeout(30)->retry(2, 500, throw: false)->get($url);
        } catch (ConnectionException $e) {
            $this->error('Download fehlgeschlagen: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            $this->error(sprintf('Download fehlgeschlagen: HTTP %d', $response->status()));

            return null;
        }

        return $response->body();
    }

    /**
     * @param  array<string, array<string, string|null>>  $upstream
     */
    private function persist(array $upstream, Carbon $syncedAt): void
    {
        $rows = [];
        foreach ($upstream as $blz => $institute) {
            $rows[] = [
                'blz' => $blz,
                ...$institute,
                'synced_at' => $syncedAt,
                'created_at' => $syncedAt,
                'updated_at' => $syncedAt,
            ];
        }

        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                FintsInstitute::query()->upsert(
                    $chunk,
                    ['blz'],
                    [...FintsInstitute::SYNCED_FIELDS, 'synced_at', 'updated_at'],
                );
            }
        });
    }

    /**
     * @param  Collection<int, string>  $vanished
     */
    private function handleVanished(Collection $vanished): void
    {
        if (! $this->option('prune')) {
            $this->comment(sprintf(
                '%d Institute sind nicht mehr in der Liste, bleiben aber erhalten (--prune zum Löschen).',
                $vanished->count(),
            ));

            return;
        }

        // A BLZ someone has a bank access for must survive, list or no list: konto_credentials
        // references it, so deleting would either fail on the foreign key or take the access
        // with it. Reported rather than swallowed - a bank leaving the list while we still
        // bank there is worth a look.
        $inUse = DB::table('konto_credentials')
            ->whereIn('blz', $vanished->all())
            ->pluck('blz')
            ->unique();

        if ($inUse->isNotEmpty()) {
            $this->warn(sprintf(
                'Nicht gelöscht, weil Bankzugänge darauf verweisen: %s',
                $inUse->implode(', '),
            ));
        }

        // Delete exactly the BLZs we found to be absent, rather than everything with an
        // older synced_at: that column has second precision, so two runs within the same
        // second would silently prune nothing.
        $deleted = 0;
        foreach ($vanished->diff($inUse)->chunk(self::CHUNK_SIZE) as $chunk) {
            $deleted += FintsInstitute::query()->whereIn('blz', $chunk->all())->delete();
        }

        $this->comment(sprintf('%d veraltete Institute gelöscht.', $deleted));
    }

    /**
     * @param  array<string, array<string, string|null>>  $changed
     * @param  Collection<string, FintsInstitute>  $existing
     */
    private function reportChanges(array $changed, Collection $existing): void
    {
        if ($changed === []) {
            return;
        }

        // The interesting drift is the endpoint moving, not a bank renaming itself.
        $movedEndpoints = [];
        foreach ($changed as $blz => $institute) {
            $before = $existing->get($blz)?->pin_tan_address;
            if ($before !== $institute['pin_tan_address']) {
                $movedEndpoints[] = [$blz, $institute['name'], $before ?? '-', $institute['pin_tan_address'] ?? '-'];
            }
        }

        if ($movedEndpoints !== []) {
            $this->newLine();
            $this->line('Geänderte PIN/TAN-Endpunkte:');
            $this->table(['BLZ', 'Bank', 'vorher', 'nachher'], $movedEndpoints);
        }
    }
}
