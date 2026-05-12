<?php

namespace App\Support;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Stringable;

class SettingsBag implements ArrayAccess, Arrayable, Jsonable, JsonSerializable, Stringable
{
    protected array $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get a value using dot notation or direct key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = data_get($this->attributes, $key, $default);

        if (is_array($value) && ! array_is_list($value)) {
            return new static($value);
        }

        return $value;
    }

    /**
     * Check if a key exists.
     */
    public function has(string $key): bool
    {
        return data_get($this->attributes, $key) !== null;
    }

    /**
     * Property access — returns nested SettingBag for associative arrays.
     */
    public function __get(string $key): mixed
    {
        $value = $this->attributes[$key] ?? null;

        if (is_array($value) && ! array_is_list($value)) {
            return new static($value);
        }

        return $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    // ── ArrayAccess ──

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Read-only
    }

    public function offsetUnset(mixed $offset): void
    {
        // Read-only
    }

    // ── Serialization ──

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
