<?php

namespace Database\Seeders;

use App\Enums\MasterStatus;
use App\Models\Publisher;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\GeneratesMalaysianDetails;
use Database\Seeders\Concerns\SpreadsCreationOverTime;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    use GeneratesMalaysianDetails, SpreadsCreationOverTime;

    /**
     * Seed fictional music publishers, including the "Taja Archive" publisher
     * that holds the rights of works without an outside publisher. The company
     * names, people and numbers are invented, but they agree with each other:
     * the email uses the company domain, the registration number starts with
     * the year of incorporation and the bank matches its SWIFT code.
     */
    public function run(): void
    {
        $publishers = $this->publishers();
        $moments = $this->spreadMoments(count($publishers));

        foreach ($publishers as $index => $publisher) {
            $this->atMoment(
                $moments[$index],
                fn (): Publisher => $this->createPublisher($publisher, $index),
            );
        }
    }

    /**
     * Build one publisher from the curated row and fill in the details that
     * follow from it.
     *
     * @param  array<string, mixed>  $row
     */
    private function createPublisher(array $row, int $index): Publisher
    {
        $bank = $row['bank'] ?? $this->bankAt($index);
        $incorporated = CarbonImmutable::parse($row['incorporated']);
        $contact = $row['contact'];
        $isOutsideMalaysia = isset($row['bank']);

        return Publisher::factory()->create([
            'name' => $row['name'],
            'rights_types_granted' => $row['rights'],
            'ipi_base_number' => $this->ipiBaseNumber(),
            'ipi_name_number' => $this->ipiNameNumber(),
            'company_registration_number' => $isOutsideMalaysia
                ? $incorporated->format('Y').fake()->numerify('#####').fake()->randomElement(['A', 'B', 'D', 'E', 'K'])
                : $incorporated->format('Y').'01'.fake()->numerify('######'),
            'registration_address' => $row['address'],
            'official_email' => 'info@'.$row['domain'],
            'incorporation_date' => $incorporated->toDateString(),
            'society_cmo' => $row['society'],
            'society_number' => $row['society'] === 'Non-Member' ? null : $row['society'].'-P-'.fake()->numerify('####'),
            'tin_number' => $isOutsideMalaysia ? null : 'C'.fake()->numerify('##########'),
            'status' => $row['status'] ?? MasterStatus::Active,
            'agreement_start_date' => $row['agreement_start'],
            'agreement_end_date' => $row['agreement_end'] ?? null,
            'auto_renewal' => $row['auto_renewal'] ?? false,
            'pre_term' => $row['pre_term'] ?? false,
            'post_term' => $row['post_term'] ?? 'During Active Year',
            'territories_granted' => $row['territories'],
            'bank_holder_name' => $row['name'],
            'bank_name' => $bank['name'],
            'client_account_number' => $this->accountNumberAt($bank),
            'swift_code' => $bank['swift'],
            'bank_address' => $bank['address'],
            'person_in_charge_name' => $contact['name'],
            'person_in_charge_title' => $contact['title'],
            'person_in_charge_nric' => $isOutsideMalaysia
                ? $contact['nric']
                : $this->nricFor(CarbonImmutable::parse(fake()->dateTimeBetween('-58 years', '-32 years')), $contact['male']),
            'person_in_charge_mobile_no' => $row['mobile'] ?? $this->mobileNumber(),
            'person_in_charge_email' => $this->emailLocalPart($contact['name']).'@'.$row['domain'],
            'taja_percentage_deal' => $row['taja_deal'],
            'client_percentage_deal' => 100 - $row['taja_deal'],
            'source_system' => 'Seeder',
            'notes' => $row['notes'] ?? null,
        ]);
    }

    /**
     * The curated publishers.
     *
     * @return list<array<string, mixed>>
     */
    private function publishers(): array
    {
        return [
            [
                'name' => 'Taja Archive',
                'domain' => 'tajamusic.example',
                'address' => 'Level 21, Menara Taja, 8 Jalan Kerinchi, Bangsar South, 59200 Kuala Lumpur',
                'incorporated' => '2021-02-15',
                'society' => 'MACP',
                'rights' => ['performance', 'mechanical', 'sync', 'lyrics', 'digital'],
                'territories' => ['WW'],
                'agreement_start' => '2021-03-01',
                'taja_deal' => 100,
                'contact' => ['name' => 'Aisyah Rahman binti Idris', 'title' => 'Head of Catalogue', 'male' => false],
                'notes' => 'Fake sample data: holds works without an outside publisher.',
            ],
            [
                'name' => 'Seri Nada Publishing Sdn Bhd',
                'domain' => 'serinada.example',
                'address' => 'Unit 12-3, Menara Seri Nada, Jalan Kiara 3, Mont Kiara, 50480 Kuala Lumpur',
                'incorporated' => '2016-08-22',
                'society' => 'MACP',
                'rights' => ['performance', 'mechanical', 'digital'],
                'territories' => ['WW'],
                'agreement_start' => '2024-03-01',
                'auto_renewal' => true,
                'taja_deal' => 70,
                'contact' => ['name' => 'Hazwani binti Mat Isa', 'title' => 'Managing Director', 'male' => false],
            ],
            [
                'name' => 'Langit Biru Music Sdn Bhd',
                'domain' => 'langitbiru.example',
                'address' => 'No. 28, Jalan SS 21/37, Damansara Utama, 47400 Petaling Jaya, Selangor',
                'incorporated' => '2012-05-09',
                'society' => 'MACP',
                'rights' => ['performance', 'mechanical', 'sync'],
                'territories' => ['MY', 'SG', 'BN'],
                'agreement_start' => '2023-07-15',
                'pre_term' => true,
                'post_term' => '12 months after the end date',
                'taja_deal' => 60,
                'contact' => ['name' => 'Daniel Lim Kok Leong', 'title' => 'Director, Business Affairs', 'male' => true],
            ],
            [
                'name' => 'Harmoni Timur Entertainment Sdn Bhd',
                'domain' => 'harmonitimur.example',
                'address' => 'Level 9, Wisma Harmoni, Persiaran Gurney, 10250 George Town, Pulau Pinang',
                'incorporated' => '2009-11-30',
                'society' => 'WAMI',
                'rights' => ['performance', 'mechanical'],
                'territories' => ['MY', 'SG'],
                'agreement_start' => '2022-09-01',
                'taja_deal' => 80,
                'contact' => ['name' => 'Kavitha a/p Raman', 'title' => 'Head of Licensing', 'male' => false],
            ],
            [
                'name' => 'Kopitiam Sound Publishing Sdn Bhd',
                'domain' => 'kopitiamsound.example',
                'address' => 'No. 5, Jalan Molek 2/3, Taman Molek, 81100 Johor Bahru, Johor',
                'incorporated' => '2018-03-14',
                'society' => 'MACP',
                'rights' => ['performance', 'lyrics'],
                'territories' => ['MY', 'SG'],
                'agreement_start' => '2024-01-10',
                'taja_deal' => 50,
                'contact' => ['name' => 'Tan Siew Hoon', 'title' => 'General Manager', 'male' => false],
                'notes' => 'Fake sample data: mainly Mandarin and Cantopop repertoire.',
            ],
            [
                'name' => 'Orkid Lagu Publishing Sdn Bhd',
                'domain' => 'orkidlagu.example',
                'address' => 'Suite 18.03, Menara Orkid, Jalan Sultan Ismail, 50250 Kuala Lumpur',
                'incorporated' => '2005-01-18',
                'society' => 'MACP',
                'rights' => ['performance', 'mechanical', 'sync', 'lyrics', 'digital'],
                'territories' => ['WW'],
                'agreement_start' => '2022-01-10',
                'auto_renewal' => true,
                'pre_term' => true,
                'taja_deal' => 75,
                'contact' => ['name' => 'Zulkifli bin Hamid', 'title' => 'Chief Executive Officer', 'male' => true],
            ],
            [
                'name' => 'Pelangi Muzik Enterprise',
                'domain' => 'pelangimuzik.example',
                'address' => 'Lot 14, Block C, Lorong Api-Api 1, Api-Api Centre, 88000 Kota Kinabalu, Sabah',
                'incorporated' => '2014-06-02',
                'society' => 'MACP',
                'rights' => ['performance', 'mechanical'],
                'territories' => ['MY'],
                'agreement_start' => '2023-04-03',
                'taja_deal' => 60,
                'contact' => ['name' => 'Jessica Gunsalam', 'title' => 'Owner', 'male' => false],
                'notes' => 'Fake sample data: Sabahan and Sarawakan repertoire.',
            ],
            [
                'name' => 'Cahaya Asia Music Pte Ltd',
                'domain' => 'cahayaasia.example',
                'address' => '10 Jalan Besar, #04-02, Singapore 208787',
                'incorporated' => '2017-10-05',
                'society' => 'Non-Member',
                'rights' => ['sync', 'digital'],
                'territories' => ['MY', 'SG', 'BN', 'ID'],
                'agreement_start' => '2025-02-01',
                'taja_deal' => 65,
                'mobile' => '+6591234567',
                'bank' => ['name' => 'DBS Bank', 'swift' => 'DBSSSGSG', 'address' => '12 Marina Boulevard, DBS Asia Central, Singapore 018982', 'digits' => 10],
                'contact' => ['name' => 'Priscilla Ong Hui Ling', 'title' => 'Regional Licensing Manager', 'male' => false, 'nric' => 'S8412345D'],
                'notes' => 'Fake sample data: Singapore-based sub-publisher for the region.',
            ],
            [
                'name' => 'Bintang Utara Music Group Sdn Bhd',
                'domain' => 'bintangutara.example',
                'address' => 'No. 22, Jalan Sultan Idris Shah, 30000 Ipoh, Perak',
                'incorporated' => '2003-04-21',
                'society' => 'WAMI',
                'rights' => ['performance'],
                'territories' => ['MY'],
                'agreement_start' => '2019-01-01',
                'agreement_end' => '2025-12-31',
                'status' => MasterStatus::Inactive,
                'taja_deal' => 50,
                'contact' => ['name' => 'Rosnah binti Hashim', 'title' => 'Director', 'male' => false],
                'notes' => 'Fake sample data: agreement ended and was not renewed.',
            ],
            [
                'name' => 'Muzik Pantai Timur Sdn Bhd',
                'domain' => 'pantaitimur.example',
                'address' => 'Lot 2188, Jalan Kebun Sultan, 15350 Kota Bharu, Kelantan',
                'incorporated' => '2011-09-12',
                'society' => 'WAMI',
                'rights' => ['performance', 'mechanical'],
                'territories' => ['MY'],
                'agreement_start' => '2023-10-02',
                'taja_deal' => 60,
                'contact' => ['name' => 'Wan Nur Aliya binti Wan Ismail', 'title' => 'Managing Director', 'male' => false],
                'notes' => 'Fake sample data: east coast Malay and folk repertoire.',
            ],
            [
                'name' => 'Irama Borneo Publishing Sdn Bhd',
                'domain' => 'iramaborneo.example',
                'address' => 'No. 88, Jalan Padungan, 93100 Kuching, Sarawak',
                'incorporated' => '2015-12-07',
                'society' => 'MACP',
                'rights' => ['performance', 'mechanical', 'lyrics'],
                'territories' => ['MY', 'BN'],
                'agreement_start' => '2024-05-20',
                'auto_renewal' => true,
                'taja_deal' => 65,
                'contact' => ['name' => 'Stephen Ajang anak Lawai', 'title' => 'Director', 'male' => true],
            ],
            [
                'name' => 'Gema Nusantara Music Sdn Bhd',
                'domain' => 'gemanusantara.example',
                'address' => 'No. 17, Jalan Hang Tuah, 75300 Melaka',
                'incorporated' => '2013-02-25',
                'society' => 'MACP',
                'rights' => ['performance', 'mechanical', 'digital'],
                'territories' => ['MY', 'SG', 'ID'],
                'agreement_start' => '2024-08-12',
                'pre_term' => true,
                'taja_deal' => 70,
                'contact' => ['name' => 'Nurul Huda binti Sulaiman', 'title' => 'Head of Publishing', 'male' => false],
            ],
            [
                'name' => 'Rimba Kreatif Publishing Sdn Bhd',
                'domain' => 'rimbakreatif.example',
                'address' => 'Lot 6, Jalan Dato\' Siamang Gagap, 70000 Seremban, Negeri Sembilan',
                'incorporated' => '2019-07-16',
                'society' => 'Non-Member',
                'rights' => ['sync', 'digital'],
                'territories' => ['WW'],
                'agreement_start' => '2024-11-04',
                'taja_deal' => 55,
                'contact' => ['name' => 'Ganesh a/l Perumal', 'title' => 'Creative Director', 'male' => true],
                'notes' => 'Fake sample data: library and advertising music.',
            ],
            [
                'name' => 'Mutiara Digital Media Sdn Bhd',
                'domain' => 'mutiaradigital.example',
                'address' => 'Block 3, Persiaran Apec, Cyber 6, 63000 Cyberjaya, Selangor',
                'incorporated' => '2020-03-09',
                'society' => 'MACP',
                'rights' => ['digital', 'sync', 'performance'],
                'territories' => ['WW'],
                'agreement_start' => '2025-01-06',
                'auto_renewal' => true,
                'taja_deal' => 80,
                'contact' => ['name' => 'Amirah Qistina binti Rahman', 'title' => 'Head of Digital', 'male' => false],
            ],
            [
                'name' => 'Teratai Putih Music Sdn Bhd',
                'domain' => 'terataiputih.example',
                'address' => 'No. 9, Jalan Besar, 25000 Kuantan, Pahang',
                'incorporated' => '2008-06-30',
                'society' => 'WAMI',
                'rights' => ['performance', 'mechanical'],
                'territories' => ['MY'],
                'agreement_start' => '2022-04-18',
                'taja_deal' => 50,
                'contact' => ['name' => 'Lee Chee Meng', 'title' => 'Proprietor', 'male' => true],
            ],
        ];
    }
}
