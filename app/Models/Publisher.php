<?php

namespace App\Models;

use App\Casts\IdentityNumber;
use App\Casts\PhoneNumber;
use App\Concerns\HasGeneratedCode;
use App\Enums\MasterStatus;
use App\Enums\RightsType;
use Carbon\CarbonImmutable;
use Database\Factories\PublisherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string|null $publisher_code
 * @property string $name
 * @property Collection<int, RightsType> $rights_types_granted
 * @property string|null $ipi_base_number
 * @property string|null $ipi_name_number
 * @property string $company_registration_number
 * @property string $registration_address
 * @property string $official_email
 * @property CarbonImmutable|null $incorporation_date
 * @property string $society_cmo
 * @property string|null $society_number
 * @property string|null $tin_number
 * @property MasterStatus $status
 * @property CarbonImmutable $agreement_start_date
 * @property CarbonImmutable|null $agreement_end_date
 * @property bool|null $auto_renewal
 * @property bool|null $pre_term
 * @property string|null $post_term
 * @property list<string> $territories_granted
 * @property string|null $bank_holder_name
 * @property string|null $bank_name
 * @property string|null $client_account_number
 * @property string|null $swift_code
 * @property string|null $bank_address
 * @property string|null $person_in_charge_name
 * @property string|null $person_in_charge_title
 * @property string|null $person_in_charge_nric
 * @property string|null $person_in_charge_mobile_no
 * @property string|null $person_in_charge_email
 * @property string|null $taja_percentage_deal
 * @property string|null $client_percentage_deal
 * @property string|null $source_system
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'name',
    'rights_types_granted',
    'ipi_base_number',
    'ipi_name_number',
    'company_registration_number',
    'registration_address',
    'official_email',
    'incorporation_date',
    'society_cmo',
    'society_number',
    'tin_number',
    'status',
    'agreement_start_date',
    'agreement_end_date',
    'auto_renewal',
    'pre_term',
    'post_term',
    'territories_granted',
    'bank_holder_name',
    'bank_name',
    'client_account_number',
    'swift_code',
    'bank_address',
    'person_in_charge_name',
    'person_in_charge_title',
    'person_in_charge_nric',
    'person_in_charge_mobile_no',
    'person_in_charge_email',
    'taja_percentage_deal',
    'client_percentage_deal',
    'source_system',
    'notes',
])]
class Publisher extends Model
{
    /** @use HasFactory<PublisherFactory> */
    use HasFactory, HasGeneratedCode, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'person_in_charge_nric' => IdentityNumber::class,
            'person_in_charge_mobile_no' => PhoneNumber::class,
            'rights_types_granted' => AsEnumCollection::of(RightsType::class),
            'territories_granted' => 'array',
            'status' => MasterStatus::class,
            'incorporation_date' => 'date',
            'agreement_start_date' => 'date',
            'agreement_end_date' => 'date',
            'auto_renewal' => 'boolean',
            'pre_term' => 'boolean',
            'taja_percentage_deal' => 'decimal:2',
            'client_percentage_deal' => 'decimal:2',
        ];
    }

    /**
     * The writers this publisher controls.
     *
     * @return HasMany<Writer, $this>
     */
    public function writers(): HasMany
    {
        return $this->hasMany(Writer::class);
    }

    /**
     * Every party row that ties the publisher to a work.
     *
     * @return HasMany<WorkParty, $this>
     */
    public function parties(): HasMany
    {
        return $this->hasMany(WorkParty::class);
    }

    /**
     * Whether the publisher is still a party of a work that exists. Rows that
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
        return 'publisher_code';
    }

    protected static function generatedCodePrefix(): string
    {
        return 'PUB';
    }
}
