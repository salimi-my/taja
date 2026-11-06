<?php

namespace App\Models;

use App\Enums\RightsChangeType;
use Carbon\CarbonImmutable;
use Database\Factories\RightsHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One entry of the change log of a work. Rows are insert-only: they are never
 * updated or deleted, and they are kept when a work is soft-deleted.
 *
 * @property int $id
 * @property int $work_id
 * @property int|null $work_party_id
 * @property RightsChangeType $change_type
 * @property array<string, mixed>|null $previous_snapshot
 * @property array<string, mixed>|null $new_snapshot
 * @property CarbonImmutable $effective_date
 * @property CarbonImmutable $recorded_at
 * @property int|null $user_id
 * @property int|null $csv_import_id
 * @property string|null $remarks
 * @property-read Work $work
 * @property-read WorkParty|null $workParty
 * @property-read User|null $user
 */
#[Fillable([
    'work_id',
    'work_party_id',
    'change_type',
    'previous_snapshot',
    'new_snapshot',
    'effective_date',
    'recorded_at',
    'user_id',
    'csv_import_id',
    'remarks',
])]
class RightsHistory extends Model
{
    /** @use HasFactory<RightsHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Refuse every change to a stored row.
     */
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Rights history entries cannot be updated.');
        });

        static::deleting(function (): never {
            throw new LogicException('Rights history entries cannot be deleted.');
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
            'change_type' => RightsChangeType::class,
            'previous_snapshot' => 'array',
            'new_snapshot' => 'array',
            'effective_date' => 'date',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * The work the entry belongs to. Still found after a soft-delete.
     *
     * @return BelongsTo<Work, $this>
     */
    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class)->withTrashed();
    }

    /**
     * The party row the change was made to, for party-level entries.
     *
     * @return BelongsTo<WorkParty, $this>
     */
    public function workParty(): BelongsTo
    {
        return $this->belongsTo(WorkParty::class);
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
