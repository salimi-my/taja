<?php

namespace App\Actions\Imports;

use App\Actions\Masters\RecordMasterHistory;
use App\Enums\CsvImportType;
use App\Enums\MasterChangeType;
use App\Http\Requests\Writers\StoreWriterRequest;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\Writer;
use Illuminate\Support\Arr;

/**
 * Imports writers with up to four pseudonyms: one row is one writer. The
 * `WRT-` code is generated.
 */
class WriterImportDefinition extends RowImportDefinition
{
    private const DATE = 'YYYY-MM-DD';

    private const YES_NO = 'Y or N';

    private const PERCENT = 'Number from 0 to 100';

    private const PHONE = 'International number such as +60110000000, or a Malaysian number starting with 0';

    private const ALIAS_COUNT = 4;

    /** @var array<string, int> Publisher id by uppercase `PUB-` code. */
    private array $publisherIds = [];

    public function type(): CsvImportType
    {
        return CsvImportType::Writers;
    }

    /**
     * @return list<ImportColumn>
     */
    public function columns(): array
    {
        $columns = [
            new ImportColumn('Name', true, 'Text, the preferred display name', 'Fake Writer', 'name'),
            new ImportColumn('Writer_Role', false, 'C, A, CA, AR or SA', 'CA', 'default_role', ImportCast::Role),
            new ImportColumn('IPI_Base_Number', false, 'Text', 'I-000000001-1', 'ipi_base_number'),
            new ImportColumn('IPI_Name_Number', false, '11 digits, unique', '00000000011', 'ipi_name_number'),
            new ImportColumn('NRIC', false, 'NRIC or passport number, saved without dashes', '000000000001', 'nric', ImportCast::Nric),
            new ImportColumn('Date_Of_Birth', false, self::DATE, '1990-05-20', 'date_of_birth'),
            new ImportColumn('Date_Of_Death', false, self::DATE, '', 'date_of_death'),
            new ImportColumn('Nationality', false, 'Text', 'Malaysian', 'nationality'),
            new ImportColumn('CMO_Society', true, 'Text, e.g. MACP, WAMI, Non-Member', 'MACP', 'cmo_society'),
            new ImportColumn('Publisher_Client_ID', false, 'An existing PUB- code', ''),
            new ImportColumn('Controlled_Territories', false, 'WW or pipe-separated ISO country codes', 'MY|SG', 'controlled_territories', ImportCast::Territories),
            new ImportColumn('Rights_Types_Controlled', false, 'Pipe-separated: Performance, Mechanical, Sync, Lyrics, Digital', 'Performance|Mechanical', 'rights_types_controlled', ImportCast::RightsTypes),
            new ImportColumn('TIN_Number', false, 'Text', 'TIN000000011', 'tin_number'),
            new ImportColumn('Email', false, 'Valid email', 'writer@fake-writer.test', 'email'),
            new ImportColumn('Phone', false, self::PHONE, '+60110000000', 'phone', ImportCast::Phone),
            new ImportColumn('Address', false, 'Text', '3 Jalan Fake, 50000 Kuala Lumpur', 'address'),
            new ImportColumn('Status', true, 'Active or Inactive', 'Active', 'status', ImportCast::Status),
            new ImportColumn('Agreement_Start_Date', true, self::DATE, '2026-01-01', 'agreement_start_date'),
            new ImportColumn('Agreement_End_Date', false, self::DATE.', not before the start date', '2028-12-31', 'agreement_end_date'),
            new ImportColumn('Auto_Renewal', false, self::YES_NO, 'Y', 'auto_renewal', ImportCast::Boolean),
            new ImportColumn('Pre_Term', false, self::YES_NO, 'N', 'pre_term', ImportCast::Boolean),
            new ImportColumn('Post_Term', false, 'Text', 'During Active Year', 'post_term'),
            new ImportColumn('Taja_Percentage_Deal', false, self::PERCENT.'; with the writer deal it must total 100', '60', 'taja_percentage_deal'),
            new ImportColumn('Writer_Percentage_Deal', false, self::PERCENT, '40', 'writer_percentage_deal'),
            new ImportColumn('Bank_Name', false, 'Text', 'Fake Bank', 'bank_name'),
            new ImportColumn('Bank_Account_Number', false, 'Text', '000000000011', 'bank_account_number'),
            new ImportColumn('Swift_Code', false, 'Text', 'FAKEMYKL', 'swift_code'),
            new ImportColumn('Bank_Address', false, 'Text', '4 Jalan Fake, 50000 Kuala Lumpur', 'bank_address'),
            new ImportColumn('Heir_Name', false, 'Text', 'Fake Heir', 'heir_name'),
            new ImportColumn('Heir_NRIC', false, 'NRIC or passport number, saved without dashes', '000000000002', 'heir_nric', ImportCast::Nric),
            new ImportColumn('Heir_Relationship', false, 'Text', 'Spouse', 'heir_relationship'),
            new ImportColumn('Heir_Mobile_Number', false, self::PHONE, '+60120000000', 'heir_mobile_number', ImportCast::Phone),
            new ImportColumn('Heir_Email', false, 'Valid email', 'heir@fake-writer.test', 'heir_email'),
            new ImportColumn('Source_System', false, 'Text', 'Manual', 'source_system'),
            new ImportColumn('Notes', false, 'Text', 'Fake example row', 'notes'),
        ];

        foreach (range(1, self::ALIAS_COUNT) as $number) {
            $columns[] = new ImportColumn("Pseudonym_Stage_Name_{$number}", false, 'Text', $number === 1 ? 'Fake Stage Name' : '', "alias_name_{$number}");
            $columns[] = new ImportColumn("IPI_Base_Number_{$number}", false, 'Text, needs the pseudonym', $number === 1 ? 'I-000000002-2' : '', "alias_ipi_{$number}");
        }

        return $columns;
    }

