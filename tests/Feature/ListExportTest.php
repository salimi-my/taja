<?php

use App\Models\Publisher;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Illuminate\Testing\TestResponse;

/**
 * The rows of a streamed CSV response, without the byte order mark.
 *
 * @return list<list<string|null>>
 */
function csvRowsOf(TestResponse $response): array
{
    $content = ltrim($response->streamedContent(), "\xEF\xBB\xBF");
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $content);
    rewind($stream);

    $rows = [];

    while (($row = fgetcsv($stream, escape: '')) !== false) {
        $rows[] = $row;
    }

    fclose($stream);

    return $rows;
}

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('writers', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('writers.export'))->assertRedirect(route('login'));
    });

    test('downloads every writer, not just one page', function () {
        Writer::factory()->count(12)->create();

        $response = $this->actingAs($this->user)->get(route('writers.export'))->assertOk();

        expect($response->headers->get('Content-Type'))->toContain('text/csv')
            ->and($response->headers->get('Content-Disposition'))->toContain('writers-');

        $rows = csvRowsOf($response);

        expect($rows)->toHaveCount(13)
            ->and($rows[0])->toContain('Writer_ID', 'Name', 'Status');
    });

    test('exports the same rows the filters show on screen', function () {
        $publisher = Publisher::factory()->create();
        $matching = Writer::factory()->create(['name' => 'Fake Match Writer', 'publisher_id' => $publisher->id]);
        Writer::factory()->create(['name' => 'Fake Other Writer']);

        $rows = csvRowsOf(
            $this->actingAs($this->user)->get(route('writers.export', ['search' => 'Match', 'publishers' => [$publisher->id]])),
        );

        expect($rows)->toHaveCount(2)
            ->and($rows[1][0])->toBe($matching->writer_code)
            ->and($rows[1][1])->toBe('Fake Match Writer');
    });

    test('includes every writer column and up to four pseudonyms', function () {
        $writer = Writer::factory()->create(['name' => 'Fake Writer', 'bank_name' => 'Fake Bank']);
        $writer->aliases()->create(['name' => 'Fake Stage One', 'ipi_base_number' => 'I-000000001-1', 'position' => 1]);
        $writer->aliases()->create(['name' => 'Fake Stage Two', 'position' => 2]);

        [$headers, $row] = csvRowsOf($this->actingAs($this->user)->get(route('writers.export')));
        $cells = array_combine($headers, $row);

        expect($headers)->toHaveCount(45)
            ->and($headers)->toContain('NRIC', 'Bank_Account_Number', 'Heir_Email', 'Pseudonym_Stage_Name_4')
            ->and($cells['Bank_Name'])->toBe('Fake Bank')
            ->and($cells['Pseudonym_Stage_Name_1'])->toBe('Fake Stage One')
            ->and($cells['IPI_Base_Number_1'])->toBe('I-000000001-1')
            ->and($cells['Pseudonym_Stage_Name_2'])->toBe('Fake Stage Two')
            ->and($cells['Pseudonym_Stage_Name_3'])->toBe('');
    });

    test('rejects an unknown sort column', function () {
        $this->actingAs($this->user)
            ->get(route('writers.export', ['sort' => 'nric']))
            ->assertSessionHasErrors('sort');
    });
});

describe('publishers', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('publishers.export'))->assertRedirect(route('login'));
    });

    test('exports the publishers matching the filters', function () {
        $matching = Publisher::factory()->create(['name' => 'Fake Match Publishing']);
        Publisher::factory()->create(['name' => 'Fake Other Publishing']);

        $response = $this->actingAs($this->user)->get(route('publishers.export', ['search' => 'Match']))->assertOk();
        $rows = csvRowsOf($response);

        expect($response->headers->get('Content-Disposition'))->toContain('publishers-')
            ->and($rows[0])->toContain('Publisher_Client_ID', 'Rights_Types_Granted')
            ->and($rows)->toHaveCount(2)
            ->and($rows[1][0])->toBe($matching->publisher_code);
    });

    test('includes every publisher column', function () {
        Publisher::factory()->create(['bank_name' => 'Fake Bank', 'notes' => 'Fake note']);

        [$headers, $row] = csvRowsOf($this->actingAs($this->user)->get(route('publishers.export')));
        $cells = array_combine($headers, $row);

        expect($headers)->toHaveCount(33)
            ->and($headers)->toContain('Company_Registration_Number', 'Client_Account_Number', 'Person_In_Charge_NRIC')
            ->and($cells['Bank_Name'])->toBe('Fake Bank')
            ->and($cells['Notes'])->toBe('Fake note');
    });
});

describe('works', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('works.export'))->assertRedirect(route('login'));
    });

    test('exports the works matching the filters and is not read as a work id', function () {
        $matching = Work::factory()->create(['english_title' => 'Fake Match Song']);
        Work::factory()->create(['english_title' => 'Fake Other Song']);

        $response = $this->actingAs($this->user)->get(route('works.export', ['search' => 'Match']))->assertOk();
        $rows = csvRowsOf($response);

        expect($response->headers->get('Content-Disposition'))->toContain('works-')
            ->and($rows[0])->toContain('TAP_Number', 'Contributors')
            ->and($rows)->toHaveCount(2)
            ->and($rows[1][0])->toBe($matching->tap_number)
            ->and($rows[1][2])->toBe('Fake Match Song');
    });

    test('includes every work column with the ISRCs and contributors', function () {
        $work = Work::factory()->create(['english_title' => 'Fake Song', 'genre' => 'Pop', 'remark' => 'Fake remark']);
        $work->isrcs()->createMany([['isrc' => 'MYABC2600001'], ['isrc' => 'MYABC2600002']]);
        WorkParty::factory()->create(['work_id' => $work->id, 'contributor_name' => 'Fake Writer']);

        [$headers, $row] = csvRowsOf($this->actingAs($this->user)->get(route('works.export')));
        $cells = array_combine($headers, $row);

        expect($headers)->toHaveCount(30)
            ->and($headers)->toContain('COMPASS_Work_Internal_No', 'Conflict_Notes')
            ->and($cells['Genre'])->toBe('Pop')
            ->and($cells['Remark'])->toBe('Fake remark')
            ->and($cells['ISRC'])->toBe('MYABC2600001|MYABC2600002')
            ->and($cells['Contributors'])->toBe('Fake Writer');
    });
});
