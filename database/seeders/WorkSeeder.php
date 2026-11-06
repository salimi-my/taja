<?php

namespace Database\Seeders;

use App\Actions\Works\ChangePartyFromDate;
use App\Actions\Works\CorrectParty;
use App\Actions\Works\CreateWork;
use App\Actions\Works\RemoveParty;
use App\Enums\ConflictStatus;
use App\Enums\RegistrationStatus;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class WorkSeeder extends Seeder
{
    /**
     * The number of works with parties whose shares add up to 100.
     */
    private const COMPLETE_WORKS = 24;

    /**
     * Seed fake works with parties built through the same actions the app
     * uses, so the TAP numbers and the rights history are real. A few works
     * are left incomplete on purpose for the Incomplete Split report. Run
     * after the `PublisherSeeder` and the `WriterSeeder`.
     */
    public function run(CreateWork $createWork, ChangePartyFromDate $changeParty, CorrectParty $correctParty, RemoveParty $removeParty): void
    {
        $writers = Writer::query()->get();
        $publishers = Publisher::query()->get();
        $userId = User::query()->where('role', 'admin')->value('id');

        $complete = collect(range(1, self::COMPLETE_WORKS))->map(fn (int $number): Work => $createWork([
            ...$this->workAttributes($number),
            'parties' => $this->completeParties($writers, $publishers),
        ], $userId));

        $createWork($this->workAttributes(100), $userId);

        $createWork([
            ...$this->workAttributes(101),
            'parties' => [$this->party($writers->random(), 'C', 60)],
        ], $userId);

        $createWork([
            ...$this->workAttributes(102),
            'parties' => [
                $this->party($writers->random(), 'C', 40),
                $this->party($writers->random(), 'A', 30),
            ],
        ], $userId);

        $this->addHistory($complete, $changeParty, $correctParty, $removeParty, $userId);
    }

    /**
     * Territory changes, corrections and one removal, so the history and the
     * ended involvement have something to show.
     *
     * @param  Collection<int, Work>  $works
     */
    private function addHistory(Collection $works, ChangePartyFromDate $changeParty, CorrectParty $correctParty, RemoveParty $removeParty, ?int $userId): void
    {
        $works->take(5)->each(function (Work $work) use ($changeParty, $userId): void {
            $changeParty($this->firstParty($work), [
                'territories_controlled' => ['MY', 'SG'],
                'effective_date' => now()->subWeek()->toDateString(),
                'remarks' => 'Fake sample: territories narrowed after a renegotiation.',
            ], $userId);
        });

        $works->slice(5, 4)->each(function (Work $work) use ($correctParty, $userId): void {
            $correctParty($this->firstParty($work), [
                'territories_controlled' => ['MY'],
                'remarks' => 'Fake sample: territory entered wrongly.',
            ], $userId);
        });

        $works->slice(9, 1)->each(function (Work $work) use ($removeParty, $userId): void {
            $removeParty($this->firstParty($work), [
                'effective_date' => now()->subDays(3)->toDateString(),
                'remarks' => 'Fake sample: writer withdrew from the work.',
            ], $userId);
        });
    }

    private function firstParty(Work $work): WorkParty
    {
        return $work->parties()->whereNull('superseded_at')->whereNull('effective_end_date')->firstOrFail();
    }

    /**
     * @return array<array-key, mixed> The factory's raw attributes plus the ISRCs.
     */
    private function workAttributes(int $number): array
    {
        $attributes = Work::factory()->raw([
            'registration_status' => fake()->randomElement(RegistrationStatus::cases()),
            'conflict_status' => $number % 9 === 0 ? ConflictStatus::ShareConflict : ConflictStatus::NoConflict,
        ]);

        $attributes['isrcs'] = [
            'MY'.strtoupper(fake()->unique()->bothify('?##')).fake()->numerify('#######'),
        ];

        return $attributes;
    }

    /**
     * One to three writers sharing half of the work and a publisher holding
     * the other half, so every total is 100.
     *
     * @param  Collection<int, Writer>  $writers
     * @param  Collection<int, Publisher>  $publishers
     * @return list<array<string, mixed>>
     */
    private function completeParties(Collection $writers, Collection $publishers): array
    {
        $chosen = $writers->random(fake()->numberBetween(1, 3));
        $each = round(50 / $chosen->count(), 2);
        $shares = $chosen->map(fn (): float => $each)->all();
        $shares[0] = round(50 - $each * ($chosen->count() - 1), 2);

        $writerParties = $chosen->values()->map(
            fn (Writer $writer, int $index): array => $this->party($writer, 'C', $shares[$index]),
        )->all();

        return [
            ...$writerParties,
            [
                'publisher_id' => $publishers->random()->id,
                'role' => 'E',
                'performance_share' => 50,
                'mechanical_share' => 50,
                'is_controlled' => true,
                'territories_controlled' => ['WW'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function party(Writer $writer, string $role, int|float $share): array
    {
        return [
            'writer_id' => $writer->id,
            'role' => $role,
            'performance_share' => $share,
            'mechanical_share' => $share,
            'is_controlled' => true,
            'territories_controlled' => ['WW'],
        ];
    }
}
