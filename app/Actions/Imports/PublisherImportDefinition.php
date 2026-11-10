<?php

namespace App\Actions\Imports;

use App\Actions\Masters\RecordMasterHistory;
use App\Enums\CsvImportType;
use App\Enums\MasterChangeType;
use App\Http\Requests\Publishers\StorePublisherRequest;
use App\Models\CsvImport;
use App\Models\Publisher;

/**
 * Imports publishers: one row is one publisher. The `PUB-` code is generated.
 */
class PublisherImportDefinition extends RowImportDefinition
{
    private const DATE = 'YYYY-MM-DD';

    private const YES_NO = 'Y or N';

    private const PERCENT = 'Number from 0 to 100';

    public function type(): CsvImportType
    {
        return CsvImportType::Publishers;
    }

    /**
     * @return list<ImportColumn>
     */
    public function columns(): array
    {
        return [
            new ImportColumn('Publisher_Name', true, 'Text', 'Fake Music Publishing Sdn Bhd', 'name'),
            new ImportColumn('Rights_Types_Granted', true, 'Pipe-separated: Performance, Mechanical, Sync, Lyrics, Digital', 'Performance|Mechanical', 'rights_types_granted', ImportCast::RightsTypes),
            new ImportColumn('IPI_Base_Number', false, 'Text', 'I-000000001-1', 'ipi_base_number'),
            new ImportColumn('IPI_Name_Number', false, '11 digits, unique', '00000000001', 'ipi_name_number'),
            new ImportColumn('Company_Registration_Number', true, 'Text, unique', '202601000001', 'company_registration_number'),
            new ImportColumn('Registration_Address', true, 'Text', '1 Jalan Fake, 50000 Kuala Lumpur', 'registration_address'),
            new ImportColumn('Official_Email', true, 'Valid email', 'office@fake-publisher.test', 'official_email'),
            new ImportColumn('Incorporation_Date', false, self::DATE, '2020-01-15', 'incorporation_date'),
            new ImportColumn('Society_CMO', true, 'Text, e.g. MACP, WAMI, Non-Member', 'MACP', 'society_cmo'),
            new ImportColumn('Society_Number', false, 'Text', 'SOC-0001', 'society_number'),
            new ImportColumn('TIN_Number', false, 'Text', 'TIN000000001', 'tin_number'),
            new ImportColumn('Status', true, 'Active or Inactive', 'Active', 'status', ImportCast::Status),
            new ImportColumn('Agreement_Start_Date', true, self::DATE, '2026-01-01', 'agreement_start_date'),
            new ImportColumn('Agreement_End_Date', false, self::DATE.', not before the start date', '2028-12-31', 'agreement_end_date'),
            new ImportColumn('Auto_Renewal', false, self::YES_NO, 'Y', 'auto_renewal', ImportCast::Boolean),
            new ImportColumn('Pre_Term', false, self::YES_NO, 'N', 'pre_term', ImportCast::Boolean),
            new ImportColumn('Post_Term', false, 'Text', 'During Active Year', 'post_term'),
            new ImportColumn('Territories_Granted', true, 'WW or pipe-separated ISO country codes', 'MY|SG', 'territories_granted', ImportCast::Territories),
            new ImportColumn('Bank_Holder_Name', false, 'Text', 'Fake Music Publishing Sdn Bhd', 'bank_holder_name'),
            new ImportColumn('Bank_Name', false, 'Text', 'Fake Bank', 'bank_name'),
            new ImportColumn('Client_Account_Number', false, 'Text', '000000000001', 'client_account_number'),
            new ImportColumn('Swift_Code', false, 'Text', 'FAKEMYKL', 'swift_code'),
            new ImportColumn('Bank_Address', false, 'Text', '2 Jalan Fake, 50000 Kuala Lumpur', 'bank_address'),
            new ImportColumn('Person_In_Charge_Name', false, 'Text', 'Fake Person', 'person_in_charge_name'),
            new ImportColumn('Person_In_Charge_Title', false, 'Text', 'Director', 'person_in_charge_title'),
            new ImportColumn('Person_In_Charge_NRIC', false, 'NRIC or passport number, saved without dashes', '000000000000', 'person_in_charge_nric', ImportCast::Nric),
            new ImportColumn('Person_In_Charge_Mobile_No', false, 'International number such as +60100000000, or a Malaysian number starting with 0', '+60100000000', 'person_in_charge_mobile_no', ImportCast::Phone),
            new ImportColumn('Person_In_Charge_Email', false, 'Valid email', 'person@fake-publisher.test', 'person_in_charge_email'),
            new ImportColumn('Taja_Percentage_Deal', false, self::PERCENT.'; with the client deal it must total 100', '60', 'taja_percentage_deal'),
            new ImportColumn('Client_Percentage_Deal', false, self::PERCENT, '40', 'client_percentage_deal'),
            new ImportColumn('Source_System', false, 'Text', 'Manual', 'source_system'),
            new ImportColumn('Notes', false, 'Text', 'Fake example row', 'notes'),
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    public function exampleRows(): array
    {
        return [
            $this->exampleRow(),
            $this->exampleRow([
                'Publisher_Name' => 'Another Fake Publisher',
                'Rights_Types_Granted' => 'Performance',
                'IPI_Name_Number' => '00000000002',
                'Company_Registration_Number' => '202601000002',
                'Official_Email' => 'office@another-fake-publisher.test',
                'Territories_Granted' => 'WW',
                'Status' => 'Inactive',
                'Person_In_Charge_Email' => 'person@another-fake-publisher.test',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return StorePublisherRequest::publisherRules();
    }

    protected function table(): string
    {
        return 'publishers';
    }

    /**
     * @return array<string, string>
     */
    protected function uniqueFields(): array
    {
        return [
            'company_registration_number' => 'Company_Registration_Number',
            'ipi_name_number' => 'IPI_Name_Number',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{column: string|null, message: string}>
     */
    protected function crossChecks(array $attributes): array
    {
        $taja = $attributes['taja_percentage_deal'] ?? null;
        $client = $attributes['client_percentage_deal'] ?? null;

        if (filled($taja) && filled($client) && abs((float) $taja + (float) $client - 100) > 0.001) {
            return [['column' => 'Client_Percentage_Deal', 'message' => 'Taja_Percentage_Deal and Client_Percentage_Deal must total 100.']];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createRecord(CsvImport $import, array $attributes): void
    {
        $publisher = Publisher::create($attributes);

        app(RecordMasterHistory::class)(
            $publisher,
            MasterChangeType::Imported,
            new: RecordMasterHistory::withoutEmptyValues(RecordMasterHistory::snapshot($publisher)),
            userId: $import->user_id,
            csvImportId: $import->id,
        );
    }
}
