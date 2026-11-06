<?php

namespace App\Actions\Imports;

use App\Actions\Works\CreateWork;
use App\Actions\Works\ResolveInitialEffectiveDate;
use App\Actions\Works\ValidatePartyShares;
use App\Enums\ConflictStatus;
use App\Enums\CsvImportType;
use App\Enums\PartyRole;
use App\Enums\RegistrationStatus;
use App\Http\Requests\Works\StoreWorkRequest;
use App\Models\CsvImport;
use App\Models\Publisher;
use App\Models\Work;
use App\Models\Writer;
use App\Models\WriterAlias;
use App\Rules\TerritoryList;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

/**
 * Imports works from a file with one row per contributor. Neighbouring rows
 * with the same `TAP_Number` + `Work_ID` form one work. A work is imported
 * whole or not at all: when any of its rows has an error, every row of it is
 * skipped and reported.
 *
 * @phpstan-type Party array{
 *     writer_id?: int,
 *     publisher_id?: int,
 *     role: string,
 *     contributor_name: string,
 *     performance_share: string,
 *     mechanical_share: string,
 *     sync_share: string|null,
 *     lyrics_share: string|null,
 *     is_controlled: bool,
 *     territories_controlled: list<string>,
 *     territory_exclusions: list<string>|null,
 * }
 * @phpstan-type WorkRecord array{
 *     work: array<string, mixed>,
 *     isrcs: list<string>,
 *     parties: list<Party>,
 *     lines: list<int>,
 * }
 * @phpstan-type Issue array{column: string|null, message: string}
 */
class WorkImportDefinition extends ImportDefinition
{
    private const DATE = 'YYYY-MM-DD';

    private const PERCENT = 'Number from 0 to 100';

    private const TERRITORIES = 'WW or country codes separated by |, e.g. MY|SG';

    private const MAX_PARTIES = 50;

    /** The scratch field used to find a work that comes back later in the file. */
    private const GROUP_FIELD = 'work_group';

    /**
     * The record attributes that belong to one contributor, not to the work.
     * The remaining columns must be identical on every row of a work.
     *
     * @var list<string>
     */
    private const PARTY_FIELDS = [
        'ipi_no',
        'contributor_name',
        'publisher_code',
        'writer_code',
        'role',
        'performance_share',
        'mechanical_share',
        'sync_share',
        'lyrics_share',
        'is_controlled',
        'territories_controlled',
        'territory_exclusions',
    ];

    private ?DuplicateTracker $tracker = null;

    private int $splitWorkCount = 0;

    private ?string $firstSplitMessage = null;

    /** @var array<string, Writer> */
    private array $writersByCode = [];

    /** @var array<string, Publisher> */
    private array $publishersByCode = [];

    /** @var array<string, array<int, array{writer: Writer, names: list<string>}>> */
    private array $writersByIpi = [];

    /** @var array<string, array<int, Publisher>> */
    private array $publishersByIpi = [];

    /** @var array<string, string> The TAP number of each existing MACP work id, keyed by the lowercase id. */
    private array $existingWorks = [];

    /** @var array<string, array<int, mixed>>|null */
    private ?array $workRules = null;

    public function type(): CsvImportType
    {
        return CsvImportType::Works;
    }

