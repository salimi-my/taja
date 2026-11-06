<?php

namespace Database\Factories;

use App\Models\Writer;
use App\Models\WriterAlias;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WriterAlias>
 */
class WriterAliasFactory extends Factory
{
    /**
     * Define the model's default state. Every value is fake.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'writer_id' => Writer::factory(),
            'name' => fake()->unique()->userName(),
            'ipi_base_number' => fake()->numerify('###########'),
            'position' => 1,
        ];
    }
}
