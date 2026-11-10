<?php

namespace Database\Seeders;

use App\Actions\Users\RecordUserHistory;
use App\Actions\Users\UpdateUser;
use App\Enums\UserChangeType;
use App\Enums\UserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Seeders\Concerns\GeneratesMalaysianDetails;
use Database\Seeders\Concerns\SpreadsCreationOverTime;
use Illuminate\Database\Seeder;

class UserHistorySeeder extends Seeder
{
    use GeneratesMalaysianDetails, SpreadsCreationOverTime;

    /**
     * Give every seeded user a `Created` entry at the moment it was created
     * (made by John Doe, who made the team), then add a few named changes: a
     * promotion, a new phone number, a new email address, a password change
     * and two-factor authentication, through the same actions the app uses.
     * Run after the `UserSeeder`.
     */
    public function run(RecordUserHistory $recordHistory, UpdateUser $updateUser): void
    {
        $adminId = User::query()->where('email', 'johndoe@example.com')->value('id');

        foreach (User::query()->get() as $user) {
            $this->atMoment($user->created_at, fn () => $recordHistory(
                $user,
                UserChangeType::Created,
                new: RecordUserHistory::withoutEmptyValues(RecordUserHistory::snapshot($user)),
                actorId: $user->id === $adminId ? null : $adminId,
            ));
        }

        $this->promoteToAdmin($updateUser, $adminId);
        $this->changePhoneNumber($updateUser, $adminId);
        $this->changeEmailAddress($updateUser);
        $this->recordSecurityEvents($recordHistory);
    }

    private function promoteToAdmin(UpdateUser $updateUser, ?int $adminId): void
    {
        $user = User::query()->where('name', 'Tan Wei Ming')->first();

        if ($user === null) {
            return;
        }

        $this->atMoment($this->afterCreation($user->created_at, 3), fn () => $updateUser($user, [
            'role' => UserRole::Admin,
        ], $adminId));
    }

    private function changePhoneNumber(UpdateUser $updateUser, ?int $adminId): void
    {
        $user = User::query()->where('name', 'Siti Nadia binti Ahmad')->first();

        if ($user === null) {
            return;
        }

        $this->atMoment($this->afterCreation($user->created_at, 2), fn () => $updateUser($user, [
            'phone' => $this->mobileNumber(),
        ], $adminId));
    }

    /**
     * A user who changes their own email address (it is unverified again).
     */
    private function changeEmailAddress(UpdateUser $updateUser): void
    {
        $user = User::query()->where('name', 'Lim Xin Yi')->first();

        if ($user === null) {
            return;
        }

        $this->atMoment($this->afterCreation($user->created_at, 2), fn () => $updateUser($user, [
            'email' => $this->emailLocalPart($user->name).'.work@example.com',
        ], $user->id));
    }

    private function recordSecurityEvents(RecordUserHistory $recordHistory): void
    {
        $jane = User::query()->where('email', 'janedoe@example.com')->first();
        $john = User::query()->where('email', 'johndoe@example.com')->first();

        if ($jane !== null) {
            $this->atMoment($this->afterCreation($jane->created_at, 1), fn () => $recordHistory(
                $jane,
                UserChangeType::PasswordChanged,
                actorId: $jane->id,
            ));
        }

        if ($john !== null) {
            $this->atMoment($this->afterCreation($john->created_at, 1), fn () => $recordHistory(
                $john,
                UserChangeType::TwoFactorEnabled,
                actorId: $john->id,
            ));
        }
    }

    /**
     * A moment some days after the record was created, never in the future.
     */
    private function afterCreation(?CarbonInterface $createdAt, int $days): CarbonImmutable
    {
        return $this->notAfterNow(($createdAt ?? CarbonImmutable::now())->addDays($days));
    }
}