    /**
     * @return list<ImportColumn>
     */
    public function columns(): array
    {
        return [
            new ImportColumn('TAP_Number', false, 'Leave blank. The system generates it', ''),
            new ImportColumn('Work_ID', true, 'Text, unique (MACP work ID)', 'MACP-FAKE-0001', 'macp_work_id'),
            new ImportColumn('English_Work_Title', false, 'Text. At least one of the two titles is required', 'Fake Song One', 'english_title'),
            new ImportColumn('Others_Work_Title', false, 'Text', '', 'other_title'),
            new ImportColumn('ISWC', false, 'T followed by 10 digits', 'T0000000001', 'iswc'),
            new ImportColumn('ISRC', false, 'ISRCs separated by |, 12 characters each', 'MYABC2600001|MYABC2600002', 'isrcs'),
            new ImportColumn('Status', false, 'Work type code, e.g. ORG', 'ORG', 'work_type'),
            new ImportColumn('Genre', false, 'Text', 'Pop', 'genre'),
            new ImportColumn('Language', false, 'Text', 'English', 'language'),
            new ImportColumn('Duration', false, 'HH:MM:SS', '00:03:30', 'duration'),
            new ImportColumn('Artist_Name', false, 'Text', 'Fake Artist', 'artist_name'),
            new ImportColumn('Ori_Work_Title', false, 'Text', '', 'original_work_title'),
            new ImportColumn('Sub_Title_Work', false, 'Text', '', 'sub_title_work'),
            new ImportColumn('Society_No', false, 'Text', 'SOC-0001', 'society_no'),
            new ImportColumn('IPI_No', false, '11 digits. Finds the writer or publisher when no ID is given', '00000000011', 'ipi_no'),
            new ImportColumn('Contributor_Name', true, 'Text, the name as written on the work', 'Fake Writer', 'contributor_name'),
            new ImportColumn('Publisher_Client_ID', false, 'PUB- code, for the roles E and SE', '', 'publisher_code'),
            new ImportColumn('Writer_ID', false, 'WRT- code, for the roles C, A, CA and AR', '', 'writer_code'),
            new ImportColumn('Writer_Roles', true, 'C, A, CA, AR, E or SE', 'C', 'role'),
            new ImportColumn('Perform_Share_Owner_Rights', true, self::PERCENT, '25', 'performance_share'),
            new ImportColumn('Controlled_Mechanical_Share', true, self::PERCENT, '25', 'mechanical_share'),
            new ImportColumn('Controlled_Sync_Share', false, self::PERCENT, '', 'sync_share'),
            new ImportColumn('Controlled_Lyrics_Share', false, self::PERCENT, '', 'lyrics_share'),
            new ImportColumn('Controlled', true, 'Y or N', 'Y', 'is_controlled'),
            new ImportColumn('Territories_Controlled', true, self::TERRITORIES, 'WW', 'territories_controlled'),
            new ImportColumn('Territory_Exclusions', false, 'Country codes separated by |', '', 'territory_exclusions'),
            new ImportColumn('Administration_Start_Date', false, self::DATE.'. Also the date the parties start', '2026-01-01', 'administration_start_date'),
            new ImportColumn('Administration_End_Date', false, self::DATE, '', 'administration_end_date'),
            new ImportColumn('Registration_Date', false, self::DATE, '2026-01-15', 'registration_date'),
            new ImportColumn('Registration_Status', true, 'Draft, Submitted, Accepted, Rejected, Conflict, Amended or Relinquished', 'Submitted', 'registration_status'),
            new ImportColumn('Public_Domain', true, 'Y or N (PD is also accepted as yes)', 'N', 'is_public_domain'),
            new ImportColumn('Song_Link', false, 'Valid URL', 'https://fake-songs.test/song-one', 'song_link'),
            new ImportColumn('Remark', false, 'Text', 'Fake example work', 'remark'),
            new ImportColumn('Release_Date', false, self::DATE, '2026-02-01', 'release_date'),
            new ImportColumn('COMPASS_Work_Internal_No', false, 'Text', '', 'compass_work_internal_no'),
            new ImportColumn('COMPASS_Work_Type', false, 'Text', '', 'compass_work_type'),
            new ImportColumn('COMPASS_Start_Date', false, self::DATE, '', 'compass_start_date'),
            new ImportColumn('WAMI_Work_Internal_No', false, 'Text', '', 'wami_work_internal_no'),
            new ImportColumn('WAMI_Work_Type', false, 'Text', '', 'wami_work_type'),
            new ImportColumn('Conflict_Status', false, 'No Conflict, Share Conflict, Ownership Conflict, Duplicate or Society Query. Blank means No Conflict', 'No Conflict', 'conflict_status'),
            new ImportColumn('Conflict_Notes', false, 'Text', '', 'conflict_notes'),
        ];
    }

