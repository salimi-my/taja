<?php

namespace App\Actions\Lists;

use App\Enums\RightsType;
use App\Models\Publisher;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The publisher list: the query the datatable pages through and the CSV export
 * downloads in full, so the screen and the file cannot drift.
 */
class PublisherList
{
    /**
     * @param  array{search: string, status: list<string>, societies: list<string>, rights_types: list<string>, territories: list<string>, sort: string, direction: string}  $filters
     * @return Builder<Publisher>
     */
    public function query(array $filters): Builder
    {
        return Publisher::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.addcslashes($filters['search'], '%_\\').'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('publisher_code', 'like', $term)
                        ->orWhere('ipi_name_number', 'like', $term)
                        ->orWhere('ipi_base_number', 'like', $term)
                        ->orWhere('company_registration_number', 'like', $term);
                });
            })
            ->when($filters['status'] !== [], fn (Builder $query) => $query->whereIn('status', $filters['status']))
            ->when($filters['societies'] !== [], fn (Builder $query) => $query->whereIn('society_cmo', $filters['societies']))
            ->when($filters['rights_types'] !== [], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    foreach ($filters['rights_types'] as $type) {
                        $query->orWhereJsonContains('rights_types_granted', $type);
                    }
                });
            })
            ->when($filters['territories'] !== [], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    foreach ([...$filters['territories'], 'WW'] as $territory) {
                        $query->orWhereJsonContains('territories_granted', $territory);
                    }
                });
            })
            ->when(
                $filters['direction'] === 'desc',
                fn (Builder $query) => $query->orderByDesc($filters['sort']),
                fn (Builder $query) => $query->orderBy($filters['sort']),
            )
            ->orderBy('id');
    }

    /**
     * Every publisher column in the order of the import template, with the
     * publisher code first, so an export can be read back in.
     *
     * @return list<string>
     */
    public function csvHeaders(): array
    {
        return [
            'Publisher_Client_ID', 'Publisher_Name', 'Rights_Types_Granted', 'IPI_Base_Number', 'IPI_Name_Number',
            'Company_Registration_Number', 'Registration_Address', 'Official_Email', 'Incorporation_Date',
            'Society_CMO', 'Society_Number', 'TIN_Number', 'Status', 'Agreement_Start_Date', 'Agreement_End_Date',
            'Auto_Renewal', 'Pre_Term', 'Post_Term', 'Territories_Granted', 'Bank_Holder_Name', 'Bank_Name',
            'Client_Account_Number', 'Swift_Code', 'Bank_Address', 'Person_In_Charge_Name', 'Person_In_Charge_Title',
            'Person_In_Charge_NRIC', 'Person_In_Charge_Mobile_No', 'Person_In_Charge_Email', 'Taja_Percentage_Deal',
            'Client_Percentage_Deal', 'Source_System', 'Notes',
        ];
    }

    /**
     * @param  iterable<Publisher>  $publishers
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $publishers): Generator
    {
        foreach ($publishers as $publisher) {
            yield [
                $publisher->publisher_code,
                $publisher->name,
                $publisher->rights_types_granted->map(fn (RightsType $type): string => $type->label())->implode('|'),
                $publisher->ipi_base_number,
                $publisher->ipi_name_number,
                $publisher->company_registration_number,
                $publisher->registration_address,
                $publisher->official_email,
                $publisher->incorporation_date?->toDateString(),
                $publisher->society_cmo,
                $publisher->society_number,
                $publisher->tin_number,
                $publisher->status->label(),
                $publisher->agreement_start_date->toDateString(),
                $publisher->agreement_end_date?->toDateString(),
                $publisher->auto_renewal,
                $publisher->pre_term,
                $publisher->post_term,
                implode('|', $publisher->territories_granted),
                $publisher->bank_holder_name,
                $publisher->bank_name,
                $publisher->client_account_number,
                $publisher->swift_code,
                $publisher->bank_address,
                $publisher->person_in_charge_name,
                $publisher->person_in_charge_title,
                $publisher->person_in_charge_nric,
                $publisher->person_in_charge_mobile_no,
                $publisher->person_in_charge_email,
                $publisher->taja_percentage_deal,
                $publisher->client_percentage_deal,
                $publisher->source_system,
                $publisher->notes,
            ];
        }
    }
}
