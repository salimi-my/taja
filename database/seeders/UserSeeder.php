<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Models\User;
use Database\Seeders\Concerns\GeneratesMalaysianDetails;
use Database\Seeders\Concerns\SpreadsCreationOverTime;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use GeneratesMalaysianDetails, SpreadsCreationOverTime;

    /**
     * The two accounts created before the team (John and Jane Doe).
     */
    private const KNOWN_ACCOUNTS = 2;

    /**
     * Seed the two known sign-in accounts and a team of regular users, created
     * at random moments over the past month. Every name is fictional, and the
     * gender, email and phone number match it. A few users have no phone or
     * gender, so the empty state of the users table shows.
     */
    public function run(): void
    {
        $team = $this->team();
        $total = count($team) + self::KNOWN_ACCOUNTS;
        $moments = $this->spreadMoments($total);

        $this->atMoment($moments[0], fn () => User::factory()->admin()->create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'phone' => '+60123456789',
            'gender' => Gender::Male,
            'password' => 'P@$$w0rd',
        ]));

        $this->atMoment($moments[1], fn () => User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'phone' => '+60177654321',
            'gender' => Gender::Female,
            'password' => 'P@$$w0rd',
        ]));

        foreach ($team as $index => [$name, $gender]) {
            $this->atMoment($moments[$index + self::KNOWN_ACCOUNTS], function () use ($index, $name, $gender): void {
                $user = User::factory()->create([
                    'name' => $name,
                    'email' => $this->emailLocalPart($name).'@example.com',
                    'phone' => $index % 17 === 16 ? null : $this->mobileNumber(),
                    'gender' => $index % 23 === 22 ? null : $gender,
                ]);

                if ($index % 12 === 11) {
                    $user->forceFill(['email_verified_at' => null])->save();
                }
            });
        }
    }

    /**
     * The regular users: Malay, Chinese and Indian Malaysian names.
     *
     * @return list<array{0: string, 1: Gender}>
     */
    private function team(): array
    {
        $men = [
            'Muhammad Aiman bin Roslan', 'Ahmad Faris bin Zulkifli', 'Mohd Hafiz bin Abdullah',
            'Khairul Anuar bin Ismail', 'Amirul Hakim bin Yusof', 'Tan Wei Ming', 'Lim Jun Hao',
            'Lee Kok Wai', 'Wong Chee Keong', 'Ng Zhi Xian', 'Chong Yi Heng',
            'Rajesh Kumar a/l Subramaniam', 'Suresh Pillai a/l Muniandy', 'Arvind Nair a/l Krishnan',
            'Daniel Tan Jia Wen', 'Benjamin Lau Kah Seng', 'Syafiq Danial bin Mansor',
            'Zulhilmi bin Hashim', 'Harith Iskandar bin Rahim', 'Ivan Teo Boon Hwa',
            'Kevin Raj a/l Selvam', 'Nik Aizat bin Nik Mahmood',
        ];

        $women = [
            'Nur Aisyah binti Karim', 'Siti Nadia binti Ahmad', 'Nurul Izzati binti Samad',
            'Farah Aina binti Rosli', 'Aina Sofea binti Jamal', 'Fatin Najwa binti Kamal',
            'Liyana Syuhada binti Omar', 'Puteri Balqis binti Zainal', 'Tan Mei Ling', 'Lim Xin Yi',
            'Lee Shu Fen', 'Wong Pei Shan', 'Chong Hui Min', 'Ng Sook Yee',
            'Priya Devi a/p Ramasamy', 'Kavitha a/p Rajendran', 'Deepa Lakshmi a/p Gopal',
            'Sharmila a/p Chandran', 'Chloe Ong Jia Hui', 'Amanda Teh Li Ying',
            'Hannah Yap Wen Qi', 'Dayana binti Mahadi', 'Sarah Imani binti Azman',
        ];

        $others = ['Alex Chen Wei Jie', 'Jamie Lee Xuan Ying', 'Sam Iskandar'];

        $team = [
            ...array_map(fn (string $name): array => [$name, Gender::Male], $men),
            ...array_map(fn (string $name): array => [$name, Gender::Female], $women),
            ...array_map(fn (string $name): array => [$name, Gender::Other], $others),
        ];

        shuffle($team);

        return $team;
    }
}
