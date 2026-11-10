<?php

namespace App\Actions\Lists;

use App\Casts\IdentityNumber;
use App\Enums\RightsType;
use App\Models\Writer;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The writer list: the query the datatable pages through and the CSV export
 * downloads in full, so the screen and the file cannot drift.
 */
class WriterList
{
    /** The pseudonyms the import template, and so the export, has columns for. */
    private const ALIAS_COUNT = 4;

    /**
     * @param  array{search: string, status: list<string>, societies: list<string>, publishers: list<string>, sort: string, direction: string}  $filters
     * @return Builder<Writer>
     */
    public function query(array $filters): Builder
    {
        return Writer::query()
            ->with(['publisher', 'aliases'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.addcslashes($filters['search'], '%_\\').'%';
                $nricDigits = IdentityNumber::normalize($filters['search']);

                $query->where(function (Builder $query) use ($term, $nricDigits): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('writer_code', 'like', $term)
                        ->orWhere('ipi_name_number', 'like', $term)
                        ->orWhere('ipi_base_number', 'like', $term)
                        ->orWhere('nric', 'like', $term)
                        ->when($nricDigits !== null, fn (Builder $query) => $query->orWhere('nric', 'like', '%'.addcslashes($nricDigits, '%_\\').'%'))
                        ->orWhereHas('aliases', function (Builder $query) use ($term): void {
                            $query->where('name', 'like', $term)
                                ->orWhere('ipi_base_number', 'like', $term);
                        });
                });
            })
            ->when($filters['status'] !== [], fn (Builder $query) => $query->whereIn('status', $filters['status']))
            ->when($filters['societies'] !== [], fn (Builder $query) => $query->whereIn('cmo_society', $filters['societies']))
            ->when($filters['publishers'] !== [], fn (Builder $query) => $query->whereIn('publisher_id', $filters['publishers']))
            ->when(
                $filters['direction'] === 'desc',
                fn (Builder $query) => $query->orderByDesc($filters['sort']),
                fn (Builder $query) => $query->orderBy($filters['sort']),
            )
            ->orderBy('id');
    }

    /**
     * Every writer column in the order of the import template, with the writer
     * code first, so an export can be read back in.
     *
     * @return list<string>
     */
    public function csvHeaders(): array
    {
        $headers = [
            'Writer_ID', 'Name', 'Writer_Role', 'IPI_Base_Number', 'IPI_Name_Number', 'NRIC', 'Date_Of_Birth',
            'Date_Of_Death', 'Nationality', 'CMO_Society', 'Publisher_Client_ID', 'Publisher_Name',
            'Controlled_Territories', 'Rights_Types_Controlled', 'TIN_Number', 'Email', 'Phone', 'Address',
            'Status', 'Agreement_Start_Date', 'Agreement_End_Date', 'Auto_Renewal', 'Pre_Term', 'Post_Term',
            'Taja_Percentage_Deal', 'Writer_Percentage_Deal', 'Bank_Name', 'Bank_Account_Number', 'Swift_Code',
            'Bank_Address', 'Heir_Name', 'Heir_NRIC', 'Heir_Relationship', 'Heir_Mobile_Number', 'Heir_Email',
            'Source_System', 'Notes',
        ];

        foreach (range(1, self::ALIAS_COUNT) as $number) {
            $headers[] = "Pseudonym_Stage_Name_{$number}";
            $headers[] = "IPI_Base_Number_{$number}";
        }

        return $headers;
    }

    /**
     * @param  iterable<Writer>  $writers
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $writers): Generator
    {
        foreach ($writers as $writer) {
            $row = [
                $writer->writer_code,
                $writer->name,
                $writer->default_role?->value,
                $writer->ipi_base_number,
                $writer->ipi_name_number,
                $writer->nric,
                $writer->date_of_birth?->toDateString(),
                $writer->date_of_death?->toDateString(),
                $writer->nationality,
                $writer->cmo_society,
                $writer->publisher?->publisher_code,
                $writer->publisher?->name,
                implode('|', $writer->controlled_territories ?? []),
                $writer->rights_types_controlled?->map(fn (RightsType $type): string => $type->label())->implode('|'),
                $writer->tin_number,
                $writer->email,
                $writer->phone,
                $writer->address,
                $writer->status->label(),
                $writer->agreement_start_date->toDateString(),
                $writer->agreement_end_date?->toDateString(),
                $writer->auto_renewal,
                $writer->pre_term,
                $writer->post_term,
                $writer->taja_percentage_deal,
                $writer->writer_percentage_deal,
                $writer->bank_name,
                $writer->bank_account_number,
                $writer->swift_code,
                $writer->bank_address,
                $writer->heir_name,
                $writer->heir_nric,
                $writer->heir_relationship,
                $writer->heir_mobile_number,
                $writer->heir_email,
                $writer->source_system,
                $writer->notes,
            ];

            $aliases = $writer->aliases->values();

            foreach (range(0, self::ALIAS_COUNT - 1) as $index) {
                $row[] = $aliases->get($index)?->name;
                $row[] = $aliases->get($index)?->ipi_base_number;
            }

            yield $row;
        }
    }
}
