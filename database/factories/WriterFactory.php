<?php

namespace Database\Factories;

use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Enums\RightsType;
use App\Models\Publisher;
use App\Models\Writer;
use App\Models\WriterAlias;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Writer>
 */
class WriterFactory extends Factory
{
    /**
     * Define the model's default state. Every value is fake.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tajaDeal = fake()->randomElement([50, 60, 70, 80]);

        return [
            'name' => fake()->unique()->name(),
            'default_role' => fake()->randomElement([PartyRole::C, PartyRole::A, PartyRole::CA, PartyRole::AR]),
            'ipi_base_number' => 'I-'.fake()->numerify('#########').'-'.fake()->randomDigit(),
            'ipi_name_number' => fake()->unique()->numerify('###########'),
            'nric' => fake()->numerify('############'),
            'date_of_birth' => fake()->date('Y-m-d', '-20 years'),
            'date_of_death' => null,
            'nationality' => 'Malaysian',
            'cmo_society' => fake()->randomElement(['MACP', 'WAMI', 'Non-Member']),
            'publisher_id' => null,
            'controlled_territories' => fake()->randomElement([['WW'], ['MY', 'SG'], ['MY', 'SG', 'BN']]),
            'rights_types_controlled' => fake()->randomElements(
                array_column(RightsType::cases(), 'value'),
                fake()->numberBetween(1, 3),
            ),
            'tin_number' => fake()->numerify('TIN#########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+601#########'),
            'address' => fake()->address(),
            'status' => MasterStatus::Active,
            'agreement_start_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'agreement_end_date' => null,
            'auto_renewal' => fake()->boolean(),
            'pre_term' => fake()->boolean(),
            'post_term' => 'During Active Year',
            'taja_percentage_deal' => $tajaDeal,
            'writer_percentage_deal' => 100 - $tajaDeal,
            'bank_name' => fake()->company().' Bank',
            'bank_account_number' => fake()->numerify('############'),
            'swift_code' => fake()->bothify('????MY??'),
            'bank_address' => fake()->address(),
            'heir_name' => fake()->name(),
            'heir_nric' => fake()->numerify('############'),
            'heir_relationship' => 'Spouse',
            'heir_mobile_number' => fake()->numerify('+601#########'),
            'heir_email' => fake()->unique()->safeEmail(),
            'source_system' => 'Factory',
            'notes' => null,
        ];
    }

    /**
     * Indicate that the writer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MasterStatus::Inactive,
        ]);
    }

    /**
     * Link the writer to a publisher (a new one unless one is given).
     */
    public function withPublisher(?Publisher $publisher = null): static
    {
        return $this->state(fn (array $attributes) => [
            'publisher_id' => $publisher instanceof Publisher ? $publisher->id : Publisher::factory(),
        ]);
    }

    /**
     * Give the writer the given number of pseudonyms, in positions 1 to 4.
     */
    public function withAliases(int $count = 2): static
    {
        return $this->afterCreating(function (Writer $writer) use ($count): void {
            foreach (range(1, min($count, 4)) as $position) {
                WriterAlias::factory()->create([
                    'writer_id' => $writer->id,
                    'position' => $position,
                ]);
            }
        });
    }

    /**
     * Leave every optional field empty.
     */
    public function withoutOptionalFields(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_role' => null,
            'ipi_base_number' => null,
            'ipi_name_number' => null,
            'nric' => null,
            'date_of_birth' => null,
            'date_of_death' => null,
            'nationality' => null,
            'publisher_id' => null,
            'controlled_territories' => null,
            'rights_types_controlled' => null,
            'tin_number' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'agreement_end_date' => null,
            'auto_renewal' => null,
            'pre_term' => null,
            'post_term' => null,
            'taja_percentage_deal' => null,
            'writer_percentage_deal' => null,
            'bank_name' => null,
            'bank_account_number' => null,
            'swift_code' => null,
            'bank_address' => null,
            'heir_name' => null,
            'heir_nric' => null,
            'heir_relationship' => null,
            'heir_mobile_number' => null,
            'heir_email' => null,
            'source_system' => null,
            'notes' => null,
        ]);
    }
}
