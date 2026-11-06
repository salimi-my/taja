<?php

namespace App\Models;

use App\Enums\PartyRole;
use Carbon\CarbonImmutable;
use Database\Factories\WorkPartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A writer or publisher on a work for one period. Rows are temporal: a change
 * ends the old row and opens a new one, nothing is overwritten.
 *
 * @property int $id
 * @property int $work_id
 * @property int|null $writer_id
 * @property int|null $publisher_id
 * @property PartyRole $role
 * @property string $contributor_name
 * @property string $performance_share
 * @property string $mechanical_share
 * @property string|null $sync_share
 * @property string|null $lyrics_share
 * @property bool $is_controlled
 * @property list<string> $territories_controlled
 * @property list<string>|null $territory_exclusions
 * @property CarbonImmutable $effective_start_date
 * @property CarbonImmutable|null $effective_end_date
 * @property CarbonImmutable|null $superseded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Work $work
 * @property-read Writer|null $writer
 * @property-read Publisher|null $publisher
 */
#[Fillable([
    'writer_id',
    'publisher_id',
    'role',
    'contributor_name',
    'performance_share',
    'mechanical_share',
    'sync_share',
    'lyrics_share',
    'is_controlled',
    'territories_controlled',
    'territory_exclusions',
    'effective_start_date',
    'effective_end_date',
    'superseded_at',
])]
class WorkParty extends Model
{
    /** @use HasFactory<WorkPartyFactory> */
    use HasFactory;

    /**
     * The rights fields of a row. Changing one of them means a new row.
     *
     * @var list<string>
     */
    public const RIGHTS_FIELDS = [
        'role',
        'performance_share',
        'mechanical_share',
        'sync_share',
        'lyrics_share',
        'is_controlled',
        'territories_controlled',
        'territory_exclusions',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => PartyRole::class,
            'performance_share' => 'decimal:2',
            'mechanical_share' => 'decimal:2',
            'sync_share' => 'decimal:2',
            'lyrics_share' => 'decimal:2',
            'is_controlled' => 'boolean',
            'territories_controlled' => 'array',
            'territory_exclusions' => 'array',
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
            'superseded_at' => 'datetime',
        ];
    }

    /**
     * The work the party is on.
     *
     * @return BelongsTo<Work, $this>
     */
    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class)->withTrashed();
    }

    /**
     * The writer, for writer roles. Still found after a soft-delete.
     *
     * @return BelongsTo<Writer, $this>
     */
    public function writer(): BelongsTo
    {
        return $this->belongsTo(Writer::class)->withTrashed();
    }

    /**
     * The publisher, for publisher roles. Still found after a soft-delete.
     *
     * @return BelongsTo<Publisher, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class)->withTrashed();
    }

    /**
     * Rows in effect today: not superseded, and not ended before today.
     *
     * @param  Builder<WorkParty>  $query
     */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->whereNull('superseded_at')
            ->where(function (Builder $query): void {
                $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', Carbon::today()->toDateString());
            });
    }

    /**
     * Rows in effect on a date (business rule 3, point-in-time view).
     *
     * @param  Builder<WorkParty>  $query
     */
    #[Scope]
    protected function effectiveOn(Builder $query, CarbonImmutable|Carbon|string $date): void
    {
        $day = Carbon::parse($date)->toDateString();

        $query->whereNull('superseded_at')
            ->whereDate('effective_start_date', '<=', $day)
            ->where(function (Builder $query) use ($day): void {
                $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $day);
            });
    }

    /**
     * The rows shown in a writer's or publisher's catalogue: not superseded,
     * on a work that still exists, newest first. Ended rows are left out
     * unless asked for.
     *
     * @param  Builder<WorkParty>  $query
     */
    #[Scope]
    protected function forCatalogue(Builder $query, bool $includeEnded): void
    {
        $query->whereNull('superseded_at')
            ->whereHas('work', fn (Builder $work) => $work->withoutTrashed())
            ->unless($includeEnded, fn (Builder $query) => $query->current())
            ->with('work')
            ->orderByDesc('effective_start_date')
            ->orderByDesc('id');
    }

    /**
     * Whether the party is a writer (as opposed to a publisher).
     */
    public function isWriter(): bool
    {
        return $this->writer_id !== null;
    }
}
