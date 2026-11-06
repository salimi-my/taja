<?php

namespace Database\Seeders;

use App\Models\Publisher;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    /**
     * Seed fake publishers, including the "Taja Archive" publisher that holds
     * the rights of works without an outside publisher.
     */
    public function run(): void
    {
        Publisher::factory()->worldwide()->create([
            'name' => 'Taja Archive',
            'society_cmo' => 'MACP',
            'taja_percentage_deal' => 100,
            'client_percentage_deal' => 0,
            'notes' => 'Fake sample data: holds works without an outside publisher.',
        ]);

        Publisher::factory()->count(5)->create();
        Publisher::factory()->inactive()->create();
    }
}
