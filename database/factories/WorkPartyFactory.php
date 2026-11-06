<?php

namespace Database\Factories;

use App\Enums\PartyRole;
use App\Models\Publisher;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkParty>
 */
class WorkPartyFactory extends Factory
{
    /**
     * Define the model's default state: a composer who owns the whole work.
     * Every value is fake.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_id' => Work::factory(),
            'writer_id' => Writer::factory(),
            'publisher_id' => null,
            'role' => PartyRole::C,
            'contributor_name' => fake()->name(),
            'performance_share' => 100,
            'mechanical_share' => 100,
            'sync_share' => null,
            'lyrics_share' => null,
            'is_controlled' => true,
            'territories_controlled' => ['WW'],
            'territory_exclusions' => null,
            'effective_start_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'effective_end_date' => null,
            'superseded_at' => null,
        ];
    }

    /**
     * Make the party a writer (a new one unless one is given).
     */
    public function forWriter(?Writer $writer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'writer_id' => $writer instanceof Writer ? $writer->id : Writer::factory(),
            'publisher_id' => null,
            'role' => PartyRole::C,
            'contributor_name' => $writer instanceof Writer ? $writer->name : fake()->name(),
        ]);
    }

    /**
     * Make the party an original publisher (a new one unless one is given).
     */
    public function forPublisher(?Publisher $publisher = null): static
    {
        return $this->state(fn (array $attributes) => [
            'writer_id' => null,
            'publisher_id' => $publisher instanceof Publisher ? $publisher->id : Publisher::factory(),
            'role' => PartyRole::E,
            'contributor_name' => $publisher instanceof Publisher ? $publisher->name : fake()->company(),
        ]);
    }

    /**
     * Mark the row as ended on the given date.
     */
    public function ended(string $date = '2026-01-31'): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_end_date' => $date,
        ]);
    }

    /**
     * Mark the row as superseded by a correction.
     */
    public function superseded(): static
    {
        return $this->state(fn (array $attributes) => [
            'superseded_at' => now(),
        ]);
    }
}
