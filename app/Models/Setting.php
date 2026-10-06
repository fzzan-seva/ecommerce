<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single key/value entry of the `settings` table.
 *
 * Values are written by Admin > Store Settings and read by App\Support\Shop,
 * which falls back to config/shop.php when a key has never been saved.
 */
class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function set(string $key, mixed $value): void
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::set((string) $key, $value);
        }
    }
}
