<?php

namespace App\Models;

use App\Enums\UserChangeType;
use Carbon\CarbonImmutable;
use Database\Factories\UserHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One entry of the change log of a user account. Rows are insert-only: they
 * are never updated or deleted. Users are hard-deleted, so the subject's name
 * and email are copied onto the entry, and so is the actor's name.
 *
 * @property int $id
 * @property int $subject_id
 * @property string $subject_name
 * @property string $subject_email
 * @property UserChangeType $change_type
 * @property array<string, mixed>|null $previous_snapshot
 * @property array<string, mixed>|null $new_snapshot
 * @property CarbonImmutable $recorded_at
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string|null $remarks
 * @property-read User|null $actor
 */
#[Fillable([
    'subject_id',
    'subject_name',
    'subject_email',
    'change_type',
    'previous_snapshot',
    'new_snapshot',
    'recorded_at',
    'actor_id',
    'actor_name',
    'remarks',
])]
class UserHistory extends Model
{
    /** @use HasFactory<UserHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Refuse every change to a stored row.
     */
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('User history entries cannot be updated.');
        });

        static::deleting(function (): never {
            throw new LogicException('User history entries cannot be deleted.');
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
            'change_type' => UserChangeType::class,
            'previous_snapshot' => 'array',
            'new_snapshot' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * The user who made the change, when the account still exists.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
