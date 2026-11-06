<?php

use App\Actions\Works\CreateWork;
use App\Actions\Works\RecordRightsHistory;
use App\Actions\Works\SummarizeRightsHistory;
use App\Enums\RightsChangeType;
use App\Models\CsvImport;
use App\Models\RightsHistory;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;

describe('the insert-only log', function () {
    test('a stored entry cannot be updated', function () {
        $entry = RightsHistory::factory()->create(['remarks' => 'Original']);

        expect(fn () => $entry->update(['remarks' => 'Changed']))->toThrow(LogicException::class);
        expect($entry->fresh()->remarks)->toBe('Original');
    });

    test('a stored entry cannot be deleted', function () {
        $entry = RightsHistory::factory()->create();

        expect(fn () => $entry->delete())->toThrow(LogicException::class);
        expect(RightsHistory::count())->toBe(1);
    });

    test('entries are kept when the work is soft-deleted', function () {
        $work = Work::factory()->create();
        RightsHistory::factory()->create(['work_id' => $work->id]);

        $work->delete();

        expect(RightsHistory::where('work_id', $work->id)->count())->toBe(1)
            ->and(RightsHistory::first()->work->id)->toBe($work->id);
    });
});

describe('RecordRightsHistory', function () {
    test('stores the entry with its effective date, user and import', function () {
        $work = Work::factory()->create();
        $party = WorkParty::factory()->create(['work_id' => $work->id]);
        $user = User::factory()->create();
        $csvImport = CsvImport::factory()->create(['user_id' => $user->id]);

        $entry = app(RecordRightsHistory::class)(
            $work,
            RightsChangeType::PartyAdded,
            '2026-02-03',
            party: $party,
            new: ['role' => 'C'],
            userId: $user->id,
            csvImportId: $csvImport->id,
            remarks: 'Imported',
        );

        expect($entry->exists)->toBeTrue()
            ->and($entry->effective_date->toDateString())->toBe('2026-02-03')
            ->and($entry->work_party_id)->toBe($party->id)
            ->and($entry->user_id)->toBe($user->id)
            ->and($entry->csv_import_id)->toBe($csvImport->id)
            ->and($entry->recorded_at)->not->toBeNull();
    });

    test('keeps only the attributes that differ between two snapshots', function () {
        $changes = RecordRightsHistory::changedAttributes(
            ['a' => '1', 'b' => ['MY'], 'c' => 'same'],
            ['a' => '2', 'b' => ['MY', 'SG'], 'c' => 'same'],
        );

        expect($changes)->toBe([
            'previous' => ['a' => '1', 'b' => ['MY']],
            'new' => ['a' => '2', 'b' => ['MY', 'SG']],
        ]);
    });
});

describe('CreateWork', function () {
    test('records the import id and the uploader on every entry it writes', function () {
        $writer = Writer::factory()->create();
        $uploader = User::factory()->create();
        $csvImport = CsvImport::factory()->create(['user_id' => $uploader->id]);

        $work = app(CreateWork::class)([
            ...Work::factory()->make()->only((new Work)->getFillable()),
            'registration_status' => 'draft',
            'conflict_status' => 'no_conflict',
            'parties' => [[
                'writer_id' => $writer->id,
                'role' => 'C',
                'performance_share' => 100,
                'mechanical_share' => 100,
                'is_controlled' => true,
                'territories_controlled' => ['WW'],
            ]],
        ], userId: $uploader->id, csvImportId: $csvImport->id);

        $entries = RightsHistory::where('work_id', $work->id)->get();

        expect($entries)->toHaveCount(2)
            ->and($entries->pluck('csv_import_id')->unique()->all())->toBe([$csvImport->id])
            ->and($entries->pluck('user_id')->unique()->all())->toBe([$uploader->id]);
    });
});

describe('SummarizeRightsHistory', function () {
    test('describes each kind of entry in one line', function () {
        $summarize = app(SummarizeRightsHistory::class);
        $work = Work::factory()->create();

        $added = RightsHistory::factory()->create([
            'work_id' => $work->id,
            'change_type' => RightsChangeType::PartyAdded,
            'new_snapshot' => ['contributor_name' => 'Fake Composer', 'role' => 'C', 'performance_share' => '25.00', 'mechanical_share' => '30.50'],
        ]);
        $changed = RightsHistory::factory()->create([
            'work_id' => $work->id,
            'change_type' => RightsChangeType::ShareUpdated,
            'previous_snapshot' => ['contributor_name' => 'Fake Composer', 'performance_share' => '25.00', 'territories_controlled' => ['WW']],
            'new_snapshot' => ['contributor_name' => 'Fake Composer', 'performance_share' => '40.00', 'territories_controlled' => ['MY', 'SG']],
        ]);
        $removed = RightsHistory::factory()->create([
            'work_id' => $work->id,
            'change_type' => RightsChangeType::PartyRemoved,
            'previous_snapshot' => ['contributor_name' => 'Fake Composer', 'role' => 'C'],
            'new_snapshot' => null,
        ]);

        expect($summarize($added))->toBe('Fake Composer added as C (25% performance, 30.5% mechanical).')
            ->and($summarize($changed))->toBe('Fake Composer: performance share from 25% to 40%, territories controlled from WW to MY, SG.')
            ->and($summarize($removed))->toBe('Fake Composer (C) removed.');
    });
});
