<?php

namespace Database\Factories;

use App\Models\Work;
use App\Models\WorkIsrc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkIsrc>
 */
class WorkIsrcFactory extends Factory
{
    /**
     * Define the model's default state. Every value is fake.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_id' => Work::factory(),
            'isrc' => 'MY'.strtoupper(fake()->bothify('???')).fake()->unique()->numerify('#######'),
        ];
    }
}
