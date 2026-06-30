<?php

namespace App\Models;

use App\Support\SettingsBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            'mail_domain' => 'open-administration.de',
            'project' => [
                'description' => [
                    'max_length' => -1,
                    'min_length' => 50,
                ],
                'protocol_url' => [
                    'active' => false,
                    'label' => '',
                ],
            ],
            'tax.active' => false,
            'datev' => false,
        ];
    }

    /**
     * Get a setting value by key, with fallback to default.
     *
     * Returns a SettingBag for nested associative arrays, allowing
     * fluent access: Setting::get('project')->description->min_length
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        $value = $setting?->value ?? $default ?? data_get(static::toMap(), $key);

        if (is_array($value) && ! array_is_list($value)) {
            return new SettingsBag($value);
        }

        return $value;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * Get all settings as a key => value map, with defaults filled in.
     */
    public static function toMap(): array
    {
        $stored = static::pluck('value', 'key')->toArray();

        return Arr::undot(array_merge(static::defaults(), $stored));
    }

    public static function drop(string $key)
    {
        return Setting::find($key)?->delete();
    }
}
