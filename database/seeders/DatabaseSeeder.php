<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
    }
}
