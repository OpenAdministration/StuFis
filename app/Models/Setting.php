<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            "finance_mail" => "service@open-administration.de",
            "mail_domain" => "open-administration.de",
            "project" => [
                "description" => [
                    "max_length" => 99999,
                    "min_length" => 50,
                ],
                "protocol_url" => [
                    "active" => false,
                    "label" => "",
                    "prefix" => "",
                ],
                "committees" => ["Referat Finanzen"],
            ],

        ];
    }

    /**
     * Get a setting value by key, with fallback to default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);

        if ($setting) {
            return $setting->value;
        }

        return $default ?? data_get(static::defaults(), $key);
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
}