    /**
     * One work with three contributors and one with a single contributor.
     *
     * @return list<array<string, string>>
     */
    public function exampleRows(): array
    {
        return [
            $this->exampleRow(),
            $this->exampleRow([
                'IPI_No' => '00000000012',
                'Contributor_Name' => 'Another Fake Writer',
                'Writer_Roles' => 'A',
            ]),
            $this->exampleRow([
                'IPI_No' => '00000000001',
                'Contributor_Name' => 'Fake Publisher',
                'Writer_Roles' => 'E',
                'Perform_Share_Owner_Rights' => '50',
                'Controlled_Mechanical_Share' => '50',
            ]),
            $this->exampleRow([
                'Work_ID' => 'MACP-FAKE-0002',
                'English_Work_Title' => '',
                'Others_Work_Title' => 'Fake Song Two',
                'ISRC' => 'MYABC2600003',
                'IPI_No' => '00000000011',
                'Perform_Share_Owner_Rights' => '100',
                'Controlled_Mechanical_Share' => '100',
            ]),
        ];
    }

    /**
     * Rows of one work are neighbours, so the work is the key. A row without a
     * Work_ID stands alone (and fails validation).
     *
     * @param  array<string, string|null>  $data
     */
    public function groupKey(array $data): ?string
    {
        $workId = $data['Work_ID'] ?? null;

        if ($workId === null) {
            return null;
        }

        return mb_strtolower(($data['TAP_Number'] ?? '').'|'.$workId);
    }

    public function layoutError(): ?string
    {
        if ($this->firstSplitMessage === null) {
            return null;
        }

        if ($this->splitWorkCount === 1) {
            return $this->firstSplitMessage;
        }

        $more = $this->splitWorkCount - 1;

        return "{$this->firstSplitMessage} {$more} more ".Str::plural('group', $more).' of rows '.($more === 1 ? 'is' : 'are').' also apart from the rest of their work.';
    }

    public function begin(CsvImport $import): void
    {
        $this->tracker = new DuplicateTracker($import->id);
        $this->tracker->clear();
        $this->splitWorkCount = 0;
        $this->firstSplitMessage = null;
    }

    public function finish(CsvImport $import): void
    {
        $this->tracker?->clear();
        $this->clearLookups();
    }

    public function validateChunk(array $rows): ChunkResult
    {
        $result = new ChunkResult;

        $this->prepareChunk($rows);

        $groups = $this->splitIntoGroups($rows);

        $keysByLine = [];

        foreach ($groups as $group) {
            $key = $this->groupKey($group[0]['data']);

            if ($key !== null) {
                $keysByLine[$group[0]['line']] = $key;
            }
        }

        $earlierLines = $this->tracker?->register(self::GROUP_FIELD, $keysByLine) ?? [];

        foreach ($groups as $group) {
            $firstLine = $group[0]['line'];

            if (isset($earlierLines[$firstLine])) {
                $this->noteSplitWork((string) $group[0]['data']['Work_ID'], $earlierLines[$firstLine], $firstLine);

                $errors = $this->splitWorkErrors($group, $earlierLines[$firstLine]);
                $record = null;
            } else {
                [$errors, $warnings, $record] = $this->checkGroup($group);

                foreach ($warnings as $line => $lineWarnings) {
                    foreach ($lineWarnings as $warning) {
                        $result->addWarning($line, $warning['column'], $warning['message']);
                    }
                }
            }

            $this->addGroupOutcome($result, $group, $errors, $record);
        }

        return $result;
    }

    /**
     * Create each valid work with its ISRCs and parties, and log the history
     * with the id of the import. A work is saved in its own transaction.
     *
     * @param  array<int, WorkRecord>  $records
     */
    public function commitChunk(CsvImport $import, array $records): array
    {
        $createWork = app(CreateWork::class);

        $imported = 0;
        $failed = [];

        foreach ($records as $record) {
            try {
                $createWork(
                    [...$record['work'], 'isrcs' => $record['isrcs'], 'parties' => $record['parties']],
                    $import->user_id,
                    $import->id,
                );

                $imported += count($record['lines']);
            } catch (QueryException) {
                foreach ($record['lines'] as $line) {
                    $failed[$line] = 'The work could not be saved, most likely because its Work_ID is already used.';
                }
            }
        }

        return ['imported' => $imported, 'failed' => $failed];
    }

