<?php

namespace Database\Seeders;

use App\Models\Publisher;
use App\Models\Writer;
use Illuminate\Database\Seeder;

class WriterSeeder extends Seeder
{
    /**
     * Seed fake writers, some with pseudonyms and some linked to a publisher.
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
    }
}
