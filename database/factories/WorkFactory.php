<?php

namespace Database\Factories;

use App\Enums\ConflictStatus;
use App\Enums\PartyRole;
use App\Enums\RegistrationStatus;
use App\Models\Publisher;
use App\Models\Work;
use App\Models\WorkIsrc;
use App\Models\WorkParty;
use App\Models\Writer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Work>
 */
class WorkFactory extends Factory
{
    /**
     * Define the model's default state. Every value is fake.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'macp_work_id' => 'MACP-'.fake()->unique()->numerify('########'),
            'english_title' => fake()->unique()->words(3, true),
            'other_title' => null,
            'iswc' => 'T'.fake()->numerify('##########'),
            'work_type' => 'ORG',
            'genre' => fake()->randomElement(['Pop', 'Rock', 'Ballad', 'Traditional']),
            'language' => fake()->randomElement(['Malay', 'English', 'Mandarin']),
            'duration' => sprintf('00:0%d:%02d', fake()->numberBetween(2, 5), fake()->numberBetween(0, 59)),
            'artist_name' => fake()->name(),
            'original_work_title' => null,
            'sub_title_work' => null,
            'society_no' => fake()->numerify('SOC-#####'),
            'administration_start_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'administration_end_date' => null,
            'registration_date' => fake()->dateTimeBetween('-2 years', '-1 week')->format('Y-m-d'),
            'registration_status' => RegistrationStatus::Draft,
            'is_public_domain' => false,
            'song_link' => 'https://example.com/songs/'.fake()->slug(2),
            'remark' => null,
            'release_date' => fake()->date(),
            'compass_work_internal_no' => null,
            'compass_work_type' => null,
            'compass_start_date' => null,
            'wami_work_internal_no' => null,
            'wami_work_type' => null,
            'conflict_status' => ConflictStatus::NoConflict,
            'conflict_notes' => null,
        ];
    }

    /**
     * Give the work the given number of ISRCs.
     */
    public function withIsrcs(int $count = 2): static
    {
        return $this->afterCreating(function (Work $work) use ($count): void {
            for ($index = 0; $index < $count; $index++) {
                WorkIsrc::factory()->create(['work_id' => $work->id]);
            }
        });
    }

    /**
     * Give the work a realistic split: a composer and an author who each own
     * their writer share, and an original publisher. Performance and mechanical
     * shares add up to 100.
     */
    public function withParties(): static
    {
        return $this->afterCreating(function (Work $work): void {
            $composer = Writer::factory()->create();
            $author = Writer::factory()->create();
            $publisher = Publisher::factory()->create();

            $startDate = $work->administration_start_date?->toDateString() ?? now()->toDateString();

            WorkParty::factory()->forWriter($composer)->create([
                'work_id' => $work->id,
                'role' => PartyRole::C,
                'performance_share' => 25,
                'mechanical_share' => 25,
                'effective_start_date' => $startDate,
            ]);
            WorkParty::factory()->forWriter($author)->create([
                'work_id' => $work->id,
                'role' => PartyRole::A,
                'performance_share' => 25,
                'mechanical_share' => 25,
                'effective_start_date' => $startDate,
            ]);
            WorkParty::factory()->forPublisher($publisher)->create([
                'work_id' => $work->id,
                'role' => PartyRole::E,
                'performance_share' => 50,
                'mechanical_share' => 50,
                'effective_start_date' => $startDate,
            ]);
        });
    }

    /**
     * Mark the work as accepted by the society.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_status' => RegistrationStatus::Accepted,
        ]);
    }

    /**
     * Mark the work as a public domain work.
     */
    public function publicDomain(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public_domain' => true,
        ]);
    }
}
