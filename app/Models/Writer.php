<?php

namespace App\Models;

use App\Casts\IdentityNumber;
use App\Casts\PhoneNumber;
use App\Concerns\HasGeneratedCode;
use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Enums\RightsType;
use Carbon\CarbonImmutable;
use Database\Factories\WriterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string|null $writer_code
 * @property string $name
 * @property PartyRole|null $default_role
 * @property string|null $ipi_base_number
 * @property string|null $ipi_name_number
 * @property string|null $nric
 * @property CarbonImmutable|null $date_of_birth
 * @property CarbonImmutable|null $date_of_death
 * @property string|null $nationality
 * @property string $cmo_society
 * @property int|null $publisher_id
 * @property list<string>|null $controlled_territories
 * @property Collection<int, RightsType>|null $rights_types_controlled
 * @property string|null $tin_number
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property MasterStatus $status
 * @property CarbonImmutable $agreement_start_date
 * @property CarbonImmutable|null $agreement_end_date
 * @property bool|null $auto_renewal
 * @property bool|null $pre_term
 * @property string|null $post_term
 * @property string|null $taja_percentage_deal
 * @property string|null $writer_percentage_deal
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $swift_code
 * @property string|null $bank_address
 * @property string|null $heir_name
 * @property string|null $heir_nric
 * @property string|null $heir_relationship
 * @property string|null $heir_mobile_number
 * @property string|null $heir_email
 * @property string|null $source_system
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Publisher|null $publisher
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WriterAlias> $aliases
 */
#[Fillable([
    'name',
    'default_role',
    'ipi_base_number',
    'ipi_name_number',
    'nric',
    'date_of_birth',
    'date_of_death',
    'nationality',
    'cmo_society',
    'publisher_id',
    'controlled_territories',
    'rights_types_controlled',
    'tin_number',
    'email',
    'phone',
    'address',
    'status',
    'agreement_start_date',
    'agreement_end_date',
    'auto_renewal',
    'pre_term',
    'post_term',
    'taja_percentage_deal',
    'writer_percentage_deal',
    'bank_name',
    'bank_account_number',
    'swift_code',
    'bank_address',
    'heir_name',
    'heir_nric',
    'heir_relationship',
    'heir_mobile_number',
    'heir_email',
    'source_system',
    'notes',
])]
class Writer extends Model
{
    /** @use HasFactory<WriterFactory> */
    use HasFactory, HasGeneratedCode, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nric' => IdentityNumber::class,
            'heir_nric' => IdentityNumber::class,
            'phone' => PhoneNumber::class,
            'heir_mobile_number' => PhoneNumber::class,
            'default_role' => PartyRole::class,
            'status' => MasterStatus::class,
            'controlled_territories' => 'array',
            'rights_types_controlled' => AsEnumCollection::of(RightsType::class),
            'date_of_birth' => 'date',
            'date_of_death' => 'date',
            'agreement_start_date' => 'date',
            'agreement_end_date' => 'date',
            'auto_renewal' => 'boolean',
            'pre_term' => 'boolean',
            'taja_percentage_deal' => 'decimal:2',
            'writer_percentage_deal' => 'decimal:2',
        ];
    }

    /**
     * The publisher that controls this writer, if any. It is still found
     * after the publisher was soft-deleted.
     *
     * @return BelongsTo<Publisher, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class)->withTrashed();
    }

    /**
     * The pseudonyms of the writer, in position order.
     *
     * @return HasMany<WriterAlias, $this>
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(WriterAlias::class)->orderBy('position');
    }

    /**
     * Every party row that ties the writer to a work.
     *
     * @return HasMany<WorkParty, $this>
     */
    public function parties(): HasMany
    {
        return $this->hasMany(WorkParty::class);
    }

    /**
     * Whether the writer is still a party of a work that exists. Rows that
     * ended, were superseded, or sit on a deleted work do not count.
     */
    public function hasCurrentParties(): bool
    {
        return $this->parties()
            ->current()
            ->whereHas('work', fn (Builder $work) => $work->withoutTrashed())
            ->exists();
    }

    protected static function generatedCodeColumn(): string
    {
        return 'writer_code';
    }

    protected static function generatedCodePrefix(): string
    {
        return 'WRT';
    }
}
