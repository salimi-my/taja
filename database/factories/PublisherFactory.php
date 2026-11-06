<?php

namespace Database\Factories;

use App\Enums\MasterStatus;
use App\Enums\RightsType;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publisher>
 */
class PublisherFactory extends Factory
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
            'name' => fake()->unique()->company().' Music',
            'rights_types_granted' => fake()->randomElements(
                array_column(RightsType::cases(), 'value'),
                fake()->numberBetween(1, 3),
            ),
            'ipi_base_number' => 'I-'.fake()->numerify('#########').'-'.fake()->randomDigit(),
            'ipi_name_number' => fake()->unique()->numerify('###########'),
            'company_registration_number' => fake()->unique()->numerify('##########'),
            'registration_address' => fake()->address(),
            'official_email' => fake()->unique()->companyEmail(),
            'incorporation_date' => fake()->date(),
            'society_cmo' => fake()->randomElement(['MACP', 'WAMI', 'Non-Member']),
            'society_number' => fake()->numerify('SOC-#####'),
            'tin_number' => fake()->numerify('TIN#########'),
            'status' => MasterStatus::Active,
            'agreement_start_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'agreement_end_date' => null,
            'auto_renewal' => fake()->boolean(),
            'pre_term' => fake()->boolean(),
            'post_term' => 'During Active Year',
            'territories_granted' => fake()->randomElement([['WW'], ['MY', 'SG'], ['MY', 'SG', 'BN']]),
            'bank_holder_name' => fake()->company(),
            'bank_name' => fake()->company().' Bank',
            'client_account_number' => fake()->numerify('############'),
            'swift_code' => fake()->bothify('????MY??'),
            'bank_address' => fake()->address(),
            'person_in_charge_name' => fake()->name(),
            'person_in_charge_title' => fake()->jobTitle(),
            'person_in_charge_nric' => fake()->numerify('######-##-####'),
            'person_in_charge_mobile_no' => fake()->numerify('+601#-#######'),
            'person_in_charge_email' => fake()->unique()->safeEmail(),
            'taja_percentage_deal' => $tajaDeal,
            'client_percentage_deal' => 100 - $tajaDeal,
            'source_system' => 'Factory',
            'notes' => null,
        ];
    }

    /**
     * Indicate that the publisher is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MasterStatus::Inactive,
        ]);
    }

    /**
     * Indicate that the publisher was granted worldwide territories.
     */
    public function worldwide(): static
    {
        return $this->state(fn (array $attributes) => [
            'territories_granted' => ['WW'],
        ]);
    }

    /**
     * Leave every optional field empty.
     */
    public function withoutOptionalFields(): static
    {
        return $this->state(fn (array $attributes) => [
            'ipi_base_number' => null,
            'ipi_name_number' => null,
            'incorporation_date' => null,
            'society_number' => null,
            'tin_number' => null,
            'agreement_end_date' => null,
            'auto_renewal' => null,
            'pre_term' => null,
            'post_term' => null,
            'bank_holder_name' => null,
            'bank_name' => null,
            'client_account_number' => null,
            'swift_code' => null,
            'bank_address' => null,
            'person_in_charge_name' => null,
            'person_in_charge_title' => null,
            'person_in_charge_nric' => null,
            'person_in_charge_mobile_no' => null,
            'person_in_charge_email' => null,
            'taja_percentage_deal' => null,
            'client_percentage_deal' => null,
            'source_system' => null,
            'notes' => null,
        ]);
    }
}