    /**
     * Load the masters and works the rows of the chunk refer to, one query each.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $rows
     */
    private function prepareChunk(array $rows): void
    {
        $this->clearLookups();

        $writerCodes = [];
        $publisherCodes = [];
        $ipis = [];
        $workIds = [];

        foreach ($rows as $row) {
            $data = $row['data'];

            if (($data['Writer_ID'] ?? null) !== null) {
                $writerCodes[strtoupper($data['Writer_ID'])] = strtoupper($data['Writer_ID']);
            }

            if (($data['Publisher_Client_ID'] ?? null) !== null) {
                $publisherCodes[strtoupper($data['Publisher_Client_ID'])] = strtoupper($data['Publisher_Client_ID']);
            }

            if (($data['IPI_No'] ?? null) !== null) {
                $ipis[$data['IPI_No']] = $data['IPI_No'];
            }

            if (($data['Work_ID'] ?? null) !== null) {
                $workIds[$data['Work_ID']] = $data['Work_ID'];
            }
        }

        if ($writerCodes !== []) {
            foreach (Writer::query()->whereIn('writer_code', array_values($writerCodes))->get(['id', 'name', 'writer_code', 'agreement_start_date']) as $writer) {
                $this->writersByCode[strtoupper($writer->writer_code)] = $writer;
            }
        }

        if ($publisherCodes !== []) {
            foreach (Publisher::query()->whereIn('publisher_code', array_values($publisherCodes))->get(['id', 'name', 'publisher_code', 'agreement_start_date']) as $publisher) {
                $this->publishersByCode[strtoupper($publisher->publisher_code)] = $publisher;
            }
        }

        if ($ipis !== []) {
            $this->loadIpiMatches(array_values($ipis));
        }

        if ($workIds !== []) {
            foreach (Work::withTrashed()->whereIn('macp_work_id', array_values($workIds))->pluck('tap_number', 'macp_work_id') as $workId => $tapNumber) {
                $this->existingWorks[mb_strtolower((string) $workId)] = $tapNumber;
            }
        }
    }

    /**
     * Find the writers (by name number or pseudonym number) and publishers
     * that hold each IPI number.
     *
     * @param  list<string>  $ipis
     */
    private function loadIpiMatches(array $ipis): void
    {
        foreach (Writer::query()->whereIn('ipi_name_number', $ipis)->get(['id', 'name', 'ipi_name_number', 'agreement_start_date']) as $writer) {
            $entry = $this->writersByIpi[$writer->ipi_name_number][$writer->id] ?? ['writer' => $writer, 'names' => []];
            $entry['names'][] = $writer->name;
            $this->writersByIpi[$writer->ipi_name_number][$writer->id] = $entry;
        }

        $aliases = WriterAlias::query()
            ->whereIn('ipi_base_number', $ipis)
            ->whereHas('writer')
            ->with('writer:id,name,agreement_start_date')
            ->get(['id', 'writer_id', 'name', 'ipi_base_number']);

        foreach ($aliases as $alias) {
            $entry = $this->writersByIpi[$alias->ipi_base_number][$alias->writer_id] ?? ['writer' => $alias->writer, 'names' => [$alias->writer->name]];
            $entry['names'][] = $alias->name;
            $this->writersByIpi[$alias->ipi_base_number][$alias->writer_id] = $entry;
        }

        foreach (Publisher::query()->whereIn('ipi_name_number', $ipis)->get(['id', 'name', 'ipi_name_number', 'agreement_start_date']) as $publisher) {
            $this->publishersByIpi[$publisher->ipi_name_number][$publisher->id] = $publisher;
        }
    }

    private function clearLookups(): void
    {
        $this->writersByCode = [];
        $this->publishersByCode = [];
        $this->writersByIpi = [];
        $this->publishersByIpi = [];
        $this->existingWorks = [];
    }

