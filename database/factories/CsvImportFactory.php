<?php

namespace Database\Factories;

use App\Enums\CsvImportStatus;
use App\Enums\CsvImportType;
use App\Models\CsvImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CsvImport>
 */
class CsvImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'type' => CsvImportType::Publishers,
            'original_filename' => 'publishers.csv',
            'stored_path' => 'imports/'.fake()->uuid().'.csv',
            'status' => CsvImportStatus::Previewed,
            'progress' => 100,
            'total_rows' => 0,
            'valid_rows' => 0,
            'error_rows' => 0,
            'warnings' => [],
            'errors' => [],
            'imported_count' => 0,
            'imported_at' => null,
        ];
    }

    /**
     * Indicate that the import was confirmed.
     */
    public function imported(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CsvImportStatus::Imported,
            'imported_at' => now(),
        ]);
    }

    /**
     * Indicate that the import was cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CsvImportStatus::Cancelled,
        ]);
    }
}
