<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Verifies that every Eloquent attribute with an `encrypted` cast can actually be
 * decrypted with the current APP_KEY. Demo dumps (and restored backups) are often
 * encrypted under a different key, which only blows up later — e.g. a 500 in the
 * booking process when it decrypts Expense::zahlung_iban to build the payment order.
 * This surfaces the mismatch up front instead.
 */
class CheckEncryptedFieldsCommand extends Command
{
    protected $signature = 'stufis:check-encryption
        {--model= : Restrict the check to a single model (FQCN or short name, e.g. Expense)}
        {--samples=5 : How many failing primary keys to list per field}';

    protected $description = 'Check that all encrypted model fields are decryptable with the current APP_KEY';

    public function handle(): int
    {
        $targets = $this->discoverEncryptedFields();

        if ($this->option('model')) {
            $needle = strtolower($this->option('model'));
            $targets = array_filter(
                $targets,
                fn (array $t) => str_contains(strtolower($t['model']), $needle)
            );
        }

        if ($targets === []) {
            $this->warn('No models with `encrypted` casts found.');

            return self::SUCCESS;
        }

        $samples = max(0, (int) $this->option('samples'));
        $rows = [];
        $hadFailures = false;

        foreach ($targets as $target) {
            /** @var class-string<Model> $model */
            $model = $target['model'];
            $field = $target['field'];
            $instance = new $model;
            $key = $instance->getKeyName();

            $total = 0;
            $empty = 0;
            $ok = 0;
            $failed = 0;
            $failedKeys = [];

            $model::query()->chunkById(500, function ($records) use (
                $field, $key, &$total, &$empty, &$ok, &$failed, &$failedKeys, $samples
            ) {
                foreach ($records as $record) {
                    $total++;
                    $raw = $record->getRawOriginal($field);
                    if ($raw === null || $raw === '') {
                        $empty++;

                        continue;
                    }
                    try {
                        Crypt::decryptString($raw);
                        $ok++;
                    } catch (DecryptException) {
                        $failed++;
                        if (count($failedKeys) < $samples) {
                            $failedKeys[] = (string) $record->getAttribute($key);
                        }
                    }
                }
            }, $key);

            if ($failed > 0) {
                $hadFailures = true;
            }

            $rows[] = [
                class_basename($model).'::'.$field,
                $total,
                $empty,
                $ok,
                $failed > 0 ? "<error>{$failed}</error>" : '0',
                $failedKeys === [] ? '—' : implode(', ', $failedKeys).($failed > $samples ? ', …' : ''),
            ];
        }

        $this->table(['Field', 'Rows', 'Empty', 'OK', 'Undecryptable', 'Sample failing PKs'], $rows);

        if ($hadFailures) {
            $this->error('Some encrypted fields could not be decrypted — the APP_KEY does not match the stored ciphertext.');

            return self::FAILURE;
        }

        $this->info('All encrypted fields decrypt cleanly with the current APP_KEY.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{model: class-string<Model>, field: string}>
     */
    private function discoverEncryptedFields(): array
    {
        $found = [];

        foreach ($this->modelClasses() as $model) {
            try {
                $instance = new $model;
            } catch (\Throwable) {
                continue;
            }

            foreach ($instance->getCasts() as $field => $cast) {
                if ($cast === 'encrypted' || Str::startsWith($cast, 'encrypted:')) {
                    $found[] = ['model' => $model, 'field' => $field];
                }
            }
        }

        return $found;
    }

    /**
     * @return list<class-string<Model>>
     */
    private function modelClasses(): array
    {
        $classes = [];
        $base = app_path('Models');

        foreach (Finder::create()->files()->in($base)->name('*.php') as $file) {
            /** @var SplFileInfo $file */
            $relative = Str::of($file->getPathname())
                ->after($base.DIRECTORY_SEPARATOR)
                ->replace(DIRECTORY_SEPARATOR, '\\')
                ->beforeLast('.php');
            $class = 'App\\Models\\'.$relative;

            if (! class_exists($class)) {
                continue;
            }
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }
            $classes[] = $class;
        }

        return $classes;
    }
}
