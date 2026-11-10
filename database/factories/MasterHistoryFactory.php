<?php

namespace Database\Factories;

use App\Enums\MasterChangeType;
use App\Models\MasterHistory;
use App\Models\Publisher;
use App\Models\Writer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterHistory>
 */
class MasterHistoryFactory extends Factory
{
    /**
     * Define the model's default state: a created entry for a new writer.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => 'writer',
            'subject_id' => Writer::factory(),
            'subject_code' => null,
            'subject_name' => fake()->name(),
            'change_type' => MasterChangeType::Created,
            'previous_snapshot' => null,
            'new_snapshot' => ['name' => fake()->name()],
            'recorded_at' => now(),
            'user_id' => null,
            'csv_import_id' => null,
            'remarks' => null,
        ];
    }

    /**
     * An entry for the given writer or publisher.
     */
    public function forSubject(Writer|Publisher $subject): static
    {
        return $this->state(fn (): array => [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'subject_code' => $subject instanceof Writer ? $subject->writer_code : $subject->publisher_code,
            'subject_name' => $subject->name,
        ]);
    }
}
