<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * An ISO 3166-1 country or territory, used to pick the territories of a writer or publisher.
 *
 * @property int $id
 * @property string $code The uppercase two-letter ISO 3166-1 alpha-2 code.
 * @property string $name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['code', 'name'])]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    private const CODES_CACHE_KEY = 'countries.codes';

    /**
     * Forget the cached codes whenever a country changes.
     */
    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCachedCodes());
        static::deleted(fn () => self::forgetCachedCodes());
    }

    /**
     * Every country code. Cached because a large CSV import checks the
     * territories of every row.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return Cache::remember(
            self::CODES_CACHE_KEY,
            now()->addHour(),
            fn (): array => array_values(array_map('strval', self::query()->pluck('code')->all())),
        );
    }

    public static function forgetCachedCodes(): void
    {
        Cache::forget(self::CODES_CACHE_KEY);
    }
}
