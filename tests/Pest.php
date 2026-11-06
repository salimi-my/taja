<?php

use App\Actions\Imports\BuildCsvTemplate;
use App\Actions\Imports\ImportRegistry;
use App\Enums\CsvImportType;
use App\Models\CsvImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Build the text of a CSV file.
 *
 * @param  list<string>  $headers
 * @param  list<array<string, string>>  $rows  Cells keyed by header; a missing header is left blank.
 */
function csvContent(array $headers, array $rows): string
{
    return BuildCsvTemplate::toCsv([
        $headers,
        ...array_map(
            fn (array $row): array => array_map(fn (string $header): string => $row[$header] ?? '', $headers),
            $rows,
        ),
    ]);
}

/**
 * Upload CSV rows to the import page of a type, as the signed in user.
 *
 * @param  list<array<string, string>>  $rows
 * @param  list<string>|null  $headers  Defaults to every column of the import.
 */
function uploadCsv(TestCase $test, CsvImportType $type, array $rows, ?array $headers = null): TestResponse
{
    $headers ??= ImportRegistry::find($type)->headers();

    return $test->post(route('imports.store', $type), [
        'file' => UploadedFile::fake()->createWithContent('import.csv', csvContent($headers, $rows)),
    ]);
}

/**
 * A valid publisher row. Every value is fake and unique per number.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function importPublisherRow(int $number, array $overrides = []): array
{
    return [
        ...ImportRegistry::find(CsvImportType::Publishers)->exampleRows()[0],
        'Publisher_Name' => "Fake Publisher {$number}",
        'IPI_Name_Number' => str_pad((string) $number, 11, '0', STR_PAD_LEFT),
        'Company_Registration_Number' => "REG-{$number}",
        ...$overrides,
    ];
}

/**
 * A valid writer row. Every value is fake and unique per number.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function importWriterRow(int $number, array $overrides = []): array
{
    return [
        ...ImportRegistry::find(CsvImportType::Writers)->exampleRows()[0],
        'Name' => "Fake Writer {$number}",
        'IPI_Name_Number' => str_pad((string) $number, 11, '0', STR_PAD_LEFT),
        'NRIC' => "NRIC-{$number}",
        'Email' => "writer{$number}@fake-writer.test",
        'Heir_NRIC' => "HEIR-{$number}",
        ...$overrides,
    ];
}

/**
 * A valid works row: one contributor of a work. Every value is fake. By default
 * the contributor is the writer with IPI number 00000000011 with 25% shares.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function importWorkRow(int $number, array $overrides = []): array
{
    return [
        ...ImportRegistry::find(CsvImportType::Works)->exampleRows()[0],
        'Work_ID' => "MACP-FAKE-{$number}",
        'English_Work_Title' => "Fake Song {$number}",
        ...$overrides,
    ];
}

/**
 * The errors or warnings of the latest import as `line:column` => messages.
 *
 * @return array<string, list<string>>
 */
function latestImportIssues(string $kind = 'errors'): array
{
    $issues = [];

    foreach (CsvImport::query()->latest('id')->firstOrFail()->{$kind} as $issue) {
        $issues["{$issue['line']}:{$issue['column']}"][] = $issue['message'];
    }

    return $issues;
}