    /**
     * Cut the rows of a chunk into works: neighbouring rows with the same key.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $rows
     * @return list<list<array{line: int, data: array<string, string|null>}>>
     */
    private function splitIntoGroups(array $rows): array
    {
        $groups = [];
        $currentKey = null;

        foreach ($rows as $row) {
            $key = $this->groupKey($row['data']);

            if ($key !== null && $key === $currentKey) {
                $groups[array_key_last($groups)][] = $row;

                continue;
            }

            $groups[] = [$row];
            $currentKey = $key;
        }

        return $groups;
    }

    private function noteSplitWork(string $workId, int $earlierLine, int $line): void
    {
        $this->splitWorkCount++;

        $this->firstSplitMessage ??= "Work_ID {$workId} is split: its rows are on line {$earlierLine} and again from line {$line}, with other works in between. Sort the file so all rows of a work are together.";
    }

    /**
     * @param  list<array{line: int, data: array<string, string|null>}>  $group
     * @return array<int, list<Issue>>
     */
    private function splitWorkErrors(array $group, int $earlierLine): array
    {
        $errors = [];

        foreach ($group as $row) {
            $errors[$row['line']][] = [
                'column' => 'Work_ID',
                'message' => "This work already started on line {$earlierLine}. Keep all rows of a work together.",
            ];
        }

        return $errors;
    }

    /**
     * A work with an error is skipped whole: the rows without an error of
     * their own are reported as skipped.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $group
     * @param  array<int, list<Issue>>  $errors
     * @param  WorkRecord|null  $record
     */
    private function addGroupOutcome(ChunkResult $result, array $group, array $errors, ?array $record): void
    {
        if ($errors === []) {
            $result->addRecord($group[0]['line'], $record);

            return;
        }

        $firstErrorLine = null;

        foreach ($group as $row) {
            if (isset($errors[$row['line']])) {
                $firstErrorLine = $row['line'];

                break;
            }
        }

        foreach ($group as $row) {
            $line = $row['line'];

            if (! isset($errors[$line])) {
                $result->addError($line, null, "Skipped because line {$firstErrorLine} of this work has an error.");

                continue;
            }

            foreach ($errors[$line] as $error) {
                $result->addError($line, $error['column'], $error['message']);
            }
        }
    }

    /**
     * Check every row of a work and the work as a whole.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $group
     * @return array{0: array<int, list<Issue>>, 1: array<int, list<Issue>>, 2: WorkRecord|null}
     */
    private function checkGroup(array $group): array
    {
        $errors = [];
        $warnings = [];
        $first = $group[0];
        $firstLine = $first['line'];

        if (count($group) > self::MAX_PARTIES) {
            $errors[$firstLine][] = ['column' => 'Work_ID', 'message' => 'A work can have at most '.self::MAX_PARTIES.' contributors (rows).'];
        }

        foreach ($group as $row) {
            if (($row['data']['TAP_Number'] ?? null) !== null) {
                $errors[$row['line']][] = ['column' => 'TAP_Number', 'message' => 'Leave this blank. Updating a work through an import is not supported yet.'];
            }
        }

        $this->mergeIssues($errors, $this->mismatchErrors($group));

        $workAttributes = $this->workAttributes($first['data']);

        foreach ($this->workErrors($workAttributes, $first['data']) as $error) {
            $errors[$firstLine][] = $error;
        }

        $parties = [];
        $masters = [];

        foreach ($group as $row) {
            [$party, $master, $rowErrors, $rowWarnings] = $this->checkParty($row['data']);

            foreach ($rowErrors as $error) {
                $errors[$row['line']][] = $error;
            }

            foreach ($rowWarnings as $warning) {
                $warnings[$row['line']][] = $warning;
            }

            $parties[] = $party;
            $masters[] = $master;
        }

        if ($errors === []) {
            $this->mergeIssues($errors, $this->shareErrors($group, $parties, $masters, $workAttributes));
        }

        if ($errors !== []) {
            return [$errors, $warnings, null];
        }

        $isrcs = $workAttributes['isrcs'];

        return [[], $warnings, [
            'work' => Arr::except($workAttributes, ['isrcs']),
            'isrcs' => CreateWork::uniqueIsrcs($isrcs),
            'parties' => array_values(array_filter($parties)),
            'lines' => array_column($group, 'line'),
        ]];
    }

