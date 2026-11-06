<?php

namespace App\Models;

use App\Concerns\HasGeneratedCode;
use App\Enums\ConflictStatus;
use App\Enums\RegistrationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\WorkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string|null $tap_number
 * @property string $macp_work_id
 * @property string|null $english_title
 * @property string|null $other_title
 * @property string|null $iswc
 * @property string|null $work_type
 * @property string|null $genre
 * @property string|null $language
 * @property string|null $duration
 * @property string|null $artist_name
 * @property string|null $original_work_title
 * @property string|null $sub_title_work
 * @property string|null $society_no
 * @property CarbonImmutable|null $administration_start_date
 * @property CarbonImmutable|null $administration_end_date
 * @property CarbonImmutable|null $registration_date
 * @property RegistrationStatus $registration_status
 * @property bool $is_public_domain
 * @property string|null $song_link
 * @property string|null $remark
 * @property CarbonImmutable|null $release_date
 * @property string|null $compass_work_internal_no
 * @property string|null $compass_work_type
 * @property CarbonImmutable|null $compass_start_date
 * @property string|null $wami_work_internal_no
 * @property string|null $wami_work_type
 * @property ConflictStatus $conflict_status
 * @property string|null $conflict_notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Collection<int, WorkIsrc> $isrcs
 * @property-read Collection<int, WorkParty> $parties
 * @property-read Collection<int, WorkParty> $currentParties
 * @property-read Collection<int, RightsHistory> $rightsHistories
 */
#[Fillable([
    'macp_work_id',
    'english_title',
    'other_title',
    'iswc',
    'work_type',
    'genre',
    'language',
    'duration',
    'artist_name',
    'original_work_title',
    'sub_title_work',
    'society_no',
    'administration_start_date',
    'administration_end_date',
    'registration_date',
    'registration_status',
    'is_public_domain',
    'song_link',
    'remark',
    'release_date',
    'compass_work_internal_no',
    'compass_work_type',
    'compass_start_date',
    'wami_work_internal_no',
    'wami_work_type',
    'conflict_status',
    'conflict_notes',
])]
class Work extends Model
{
    /** @use HasFactory<WorkFactory> */
    use HasFactory, HasGeneratedCode, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'administration_start_date' => 'date',
            'administration_end_date' => 'date',
            'registration_date' => 'date',
            'registration_status' => RegistrationStatus::class,
            'is_public_domain' => 'boolean',
            'release_date' => 'date',
            'compass_start_date' => 'date',
            'conflict_status' => ConflictStatus::class,
        ];
    }

    /**
     * The ISRCs of the work.
     *
     * @return HasMany<WorkIsrc, $this>
     */
    public function isrcs(): HasMany
    {
        return $this->hasMany(WorkIsrc::class)->orderBy('isrc');
    }

    /**
     * Every party row of the work, including ended and superseded ones.
     *
     * @return HasMany<WorkParty, $this>
     */
    public function parties(): HasMany
    {
        return $this->hasMany(WorkParty::class)
            ->orderBy('effective_start_date')
            ->orderBy('id');
    }

    /**
     * The change log of the work, newest effective date first.
     *
     * @return HasMany<RightsHistory, $this>
     */
    public function rightsHistories(): HasMany
    {
        return $this->hasMany(RightsHistory::class)
            ->orderByDesc('effective_date')
            ->orderByDesc('id');
    }

    /**
     * The party rows in effect today (business rule 3).
     *
     * @return HasMany<WorkParty, $this>
     */
    public function currentParties(): HasMany
    {
        return $this->parties()->current();
    }

    /**
     * Match the term against the titles, identifiers, ISRCs, contributors and publishers.
     *
     * @param  Builder<Work>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $search): void
    {
        $term = '%'.addcslashes($search, '%_\\').'%';

        $query->where(function (Builder $query) use ($term): void {
            $query->where('english_title', 'like', $term)
                ->orWhere('other_title', 'like', $term)
                ->orWhere('tap_number', 'like', $term)
                ->orWhere('macp_work_id', 'like', $term)
                ->orWhere('iswc', 'like', $term)
                ->orWhereHas('isrcs', fn (Builder $isrcs) => $isrcs->where('isrc', 'like', $term))
                ->orWhereHas('parties', function (Builder $parties) use ($term): void {
                    $parties->whereNull('superseded_at')
                        ->where(function (Builder $parties) use ($term): void {
                            $parties->where('contributor_name', 'like', $term)
                                ->orWhereHas('publisher', fn (Builder $publisher) => $publisher->where('name', 'like', $term));
                        });
                });
        });
    }

    /**
     * The display title: English first, then the other title.
     */
    public function displayTitle(): string
    {
        return $this->english_title ?? $this->other_title ?? $this->macp_work_id;
    }

    protected static function generatedCodeColumn(): string
    {
        return 'tap_number';
    }

    protected static function generatedCodePrefix(): string
    {
        return 'TAP';
    }
}
