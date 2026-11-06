<?php

namespace Database\Factories;

use App\Enums\RightsChangeType;
use App\Models\RightsHistory;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RightsHistory>
 */
class RightsHistoryFactory extends Factory
{
    /**
     * Define the model's default state: a work-created entry with fake data.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_id' => Work::factory(),
            'work_party_id' => null,
            'change_type' => RightsChangeType::WorkCreated,
            'previous_snapshot' => null,
            'new_snapshot' => ['english_title' => fake()->sentence(3)],
            'effective_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'recorded_at' => now(),
            'user_id' => null,
            'csv_import_id' => null,
            'remarks' => null,
        ];
    }
}