    /**
     * @param  array<int, list<Issue>>  $issues
     * @param  array<int, list<Issue>>  $more
     */
    private function mergeIssues(array &$issues, array $more): void
    {
        foreach ($more as $line => $lineIssues) {
            foreach ($lineIssues as $issue) {
                $issues[$line][] = $issue;
            }
        }
    }

    /**
     * The columns that describe the work must hold the same value on every row of it.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $group
     * @return array<int, list<Issue>>
     */
    private function mismatchErrors(array $group): array
    {
        $errors = [];
        $first = $group[0];
        $headers = [];

        foreach ($this->columns() as $column) {
            if (! in_array($column->field, self::PARTY_FIELDS, true)) {
                $headers[] = $column->header;
            }
        }

        foreach (array_slice($group, 1) as $row) {
            foreach ($headers as $header) {
                $value = $row['data'][$header] ?? null;
                $expected = $first['data'][$header] ?? null;

                if ($value === $expected) {
                    continue;
                }

                $shown = $expected === null ? 'blank' : '"'.Str::limit($expected, 40).'"';
                $errors[$row['line']][] = ['column' => $header, 'message' => "Must be the same on every row of the work. Line {$first['line']} has {$shown}."];
            }
        }

        return $errors;
    }

    /**
     * The attributes of the work from the first row of it.
     *
     * @param  array<string, string|null>  $data
     * @return array<string, mixed>
     */
    private function workAttributes(array $data): array
    {
        $attributes = [];

        foreach ($this->columns() as $column) {
            if ($column->field === null || in_array($column->field, self::PARTY_FIELDS, true)) {
                continue;
            }

            $value = $data[$column->header] ?? null;

            $attributes[$column->field] = match ($column->field) {
                'registration_status' => $value === null ? null : (RegistrationStatus::fromImport($value)->value ?? $value),
                'conflict_status' => $value === null ? ConflictStatus::NoConflict->value : (ConflictStatus::fromImport($value)->value ?? $value),
                'is_public_domain' => $this->toBoolean($value, allowPublicDomain: true),
                'work_type' => $value === null ? null : strtoupper($value),
                'isrcs' => $this->toList($value),
                default => $value,
            };
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string|null>  $data
     * @return list<Issue>
     */
    private function workErrors(array $attributes, array $data): array
    {
        $errors = [];

        $validator = Validator::make($attributes, $this->checkedWorkRules(), [], $this->attributeNames(['isrcs.*' => 'ISRC']));

        foreach ($validator->errors()->messages() as $key => $messages) {
            foreach ($messages as $message) {
                $errors[] = ['column' => $this->headerFor(explode('.', $key)[0]), 'message' => $message];
            }
        }

        $existing = $this->existingWorks[mb_strtolower((string) ($data['Work_ID'] ?? ''))] ?? null;

        if ($existing !== null) {
            $errors[] = ['column' => 'Work_ID', 'message' => "The Work_ID {$data['Work_ID']} is already registered as {$existing}."];
        }

        return $errors;
    }

    /**
     * The work rules without the `unique` rule, which would query the database for every work.
     *
     * @return array<string, array<int, mixed>>
     */
    private function checkedWorkRules(): array
    {
        return $this->workRules ??= array_map(
            fn (array $rules): array => array_values(array_filter($rules, fn (mixed $rule): bool => ! $rule instanceof Unique)),
            StoreWorkRequest::workRules(),
        );
    }

    /**
     * Check one row: the party fields, then which writer or publisher it is.
     *
     * @param  array<string, string|null>  $data
     * @return array{0: Party|null, 1: Writer|Publisher|null, 2: list<Issue>, 3: list<Issue>}
     */
    private function checkParty(array $data): array
    {
        $roleText = $data['Writer_Roles'] ?? null;
        $role = $roleText === null ? null : PartyRole::fromImport($roleText);

        $attributes = [
            'contributor_name' => $data['Contributor_Name'] ?? null,
            'role' => $role->value ?? $roleText,
            'performance_share' => $data['Perform_Share_Owner_Rights'] ?? null,
            'mechanical_share' => $data['Controlled_Mechanical_Share'] ?? null,
            'sync_share' => $data['Controlled_Sync_Share'] ?? null,
            'lyrics_share' => $data['Controlled_Lyrics_Share'] ?? null,
            'is_controlled' => $this->toBoolean($data['Controlled'] ?? null),
            'territories_controlled' => TerritoryList::parse($data['Territories_Controlled'] ?? null) ?: null,
            'territory_exclusions' => TerritoryList::parse($data['Territory_Exclusions'] ?? null) ?: null,
        ];

        $rules = [...StoreWorkRequest::partyFieldRules(), 'contributor_name' => ['required', 'string', 'max:255']];

        $errors = [];
        $validator = Validator::make($attributes, $rules, [], $this->attributeNames());

        foreach ($validator->errors()->messages() as $key => $messages) {
            foreach ($messages as $message) {
                $errors[] = ['column' => $this->headerFor($key), 'message' => $message];
            }
        }

        $master = null;
        $warnings = [];

        if ($role !== null) {
            [$master, $matchedNames, $identityErrors] = $this->resolveMaster($role, $data);

            $errors = [...$errors, ...$identityErrors];

            $name = $attributes['contributor_name'];

            if ($master !== null && is_string($name) && ! $this->isKnownName($name, $matchedNames)) {
                $warnings[] = ['column' => 'Contributor_Name', 'message' => "The name differs from {$master->name} on record."];
            }
        }

        if ($errors !== [] || $master === null) {
            return [null, $master, $errors, $warnings];
        }

        $party = [
            ...($master instanceof Writer ? ['writer_id' => $master->id] : ['publisher_id' => $master->id]),
            'role' => $attributes['role'],
            'contributor_name' => $attributes['contributor_name'],
            'performance_share' => $attributes['performance_share'],
            'mechanical_share' => $attributes['mechanical_share'],
            'sync_share' => $attributes['sync_share'],
            'lyrics_share' => $attributes['lyrics_share'],
            'is_controlled' => $attributes['is_controlled'],
            'territories_controlled' => $attributes['territories_controlled'],
            'territory_exclusions' => $attributes['territory_exclusions'],
        ];

        /** @var Party $party */
        return [$party, $master, [], $warnings];
    }

    /**
     * Find the writer or publisher of a row: by the code of the matching
     * column, else by the IPI number. The role decides which kind it is.
     *
     * @param  array<string, string|null>  $data
     * @return array{0: Writer|Publisher|null, 1: list<string>, 2: list<Issue>}
     */
    private function resolveMaster(PartyRole $role, array $data): array
    {
        $isWriter = $role->isWriterRole();
        $kind = $isWriter ? 'writer' : 'publisher';
        $codeHeader = $isWriter ? 'Writer_ID' : 'Publisher_Client_ID';
        $otherHeader = $isWriter ? 'Publisher_Client_ID' : 'Writer_ID';
        $code = $data[$codeHeader] ?? null;
        $ipi = $data['IPI_No'] ?? null;

        if (($data[$otherHeader] ?? null) !== null) {
            return [null, [], [['column' => $otherHeader, 'message' => "The role {$role->value} is a {$kind} role, so leave {$otherHeader} blank and use {$codeHeader} or IPI_No."]]];
        }

        if ($code !== null) {
            $master = $isWriter ? ($this->writersByCode[strtoupper($code)] ?? null) : ($this->publishersByCode[strtoupper($code)] ?? null);

            if ($master === null) {
                return [null, [], [['column' => $codeHeader, 'message' => "No {$kind} has the code {$code}. Import the {$kind}s first."]]];
            }

            return [$master, [$master->name], []];
        }

        if ($ipi === null) {
            return [null, [], [['column' => 'IPI_No', 'message' => "Give {$codeHeader} or IPI_No to say which {$kind} this is."]]];
        }

        if (! preg_match('/^\d{11}$/', $ipi)) {
            return [null, [], [['column' => 'IPI_No', 'message' => 'The IPI_No must be an IPI name number of exactly 11 digits.']]];
        }

        $matches = $isWriter ? ($this->writersByIpi[$ipi] ?? []) : ($this->publishersByIpi[$ipi] ?? []);

        if ($matches === []) {
            return [null, [], [['column' => 'IPI_No', 'message' => "No {$kind} has the IPI number {$ipi}. Import the {$kind}s first."]]];
        }

        if (count($matches) > 1) {
            return [null, [], [['column' => 'IPI_No', 'message' => "More than one {$kind} has the IPI number {$ipi}. Use {$codeHeader} to choose one."]]];
        }

        $match = reset($matches);

        return $match instanceof Publisher
            ? [$match, [$match->name], []]
            : [$match['writer'], $match['names'], []];
    }

    /**
     * @param  list<string>  $knownNames
     */
    private function isKnownName(string $name, array $knownNames): bool
    {
        foreach ($knownNames as $known) {
            if (mb_strtolower(trim($known)) === mb_strtolower(trim($name))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run the share and duplicate rules over the parties of a work, with the
     * effective dates the parties will get.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $group
     * @param  list<Party|null>  $parties
     * @param  list<Writer|Publisher|null>  $masters
     * @param  array<string, mixed>  $workAttributes
     * @return array<int, list<Issue>>
     */
    private function shareErrors(array $group, array $parties, array $masters, array $workAttributes): array
    {
        $resolveDate = new ResolveInitialEffectiveDate;
        $rows = [];

        foreach ($parties as $index => $party) {
            $rows[] = [
                'writer_id' => $party['writer_id'] ?? null,
                'publisher_id' => $party['publisher_id'] ?? null,
                'role' => PartyRole::from($party['role']),
                'performance_share' => $party['performance_share'],
                'mechanical_share' => $party['mechanical_share'],
                'sync_share' => $party['sync_share'],
                'lyrics_share' => $party['lyrics_share'],
                'territories_controlled' => $party['territories_controlled'],
                'effective_start_date' => $resolveDate($workAttributes['administration_start_date'] ?? null, $masters[$index]),
                'effective_end_date' => null,
            ];
        }

        $headers = [
            'performance' => 'Perform_Share_Owner_Rights',
            'mechanical' => 'Controlled_Mechanical_Share',
            'sync' => 'Controlled_Sync_Share',
            'lyrics' => 'Controlled_Lyrics_Share',
        ];

        $errors = [];

        foreach ((new ValidatePartyShares)($rows) as $error) {
            if ($error['field'] === 'parties') {
                $column = null;

                foreach ($headers as $label => $header) {
                    if (str_contains($error['message'], "The {$label} shares")) {
                        $column = $header;
                    }
                }

                $errors[$group[0]['line']][] = ['column' => $column, 'message' => $error['message']];

                continue;
            }

            $index = (int) explode('.', $error['field'])[1];
            $errors[$group[$index]['line']][] = ['column' => 'Contributor_Name', 'message' => $error['message']];
        }

        return $errors;
    }

    /**
     * Turn `Y`/`N` (and `PD` for the public domain flag) into a boolean.
     * Anything else is passed on as written, so validation names the column.
     */
    private function toBoolean(?string $value, bool $allowPublicDomain = false): mixed
    {
        if ($value === null) {
            return null;
        }

        return match (strtolower($value)) {
            'y', 'yes' => true,
            'n', 'no' => false,
            'pd' => $allowPublicDomain ? true : $value,
            default => $value,
        };
    }

    /**
     * @return list<string>
     */
    private function toList(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[|,;]/', $value) ?: []),
            fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function attributeNames(array $extra = []): array
    {
        $names = [];

        foreach ($this->columns() as $column) {
            if ($column->field !== null) {
                $names[$column->field] = $column->header;
            }
        }

        return [...$names, ...$extra];
    }

    private function headerFor(string $field): ?string
    {
        foreach ($this->columns() as $column) {
            if ($column->field === $field) {
                return $column->header;
            }
        }

        return null;
    }
}
