<?php

use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->writer = Writer::factory()->create(['name' => 'Fake Writer']);
});

describe('screen', function () {
    test('guests are redirected to the login page', function () {
        $this->get(route('reports.writer-catalogue'))->assertRedirect(route('login'));
        $this->get(route('reports.export.writer-catalogue'))->assertRedirect(route('login'));
    });

    test('regular users can view the report', function () {
        $this->actingAs($this->user)->get(route('reports.writer-catalogue'))->assertOk();
    });

    test('without a writer there are no rows', function () {
        WorkParty::factory()->create();

        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/writer-catalogue')
                ->where('writer', null)
                ->has('rows.data', 0)
                ->has('writers', 2)
            );
    });

    test('lists the current works of the chosen writer with the work details', function () {
        $work = Work::factory()->create(['english_title' => 'Fake Song']);
        WorkParty::factory()->forWriter($this->writer)->create(['work_id' => $work->id, 'performance_share' => 40]);
        WorkParty::factory()->create();

        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['writer_id' => $this->writer->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('writer.code', $this->writer->writer_code)
                ->has('rows.data', 1)
                ->where('rows.data.0.work.tap_number', $work->tap_number)
                ->where('rows.data.0.work.title', 'Fake Song')
                ->where('rows.data.0.performance_share', '40.00')
            );
    });

    test('ended involvement and superseded rows are hidden unless asked for', function () {
        WorkParty::factory()->forWriter($this->writer)->create();
        WorkParty::factory()->forWriter($this->writer)->ended('2020-01-31')->create();
        WorkParty::factory()->forWriter($this->writer)->superseded()->create();

        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['writer_id' => $this->writer->id]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1));

        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['writer_id' => $this->writer->id, 'include_ended' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 2));
    });

    test('rejects an unknown writer', function () {
        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['writer_id' => 999999]))
            ->assertSessionHasErrors('writer_id');
    });
});

describe('filters', function () {
    test('narrows the catalogue by role', function () {
        $composer = WorkParty::factory()->forWriter($this->writer)->create(['role' => 'C']);
        WorkParty::factory()->forWriter($this->writer)->create(['role' => 'A']);

        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['writer_id' => $this->writer->id, 'roles' => ['C']]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $composer->id));
    });

    test('narrows the catalogue by the status of the work', function () {
        $draft = Work::factory()->create(['registration_status' => 'draft', 'conflict_status' => 'no_conflict']);
        $conflict = Work::factory()->create(['registration_status' => 'submitted', 'conflict_status' => 'share_conflict']);
        $draftParty = WorkParty::factory()->forWriter($this->writer)->create(['work_id' => $draft->id]);
        $conflictParty = WorkParty::factory()->forWriter($this->writer)->create(['work_id' => $conflict->id]);

        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['writer_id' => $this->writer->id, 'registration_statuses' => ['draft']]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $draftParty->id));

        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['writer_id' => $this->writer->id, 'conflict_statuses' => ['share_conflict']]))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $conflictParty->id));
    });

    test('the search matches the title of the work', function () {
        $match = WorkParty::factory()->forWriter($this->writer)->create([
            'work_id' => Work::factory()->create(['english_title' => 'Alpha Fake'])->id,
        ]);
        WorkParty::factory()->forWriter($this->writer)->create([
            'work_id' => Work::factory()->create(['english_title' => 'Beta Fake'])->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['writer_id' => $this->writer->id, 'search' => 'Alpha']))
            ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1)->where('rows.data.0.id', $match->id));
    });

    test('rejects a publisher role or an unknown status', function () {
        $this->actingAs($this->user)
            ->get(route('reports.writer-catalogue', ['roles' => ['E'], 'conflict_statuses' => ['nope']]))
            ->assertSessionHasErrors(['roles.0', 'conflict_statuses.0']);
    });

    test('the export uses the same filters', function () {
        WorkParty::factory()->forWriter($this->writer)->create(['role' => 'C']);
        WorkParty::factory()->forWriter($this->writer)->create(['role' => 'A']);

        $content = $this->actingAs($this->user)
            ->get(route('reports.export.writer-catalogue', ['writer_id' => $this->writer->id, 'roles' => ['C']]))
            ->streamedContent();

        expect(array_filter(explode("\n", $content)))->toHaveCount(2);
    });
});

describe('export', function () {
    test('downloads the catalogue of the writer as csv', function () {
        $work = Work::factory()->create(['english_title' => 'Fake Song']);
        WorkParty::factory()->forWriter($this->writer)->create(['work_id' => $work->id, 'territories_controlled' => ['MY', 'SG']]);
        WorkParty::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('reports.export.writer-catalogue', ['writer_id' => $this->writer->id]))
            ->assertOk()
            ->assertDownload();

        $lines = array_values(array_filter(explode("\n", $response->streamedContent())));

        expect($lines)->toHaveCount(2)
            ->and($lines[0])->toContain('TAP_Number')
            ->and($lines[1])->toContain($work->tap_number)->toContain('MY|SG')->toContain('Fake Writer');
    });

    test('the export without a writer has only the header', function () {
        $response = $this->actingAs($this->user)->get(route('reports.export.writer-catalogue'));

        expect(array_filter(explode("\n", $response->streamedContent())))->toHaveCount(1);
    });
});
