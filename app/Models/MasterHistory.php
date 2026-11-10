<?php

namespace App\Models;

use App\Enums\MasterChangeType;
use Carbon\CarbonImmutable;
use Database\Factories\MasterHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * One entry of the change log of a writer or a publisher. Rows are
 * insert-only: they are never updated or deleted, and they are kept when the
 * subject is soft-deleted.
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property string|null $subject_code
 * @property string $subject_name
 * @property MasterChangeType $change_type
 * @property array<string, mixed>|null $previous_snapshot
 * @property array<string, mixed>|null $new_snapshot
 * @property CarbonImmutable $recorded_at
 * @property int|null $user_id
 * @property int|null $csv_import_id
 * @property string|null $remarks
 * @property-read Writer|Publisher|null $subject
 * @property-read User|null $user
 */
#[Fillable([
    'subject_type',
    'subject_id',
    'subject_code',
    'subject_name',
    'change_type',
    'previous_snapshot',
    'new_snapshot',
    'recorded_at',
    'user_id',
    'csv_import_id',
    'remarks',
])]
class MasterHistory extends Model
{
    /** @use HasFactory<MasterHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Refuse every change to a stored row.
     */
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Master history entries cannot be updated.');
        });

        static::deleting(function (): never {
            throw new LogicException('Master history entries cannot be deleted.');
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'change_type' => MasterChangeType::class,
            'previous_snapshot' => 'array',
            'new_snapshot' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * The writer or publisher the entry belongs to. Still found after a soft-delete.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    /**
     * The user who made the change.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
