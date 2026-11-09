<?php

namespace Database\Seeders;

use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Models\Publisher;
use App\Models\Writer;
use App\Models\WriterAlias;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\GeneratesMalaysianDetails;
use Database\Seeders\Concerns\SpreadsCreationOverTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class WriterSeeder extends Seeder
{
    use GeneratesMalaysianDetails, SpreadsCreationOverTime;

    /**
     * Home addresses to cycle through for the Malaysian writers.
     *
     * @var list<string>
     */
    private const ADDRESSES = [
        'No. 14, Jalan Bukit Indah 3/2, Taman Bukit Indah, 81200 Johor Bahru, Johor',
        'A-12-5, Residensi Desa Kiara, Jalan Desa Kiara, 50480 Kuala Lumpur',
        'No. 7, Lorong Kenanga 2, Taman Kenanga, 43000 Kajang, Selangor',
        '22, Jalan Burma, Pulau Tikus, 10350 George Town, Pulau Pinang',
        'No. 31, Jalan Setia Prima 3/4, Setia Alam, 40170 Shah Alam, Selangor',
        'B-8-12, Pangsapuri Seri Cempaka, Jalan Ipoh Lama, 51200 Kuala Lumpur',
        'No. 3, Lorong Maarof, Bangsar, 59000 Kuala Lumpur',
        'No. 18, Jalan PJU 5/12, Kota Damansara, 47810 Petaling Jaya, Selangor',
        'Lot 56, Taman Kingfisher, Jalan Tuaran, 88450 Kota Kinabalu, Sabah',
        'No. 9, Jalan Tun Abdul Razak, Susur 4, 80000 Johor Bahru, Johor',
        'No. 41, Jalan Seri Kuantan 5, Taman Seri Kuantan, 25250 Kuantan, Pahang',
        'No. 6, Lorong Pasir Puteh 11, 31650 Ipoh, Perak',
    ];

    /**
     * Rights and territories to cycle through for writers who do not set them.
     *
     * @var list<array{rights: list<string>, territories: list<string>}>
     */
    private const RIGHTS_PROFILES = [
        ['rights' => ['performance', 'mechanical'], 'territories' => ['WW']],
        ['rights' => ['performance', 'mechanical', 'digital'], 'territories' => ['MY', 'SG', 'BN']],
        ['rights' => ['performance', 'mechanical', 'sync', 'lyrics', 'digital'], 'territories' => ['WW']],
        ['rights' => ['performance'], 'territories' => ['MY']],
    ];

    /**
     * Seed fictional writers, some with stage names, some linked to a publisher
     * and a few with a birthday coming up for the dashboard reminder. The
     * NRIC matches the date of birth, the email matches the name and the heir
     * has a plausible age and relationship. Run after the `PublisherSeeder`.
     */
    public function run(): void
    {
        $publishers = Publisher::query()->pluck('id', 'name');
        $writers = $this->writers();
        $moments = $this->spreadMoments(count($writers));

        foreach ($writers as $index => $row) {
            if (isset($row['birthday_in'])) {
                $row['dob'] = now()->addDays($row['birthday_in'][0])->subYears($row['birthday_in'][1])->toDateString();
            }

            $this->atMoment(
                $moments[$index],
                fn (): Writer => $this->createWriter($row, $index, $publishers),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, int>  $publishers  Publisher ids keyed by name.
     */
    private function createWriter(array $row, int $index, Collection $publishers): Writer
    {
        $nationality = $row['nationality'] ?? 'Malaysian';
        $dateOfBirth = CarbonImmutable::parse($row['dob']);
        $bank = $this->bankAt($index);
        $profile = self::RIGHTS_PROFILES[$index % count(self::RIGHTS_PROFILES)];
        $tajaDeal = [60, 70, 50, 80, 65][$index % 5];
        $agreementStart = CarbonImmutable::parse('2022-01-03')->addDays($index * 53);
        $isMalaysian = $nationality === 'Malaysian';
        $heir = isset($row['heir']) ? $this->heirDetails($row['heir'], $dateOfBirth) : [];

        $writer = Writer::factory()->create([
            'name' => $row['name'],
            'default_role' => $row['role'],
            'ipi_base_number' => $this->ipiBaseNumber(),
            'ipi_name_number' => $this->ipiNameNumber(),
            'nric' => $isMalaysian ? $this->nricFor($dateOfBirth, $row['male']) : $this->foreignIdentityNumber($nationality),
            'date_of_birth' => $dateOfBirth->toDateString(),
            'date_of_death' => $row['died'] ?? null,
            'nationality' => $nationality,
            'cmo_society' => $row['society'],
            'publisher_id' => isset($row['publisher']) ? $publishers[$row['publisher']] : null,
            'controlled_territories' => $row['territories'] ?? $profile['territories'],
            'rights_types_controlled' => $row['rights'] ?? $profile['rights'],
            'tin_number' => $isMalaysian ? 'IG'.fake()->numerify('###########') : null,
            'email' => $this->emailLocalPart($row['name']).'@'.['example.com', 'example.net', 'example.org'][$index % 3],
            'phone' => $this->phoneFor($nationality),
            'address' => $row['address'] ?? self::ADDRESSES[$index % count(self::ADDRESSES)],
            'status' => $row['status'] ?? MasterStatus::Active,
            'agreement_start_date' => ($row['agreement_start'] ?? $agreementStart->toDateString()),
            'agreement_end_date' => $row['agreement_end'] ?? null,
            'auto_renewal' => $index % 2 === 0,
            'pre_term' => $index % 3 === 0,
            'post_term' => $index % 4 === 0 ? '12 months after the end date' : 'During Active Year',
            'taja_percentage_deal' => $tajaDeal,
            'writer_percentage_deal' => 100 - $tajaDeal,
            'bank_name' => $bank['name'],
            'bank_account_number' => $this->accountNumberAt($bank),
            'swift_code' => $bank['swift'],
            'bank_address' => $bank['address'],
            'heir_name' => $heir['name'] ?? null,
            'heir_nric' => $heir['nric'] ?? null,
            'heir_relationship' => $heir['relationship'] ?? null,
            'heir_mobile_number' => $heir['mobile'] ?? null,
            'heir_email' => $heir['email'] ?? null,
            'source_system' => 'Seeder',
            'notes' => $row['notes'] ?? null,
        ]);

        foreach ($row['aliases'] ?? [] as $position => $alias) {
            WriterAlias::factory()->create([
                'writer_id' => $writer->id,
                'name' => $alias,
                'ipi_base_number' => fake()->numerify('00#########'),
                'position' => $position + 1,
            ]);
        }

        return $writer;
    }

    /**
     * The heir's contact details, with an age that fits the relationship.
     *
     * @param  array{0: string, 1: string, 2: bool}  $heir  Name, relationship and whether the heir is a man.
     * @return array{name: string, relationship: string, nric: string, mobile: string, email: string}
     */
    private function heirDetails(array $heir, CarbonImmutable $writerDateOfBirth): array
    {
        [$name, $relationship, $isMale] = $heir;

        $yearsOlder = match ($relationship) {
            'Child' => fake()->numberBetween(-30, -24),
            'Parent' => fake()->numberBetween(24, 30),
            default => fake()->numberBetween(-4, 4),
        };

        return [
            'name' => $name,
            'relationship' => $relationship,
            'nric' => $this->nricFor($writerDateOfBirth->subYears($yearsOlder), $isMale),
            'mobile' => $this->mobileNumber(),
            'email' => $this->emailLocalPart($name).'@example.net',
        ];
    }

    /**
     * A mobile number that fits the writer's country.
     */
    private function phoneFor(string $nationality): string
    {
        return match ($nationality) {
            'Singaporean' => '+659'.fake()->numerify('#######'),
            'Indonesian' => '+62812'.fake()->numerify('########'),
            'Bruneian' => '+673'.fake()->randomElement(['7', '8']).fake()->numerify('######'),
            default => $this->mobileNumber(),
        };
    }

    /**
     * An identity or passport number for a writer who is not Malaysian.
     */
    private function foreignIdentityNumber(string $nationality): string
    {
        return match ($nationality) {
            'Singaporean' => 'S'.fake()->numerify('#######').fake()->randomElement(['A', 'D', 'G', 'J']),
            'Bruneian' => fake()->numerify('00-######'),
            default => 'C'.fake()->numerify('#######'),
        };
    }

    /**
     * The curated writers. `birthday_in` is `[days from today, age turned]`.
     *
     * @return list<array<string, mixed>>
     */
    private function writers(): array
    {
        return [
            [
                'name' => 'Hafiz Zainuddin bin Samad', 'male' => true, 'role' => PartyRole::CA, 'dob' => '1986-04-12',
                'society' => 'MACP', 'aliases' => ['HZ Beats'],
                'heir' => ['Nurul Huda binti Hamzah', 'Spouse', false],
            ],
            [
                'name' => 'Nurul Ain binti Razak', 'male' => false, 'role' => PartyRole::A, 'dob' => '1991-09-03',
                'society' => 'MACP', 'aliases' => ['Ain Razak'],
                'heir' => ['Razak bin Daud', 'Parent', true],
            ],
            [
                'name' => 'Adam Firdaus bin Kamarul', 'male' => true, 'role' => PartyRole::C, 'dob' => '1983-01-27',
                'society' => 'MACP', 'publisher' => 'Seri Nada Publishing Sdn Bhd',
                'heir' => ['Liyana binti Ramli', 'Spouse', false],
            ],
            [
                'name' => 'Sharifah Maryam binti Syed Hamzah', 'male' => false, 'role' => PartyRole::CA, 'dob' => '1979-06-18',
                'society' => 'MACP', 'aliases' => ['Maryam Hamzah', 'Mary H'],
                'heir' => ['Syed Haikal bin Syed Zakwan', 'Child', true],
            ],
            [
                'name' => 'Tan Wei Liang', 'male' => true, 'role' => PartyRole::C, 'dob' => '1988-11-05',
                'society' => 'MACP', 'publisher' => 'Kopitiam Sound Publishing Sdn Bhd',
                'aliases' => ['Weiliang T', '陈伟良'],
                'territories' => ['MY', 'SG'], 'rights' => ['performance', 'mechanical', 'lyrics'],
                'heir' => ['Lim Mei Chin', 'Spouse', false],
            ],
            [
                'name' => 'Lim Hui Shan', 'male' => false, 'role' => PartyRole::A, 'dob' => '1992-02-14',
                'society' => 'MACP', 'publisher' => 'Kopitiam Sound Publishing Sdn Bhd', 'aliases' => ['Shan L'],
                'territories' => ['MY', 'SG'], 'rights' => ['performance', 'mechanical', 'lyrics'],
            ],
            [
                'name' => 'Chong Kah Yan', 'male' => false, 'role' => PartyRole::CA, 'dob' => '1985-07-21',
                'society' => 'MACP', 'aliases' => ['KY Chong'],
                'heir' => ['Chong Kah Meng', 'Sibling', true],
            ],
            [
                'name' => 'Ravindran a/l Maniam', 'male' => true, 'role' => PartyRole::C, 'dob' => '1975-12-09',
                'society' => 'WAMI', 'publisher' => 'Harmoni Timur Entertainment Sdn Bhd', 'aliases' => ['Ravi Maniam'],
                'heir' => ['Shanti a/p Ravindran', 'Child', false],
            ],
            [
                'name' => 'Kalaivani a/p Subramaniam', 'male' => false, 'role' => PartyRole::A, 'dob' => '1982-03-30',
                'society' => 'WAMI', 'publisher' => 'Harmoni Timur Entertainment Sdn Bhd', 'aliases' => ['Kalai V'],
            ],
            [
                'name' => 'Iskandar Dzulkarnain bin Azhar', 'male' => true, 'role' => PartyRole::AR, 'dob' => '1990-08-16',
                'society' => 'MACP', 'aliases' => ['Is Dzul', 'DJ Iskandar'],
                'heir' => ['Azhar bin Mahmud', 'Parent', true],
            ],
            [
                'name' => 'Faridah binti Yaacob', 'male' => false, 'role' => PartyRole::A, 'dob' => '1968-05-11',
                'society' => 'MACP', 'publisher' => 'Orkid Lagu Publishing Sdn Bhd',
                'heir' => ['Nurul Syafiqah binti Hashim', 'Child', false],
            ],
            [
                'name' => 'Zul Hakimi bin Othman', 'male' => true, 'role' => PartyRole::CA, 'dob' => '1994-10-02',
                'society' => 'MACP', 'aliases' => ['ZH'],
            ],
            [
                'name' => 'Siti Aminah binti Daud', 'male' => false, 'role' => PartyRole::CA, 'dob' => '1987-12-24',
                'society' => 'MACP',
                'heir' => ['Mohd Redzuan bin Salleh', 'Spouse', true],
            ],
            [
                'name' => 'Jason Wong Zhi Kang', 'male' => true, 'role' => PartyRole::C, 'dob' => '1993-06-06',
                'society' => 'Non-Member', 'aliases' => ['JW Zhi'],
            ],
            [
                'name' => 'Dewi Anggraini Putri', 'male' => false, 'role' => PartyRole::A, 'dob' => '1989-01-19',
                'nationality' => 'Indonesian', 'society' => 'Non-Member',
                'address' => 'Jl. Kemang Raya No. 27, Kemang, Jakarta Selatan 12730, Indonesia',
                'territories' => ['MY', 'ID'], 'rights' => ['performance', 'mechanical', 'digital'],
            ],
            [
                'name' => 'Muhammad Iqbal bin Hj Salleh', 'male' => true, 'role' => PartyRole::AR, 'dob' => '1981-09-29',
                'society' => 'MACP', 'publisher' => 'Pelangi Muzik Enterprise',
                'address' => 'Lot 56, Taman Kingfisher, Jalan Tuaran, 88450 Kota Kinabalu, Sabah',
                'territories' => ['MY'], 'rights' => ['performance', 'mechanical'],
                'heir' => ['Noraini binti Ismail', 'Spouse', false],
            ],
            [
                'name' => 'Jessie Yap Mei Fong', 'male' => false, 'role' => PartyRole::CA, 'dob' => '1996-04-08',
                'society' => 'MACP', 'publisher' => 'Langit Biru Music Sdn Bhd', 'aliases' => ['Jessie Y'],
            ],
            [
                'name' => 'Abdul Rahim bin Dollah', 'male' => true, 'role' => PartyRole::CA, 'dob' => '1952-02-02',
                'died' => '2024-08-17', 'society' => 'MACP',
                'heir' => ['Hasnah binti Mohd Isa', 'Spouse', false],
                'notes' => 'Fake sample data: the writer has passed away and royalties are paid to the heir.',
            ],
            [
                'name' => 'Sufian Shahrul bin Mahyuddin', 'male' => true, 'role' => PartyRole::C, 'birthday_in' => [0, 34],
                'society' => 'MACP',
            ],
            [
                'name' => 'Nadiah Khalida binti Hairi', 'male' => false, 'role' => PartyRole::A, 'birthday_in' => [0, 29],
                'society' => 'MACP', 'aliases' => ['Nadiah K'],
            ],
            [
                'name' => 'Benjamin Teo Chin Hock', 'male' => true, 'role' => PartyRole::CA, 'birthday_in' => [1, 41],
                'nationality' => 'Singaporean', 'society' => 'Non-Member',
                'address' => '58 Joo Chiat Place, #03-01, Singapore 427781',
                'territories' => ['SG', 'MY'], 'rights' => ['performance', 'mechanical', 'sync'],
            ],
            [
                'name' => 'Preeya Nair a/p Gopalan', 'male' => false, 'role' => PartyRole::CA, 'birthday_in' => [3, 37],
                'society' => 'MACP', 'aliases' => ['Preeya N'],
            ],
            [
                'name' => 'Rosli Ahmad bin Hamdan', 'male' => true, 'role' => PartyRole::C, 'dob' => '1957-03-10',
                'society' => 'MACP', 'status' => MasterStatus::Inactive,
                'agreement_start' => '2020-01-01', 'agreement_end' => '2025-06-30',
                'notes' => 'Fake sample data: retired and the agreement was not renewed.',
            ],
            [
                'name' => 'Amirul Haziq bin Rosdi', 'male' => true, 'role' => PartyRole::CA, 'dob' => '1995-03-22',
                'society' => 'MACP', 'publisher' => 'Mutiara Digital Media Sdn Bhd', 'aliases' => ['Haziq R'],
                'heir' => ['Rosdi bin Kassim', 'Parent', true],
            ],
            [
                'name' => 'Yusri Hakim bin Jaafar', 'male' => true, 'role' => PartyRole::C, 'dob' => '1984-07-13',
                'nationality' => 'Bruneian', 'society' => 'Non-Member',
                'address' => 'No. 12, Simpang 78, Kampung Kiulap, Bandar Seri Begawan BE1318, Brunei',
                'territories' => ['BN', 'MY'], 'rights' => ['performance', 'mechanical'],
            ],
        ];
    }
}
