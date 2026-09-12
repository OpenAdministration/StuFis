<?php

namespace App\Console\Commands;

use App\Support\FastCgiProcesses;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Install bin/templates/php.ini into the FastCGI directories of a Hostsharing.net webspace.
 *
 * Hostsharing gives no access to the system php.ini; the per-domain override lives in
 * ~/doms/<domain>/fastcgi[-ssl]/php.ini and is the only place an instance can raise its
 * upload/memory limits or enable opcache. Copying it by hand is easy to forget after an
 * update that changed the template, and easy to get wrong on a webspace hosting more than
 * one domain - hence this command.
 *
 * By default it only touches domains whose document root actually serves *this* checkout
 * (htdocs[-ssl] resolving to public/), so an unrelated domain on the same webspace is left
 * alone. Pass --all or --domain to override that.
 */
class InstallPhpIniCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stufis:php-ini
        {--domain=* : Only install into these domains (skips the served-by-this-instance check)}
        {--all : Install into every domain under the doms directory, not just those serving this instance}
        {--dry-run : Report what would change without writing anything}
        {--force : Overwrite without asking for confirmation}
        {--restart : Recycle this user\'s FastCGI PHP processes afterwards so the new settings take effect}
        {--doms-dir= : Override the Hostsharing doms directory (default: ~/doms)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the StuFiS php.ini template into a Hostsharing webspace\'s FastCGI directories';

    /**
     * Document root names a Hostsharing domain can have, paired with the FastCGI directory
     * whose php.ini configures it.
     *
     * @var array<string, string>
     */
    private const array DOCUMENT_ROOTS = [
        'htdocs-ssl' => 'fastcgi-ssl',
        'htdocs' => 'fastcgi',
    ];

    public function handle(): int
    {
        $template = base_path('bin/templates/php.ini');

        if (! File::isFile($template)) {
            $this->error("Template not found: {$template}");

            return self::FAILURE;
        }

        $domsDir = $this->domsDir();

        if (! File::isDirectory($domsDir)) {
            $this->error("No doms directory at {$domsDir} - this does not look like a Hostsharing webspace.");
            $this->line('Pass --doms-dir to point at it explicitly.');

            return self::FAILURE;
        }

        $targets = $this->targets($domsDir);

        if ($targets === []) {
            $this->warn('No matching FastCGI directory found.');
            $this->line($this->option('all') || $this->option('domain')
                ? "Checked every domain under {$domsDir} for a fastcgi/fastcgi-ssl directory."
                : 'No domain under '.$domsDir.' serves '.public_path().'. Use --all to install everywhere anyway.');

            return self::FAILURE;
        }

        $contents = File::get($template);
        $plan = $this->plan($targets, $contents);

        $this->table(
            ['Domain', 'php.ini', 'Status'],
            array_map(fn (array $entry): array => [$entry['domain'], $entry['path'], $entry['status']], $plan)
        );

        $pending = array_values(array_filter($plan, fn (array $entry): bool => $entry['status'] !== 'unchanged'));

        if ($pending === []) {
            $this->info('Every target already matches the template - nothing to do.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->comment(count($pending).' file(s) would be written. Re-run without --dry-run to apply.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Write '.count($pending).' file(s)?', true)) {
            $this->comment('Aborted.');

            return self::FAILURE;
        }

        foreach ($pending as $entry) {
            $this->write($entry, $contents);
        }

        $this->info('Installed the php.ini template into '.count($pending).' directory/directories.');

        return $this->finish();
    }

    /**
     * Resolve the directory holding the webspace's domains.
     */
    private function domsDir(): string
    {
        $override = $this->option('doms-dir');

        if (is_string($override) && $override !== '') {
            return rtrim($override, '/');
        }

        return rtrim((string) (getenv('HOME') ?: '~'), '/').'/doms';
    }

    /**
     * Collect every FastCGI directory that should receive the template.
     *
     * @return list<array{domain: string, path: string}>
     */
    private function targets(string $domsDir): array
    {
        /** @var list<string> $only */
        $only = (array) $this->option('domain');
        $everyDomain = (bool) $this->option('all') || $only !== [];
        $public = realpath(public_path()) ?: public_path();

        $targets = [];

        foreach (File::directories($domsDir) as $domainDir) {
            $domain = basename((string) $domainDir);

            if ($only !== [] && ! in_array($domain, $only, true)) {
                continue;
            }

            foreach (self::DOCUMENT_ROOTS as $documentRoot => $fastcgiDir) {
                $fastcgiPath = $domainDir.'/'.$fastcgiDir;

                if (! File::isDirectory($fastcgiPath)) {
                    continue;
                }

                if (! $everyDomain && realpath($domainDir.'/'.$documentRoot) !== $public) {
                    continue;
                }

                $targets[] = ['domain' => $domain, 'path' => $fastcgiPath.'/php.ini'];
            }
        }

        foreach (array_diff($only, array_column($targets, 'domain')) as $missing) {
            $this->warn("No fastcgi directory found for domain '{$missing}'.");
        }

        return $targets;
    }

    /**
     * Classify every target against the template contents.
     *
     * @param  list<array{domain: string, path: string}>  $targets
     * @return list<array{domain: string, path: string, status: string}>
     */
    private function plan(array $targets, string $contents): array
    {
        return array_map(function (array $target) use ($contents): array {
            if (! File::isFile($target['path'])) {
                $status = 'create';
            } elseif (File::get($target['path']) === $contents) {
                $status = 'unchanged';
            } else {
                $status = 'overwrite';
            }

            return $target + ['status' => $status];
        }, $targets);
    }

    /**
     * Write the template, keeping a timestamped copy of whatever was there before.
     *
     * @param  array{domain: string, path: string, status: string}  $entry
     */
    private function write(array $entry, string $contents): void
    {
        if ($entry['status'] === 'overwrite') {
            $backup = $entry['path'].'.bak-'.now()->format('Ymd-His');
            File::copy($entry['path'], $backup);
            $this->line("  Backed up the previous php.ini to {$backup}");
        }

        File::put($entry['path'], $contents);
        File::chmod($entry['path'], 0644);
        $this->line("  Wrote {$entry['path']}");
    }

    /**
     * Report (or perform) the FastCGI recycle the new settings need.
     *
     * Running processes keep the configuration they started with, so nothing changes until
     * they are gone. The same holds for the opcache and realpath caches they carry, which is
     * why recycling is worth doing after a deployment too.
     */
    private function finish(): int
    {
        if (! $this->option('restart')) {
            $this->newLine();
            $this->comment('The settings only take effect once the running FastCGI processes are gone:');
            $this->line('  pkill -u "$USER" \'^php\'      # or re-run with --restart');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->comment('Recycling FastCGI processes - in-flight requests are dropped, new ones start fresh.');

        $processes = new FastCgiProcesses;
        $killed = $processes->kill($processes->pids());

        $this->info($killed === 0
            ? 'No FastCGI process was running the previous configuration.'
            : "Recycled {$killed} FastCGI process(es).");

        return self::SUCCESS;
    }
}
