<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes the cached Belege PDF so it is regenerated on the next request.
 *
 * AuslagenHandler2::generate_belege_pdf() short-circuits when
 * auslagen/{id}/belege-pdf-v{version}.pdf already exists, so a PDF that was
 * generated before a fix (or with stale data) keeps being served until the
 * version bumps. This throws away every cached version for the given
 * auslage(s) to force a fresh render.
 */
class InvalidateBelegePdfCommand extends Command
{
    protected $signature = 'stufis:invalidate-belege-pdf {auslagen?* : One or more auslage IDs} {--all : Invalidate the cache for every auslage}';

    protected $description = 'Delete all cached Belege PDFs for the given auslage(s) so they are regenerated';

    public function handle(): int
    {
        $auslagenIds = $this->resolveAuslagenIds();

        if ($auslagenIds === null) {
            return self::INVALID;
        }

        $deleted = 0;

        foreach ($auslagenIds as $auslagenId) {
            $cached = array_values(array_filter(
                Storage::files("auslagen/{$auslagenId}"),
                static fn (string $path): bool => preg_match('/^belege-pdf-v\d+\.pdf$/', basename($path)) === 1
            ));

            if ($cached === []) {
                continue;
            }

            Storage::delete($cached);
            $deleted += count($cached);
            $this->line("A{$auslagenId}: <info>invalidated</info> ".implode(', ', $cached));
        }

        $this->info("Done. {$deleted} cached Belege PDF(s) invalidated.");

        return self::SUCCESS;
    }

    /**
     * @return list<string>|null the auslage IDs to process, or null on invalid input
     */
    private function resolveAuslagenIds(): ?array
    {
        $given = $this->argument('auslagen');

        if ($this->option('all')) {
            if ($given !== []) {
                $this->error('Pass either auslage IDs or --all, not both.');

                return null;
            }

            // strip the "auslagen/" prefix to get the bare IDs
            return array_map(
                basename(...),
                Storage::directories('auslagen')
            );
        }

        if ($given === []) {
            $this->error('Provide at least one auslage ID, or use --all.');

            return null;
        }

        return $given;
    }
}
