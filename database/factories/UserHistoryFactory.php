<?php

namespace Database\Factories;

use App\Enums\UserChangeType;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserHistory>
 */
class UserHistoryFactory extends Factory
{
    /**
     * Define the model's default state: a created entry for a new user.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => User::factory(),
            'subject_name' => fake()->name(),
            'subject_email' => fake()->unique()->safeEmail(),
            'change_type' => UserChangeType::Created,
            'previous_snapshot' => null,
            'new_snapshot' => ['name' => fake()->name()],
            'recorded_at' => now(),
            'actor_id' => null,
            'actor_name' => null,
            'remarks' => null,
        ];
    }

    /**
     * An entry for the given user.
     */
    public function forSubject(User $subject): static
    {
        return $this->state(fn (): array => [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'subject_email' => $subject->email,
        ]);
    }

    /**
     * An entry made by the given user.
     */
    public function byActor(User $actor): static
    {
        return $this->state(fn (): array => [
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
        ]);
    }
}
