<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CountrySeeder::class);

        User::factory()->admin()->create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'P@$$w0rd',
        ]);

        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'password' => 'P@$$w0rd',
        ]);

        User::factory(48)->create();

        $this->call([
            PublisherSeeder::class,
            WriterSeeder::class,
            WorkSeeder::class,
        ]);
    }
}
