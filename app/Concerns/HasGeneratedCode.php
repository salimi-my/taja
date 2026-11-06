<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Gives a model a system-generated, zero-padded code such as `TAP-000001`.
 *
 * The code is derived from the row id right after the insert, so two requests
 * can never produce the same code, and because ids are not recycled a code is
 * never reused. The model declares the column and prefix:
 *
 *     protected static function generatedCodeColumn(): string { return 'tap_number'; }
 *     protected static function generatedCodePrefix(): string { return 'TAP'; }
 *
 * The code column must be `unique()` and `nullable()` in the migration,
 * because it stays empty for the moment between the insert and the update.
 * MySQL allows many null values in a unique index.
 *
 * @mixin Model
 */
trait HasGeneratedCode
{
    /**
     * The column that stores the generated code.
     */
    abstract protected static function generatedCodeColumn(): string;

    /**
     * The prefix of the generated code, without the dash.
     */
    abstract protected static function generatedCodePrefix(): string;

    /**
     * Format a code from a prefix and a row id, e.g. `TAP-000042`.
     */
    public static function formatCode(string $prefix, int $id): string
    {
        return sprintf('%s-%s', $prefix, str_pad((string) $id, 6, '0', STR_PAD_LEFT));
    }

    protected static function bootHasGeneratedCode(): void
    {
        static::created(function (Model $model): void {
            $column = static::generatedCodeColumn();

            if ($model->getAttribute($column) !== null) {
                return;
            }

            $model->setAttribute($column, static::formatCode(
                static::generatedCodePrefix(),
                (int) $model->getKey(),
            ));
            $model->saveQuietly();
        });
    }
}
