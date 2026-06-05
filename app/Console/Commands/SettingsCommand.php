<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\SettingsBag;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class SettingsCommand extends Command
{
    protected $signature = 'stufis:settings
        {action : One of: list, get, set, forget}
        {key? : The setting key (dot notation supported for nested values)}
        {value? : The value to set (parsed as JSON when possible, otherwise kept as a string)}
        {--json : Output raw JSON instead of a table}';

    protected $description = 'Get, set, list or forget Settings model values';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list' => $this->list(),
            'get' => $this->get(),
            'set' => $this->set(),
            'forget' => $this->forget(),
            default => $this->fail("Unknown action '{$this->argument('action')}'. Use one of: list, get, set, forget."),
        };
    }

    private function list(): int
    {
        $map = Setting::toMap();

        if ($this->option('json')) {
            $this->line($this->encode($map));

            return self::SUCCESS;
        }

        $rows = [];
        foreach (Arr::dot($map) as $key => $value) {
            $rows[] = [$key, is_scalar($value) || $value === null ? $value : $this->encode($value)];
        }

        $this->table(['Key', 'Value'], $rows);

        return self::SUCCESS;
    }

    private function get(): int
    {
        $key = $this->argument('key');

        if (empty($key)) {
            return $this->fail('A key is required for the "get" action.');
        }

        $value = Setting::get($key);

        if ($value === null) {
            $this->warn("No value found for '{$key}'.");

            return self::SUCCESS;
        }

        $this->line($this->encode($value instanceof SettingsBag ? $value->toArray() : $value));

        return self::SUCCESS;
    }

    private function set(): int
    {
        $key = $this->argument('key');

        if (empty($key)) {
            return $this->fail('A key is required for the "set" action.');
        }

        if (! $this->hasArgument('value') || $this->argument('value') === null) {
            return $this->fail('A value is required for the "set" action.');
        }

        $value = $this->decode($this->argument('value'));

        Setting::set($key, $value);

        $this->info("Set '{$key}' to:");
        $this->line($this->encode($value));

        return self::SUCCESS;
    }

    private function forget(): int
    {
        $key = $this->argument('key');

        if (empty($key)) {
            return $this->fail('A key is required for the "forget" action.');
        }

        if (Setting::drop($key)) {
            $this->info("Removed '{$key}'. It will now fall back to its default (if any).");
        } else {
            $this->warn("No stored setting found for '{$key}'.");
        }

        return self::SUCCESS;
    }

    /**
     * Parse a CLI string as JSON when possible, otherwise keep it as a plain string.
     */
    private function decode(string $raw): mixed
    {
        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $raw;
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
