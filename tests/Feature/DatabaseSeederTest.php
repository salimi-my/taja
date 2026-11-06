<?php

use App\Actions\Reports\IncompleteSplitReport;
use App\Enums\RightsChangeType;
use App\Models\Publisher;
use App\Models\RightsHistory;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;

test('the seeders build a catalogue with codes, history and incomplete works', function () {
    $this->seed();

    expect(Publisher::where('name', 'Taja Archive')->exists())->toBeTrue()
        ->and(Writer::count())->toBeGreaterThan(10)
        ->and(Work::count())->toBe(27)
        ->and(Work::whereNull('tap_number')->count())->toBe(0)
        ->and(Writer::whereNull('writer_code')->count())->toBe(0)
        ->and(Publisher::whereNull('publisher_code')->count())->toBe(0)
        ->and(Work::has('isrcs')->count())->toBe(27);

    $types = RightsHistory::query()->pluck('change_type')->unique()->all();

    expect($types)->toContain(RightsChangeType::WorkCreated, RightsChangeType::PartyAdded, RightsChangeType::TerritoryChanged, RightsChangeType::Correction, RightsChangeType::PartyRemoved)
        ->and(WorkParty::whereNotNull('superseded_at')->exists())->toBeTrue()
        ->and(WorkParty::whereNotNull('effective_end_date')->exists())->toBeTrue();

    expect(app(IncompleteSplitReport::class)->count())->toBeGreaterThanOrEqual(3)
        ->and(app(IncompleteSplitReport::class)->count())->toBeLessThan(10);
});