    /**
     * @return list<array<string, string>>
     */
    public function exampleRows(): array
    {
        return [
            $this->exampleRow(),
            $this->exampleRow([
                'Name' => 'Another Fake Writer',
                'Writer_Role' => 'A',
                'IPI_Name_Number' => '00000000012',
                'NRIC' => '000000000003',
                'Email' => 'writer@another-fake-writer.test',
                'Heir_NRIC' => '000000000004',
                'Pseudonym_Stage_Name_1' => '',
                'IPI_Base_Number_1' => '',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = Arr::except(StoreWriterRequest::writerRules(), ['aliases', 'aliases.*.name', 'aliases.*.ipi_base_number']);

        foreach (range(1, self::ALIAS_COUNT) as $number) {
            $rules["alias_name_{$number}"] = ['nullable', 'string', 'max:255', "required_with:alias_ipi_{$number}"];
            $rules["alias_ipi_{$number}"] = ['nullable', 'string', 'max:50'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function uniqueFields(): array
    {
        return ['ipi_name_number' => 'IPI_Name_Number'];
    }

    /**
     * @return array<string, string>
     */
    protected function warnOnRepeatFields(): array
    {
        return ['nric' => 'NRIC'];
    }

    protected function table(): string
    {
        return 'writers';
    }

    /**
     * Find the publishers of every `PUB-` code in the chunk with one query.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $rows
     */
    protected function prepareChunk(array $rows): void
    {
        $codes = [];

        foreach ($rows as $row) {
            $code = $row['data']['Publisher_Client_ID'] ?? null;

            if ($code !== null) {
                $codes[strtoupper($code)] = true;
            }
        }

        $this->publisherIds = $codes === [] ? [] : Publisher::query()
            ->whereIn('publisher_code', array_keys($codes))
            ->pluck('id', 'publisher_code')
            ->mapWithKeys(fn (int $id, string $code): array => [strtoupper($code) => $id])
            ->all();
    }

    /**
     * Resolve the `PUB-` code of the row to a publisher.
     *
     * @param  array<string, string|null>  $data
     * @param  array<string, mixed>  $attributes
     * @return list<array{column: string|null, message: string}>
     */
    protected function resolveExtras(array $data, array &$attributes): array
    {
        $code = $data['Publisher_Client_ID'] ?? null;

        $attributes['publisher_id'] = null;

        if ($code === null) {
            return [];
        }

        $publisherId = $this->publisherIds[strtoupper($code)] ?? null;

        if ($publisherId === null) {
            return [['column' => 'Publisher_Client_ID', 'message' => "No publisher has the code {$code}. Import the publishers first."]];
        }

        $attributes['publisher_id'] = $publisherId;

        return [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{column: string|null, message: string}>
     */
    protected function crossChecks(array $attributes): array
    {
        $taja = $attributes['taja_percentage_deal'] ?? null;
        $writer = $attributes['writer_percentage_deal'] ?? null;

        if (filled($taja) && filled($writer) && abs((float) $taja + (float) $writer - 100) > 0.001) {
            return [['column' => 'Writer_Percentage_Deal', 'message' => 'Taja_Percentage_Deal and Writer_Percentage_Deal must total 100.']];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createRecord(CsvImport $import, array $attributes): void
    {
        $writer = Writer::create(array_filter(
            $attributes,
            fn (string $field): bool => ! str_starts_with($field, 'alias_'),
            ARRAY_FILTER_USE_KEY,
        ));

        $position = 0;

        foreach (range(1, self::ALIAS_COUNT) as $number) {
            $name = $attributes["alias_name_{$number}"] ?? null;

            if ($name === null) {
                continue;
            }

            $writer->aliases()->create([
                'name' => $name,
                'ipi_base_number' => $attributes["alias_ipi_{$number}"] ?? null,
                'position' => ++$position,
            ]);
        }

        app(RecordMasterHistory::class)(
            $writer,
            MasterChangeType::Imported,
            new: RecordMasterHistory::withoutEmptyValues(RecordMasterHistory::snapshot($writer)),
            userId: $import->user_id,
            csvImportId: $import->id,
        );
    }
}
