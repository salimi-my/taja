<?php

namespace Database\Seeders;

use App\Models\Publisher;
use App\Models\Writer;
use Illuminate\Database\Seeder;

class WriterSeeder extends Seeder
{
    /**
     * Seed fake writers, some with pseudonyms, some linked to a publisher and a few
     * with a birthday coming up for the dashboard reminder.
     * Run after the `PublisherSeeder`.
     */
    public function run(): void
    {
        $publishers = Publisher::query()->get();

        Writer::factory()->count(12)->withAliases(2)->create();

        Writer::factory()->count(6)->create();

        Writer::factory()->count(4)->create()->each(
            fn (Writer $writer) => $writer->update(['publisher_id' => $publishers->random()->id]),
        );

        Writer::factory()->inactive()->create();

        foreach ([0, 0, 1, 3] as $daysAhead) {
            Writer::factory()->create([
                'date_of_birth' => now()->addDays($daysAhead)->subYears(fake()->numberBetween(25, 70))->toDateString(),
            ]);
        }
    }
}
