<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /** @return array<string,mixed>|null */
    public static function getValue(string $key): ?array
    {
        return self::where('key', $key)->first()?->value;
    }

    /** @param array<string,mixed> $value */
    public static function setValue(string $key, array $value): self
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
