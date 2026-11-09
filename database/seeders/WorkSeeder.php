<?php

namespace Database\Seeders;

use App\Actions\Works\ChangePartyFromDate;
use App\Actions\Works\CorrectParty;
use App\Actions\Works\CreateWork;
use App\Actions\Works\RemoveParty;
use App\Enums\ConflictStatus;
use App\Enums\PartyRole;
use App\Enums\RegistrationStatus;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkParty;
use App\Models\Writer;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\SpreadsCreationOverTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;

class WorkSeeder extends Seeder
{
    use SpreadsCreationOverTime;

    /**
     * The writers by short key, as named in the `WriterSeeder`.
     *
     * @var array<string, string>
     */
    private const WRITERS = [
        'hafiz' => 'Hafiz Zainuddin bin Samad',
        'ain' => 'Nurul Ain binti Razak',
        'adam' => 'Adam Firdaus bin Kamarul',
        'maryam' => 'Sharifah Maryam binti Syed Hamzah',
        'weiliang' => 'Tan Wei Liang',
        'huishan' => 'Lim Hui Shan',
        'kahyan' => 'Chong Kah Yan',
        'ravi' => 'Ravindran a/l Maniam',
        'kalai' => 'Kalaivani a/p Subramaniam',
        'iskandar' => 'Iskandar Dzulkarnain bin Azhar',
        'faridah' => 'Faridah binti Yaacob',
        'zulhakimi' => 'Zul Hakimi bin Othman',
        'aminah' => 'Siti Aminah binti Daud',
        'jason' => 'Jason Wong Zhi Kang',
        'dewi' => 'Dewi Anggraini Putri',
        'iqbal' => 'Muhammad Iqbal bin Hj Salleh',
        'jessie' => 'Jessie Yap Mei Fong',
        'rahim' => 'Abdul Rahim bin Dollah',
        'sufian' => 'Sufian Shahrul bin Mahyuddin',
        'nadiah' => 'Nadiah Khalida binti Hairi',
        'benjamin' => 'Benjamin Teo Chin Hock',
        'preeya' => 'Preeya Nair a/p Gopalan',
        'rosli' => 'Rosli Ahmad bin Hamdan',
        'yusri' => 'Yusri Hakim bin Jaafar',
        'haziq' => 'Amirul Haziq bin Rosdi',
    ];

    /**
     * The publishers by short key, as named in the `PublisherSeeder`.
     *
     * @var array<string, string>
     */
    private const PUBLISHERS = [
        'archive' => 'Taja Archive',
        'serinada' => 'Seri Nada Publishing Sdn Bhd',
        'langitbiru' => 'Langit Biru Music Sdn Bhd',
        'harmoni' => 'Harmoni Timur Entertainment Sdn Bhd',
        'kopitiam' => 'Kopitiam Sound Publishing Sdn Bhd',
        'orkid' => 'Orkid Lagu Publishing Sdn Bhd',
        'pelangi' => 'Pelangi Muzik Enterprise',
        'cahaya' => 'Cahaya Asia Music Pte Ltd',
        'pantai' => 'Muzik Pantai Timur Sdn Bhd',
        'borneo' => 'Irama Borneo Publishing Sdn Bhd',
        'gema' => 'Gema Nusantara Music Sdn Bhd',
        'rimba' => 'Rimba Kreatif Publishing Sdn Bhd',
        'mutiara' => 'Mutiara Digital Media Sdn Bhd',
        'teratai' => 'Teratai Putih Music Sdn Bhd',
    ];

    /**
     * Three-character ISRC registrant codes, handed out in turn.
     *
     * @var list<string>
     */
    private const REGISTRANT_CODES = ['TJA', 'LBM', 'SNP', 'KSD', 'OLP', 'HTE'];

    /**
     * Seed fictional works with parties built through the same actions the
     * app uses, so the TAP numbers and the rights history are real. Splits name
     * the writers and publishers, and the administration start date never
     * comes before the agreements of the parties on the work. A few works are
     * left incomplete on purpose for the Incomplete Split report. Run after
     * the `PublisherSeeder` and the `WriterSeeder`.
     */
    public function run(CreateWork $createWork, ChangePartyFromDate $changeParty, CorrectParty $correctParty, RemoveParty $removeParty): void
    {
        $writers = collect(self::WRITERS)->map(fn (string $name): Writer => Writer::query()->where('name', $name)->firstOrFail());
        $publishers = collect(self::PUBLISHERS)->map(fn (string $name): Publisher => Publisher::query()->where('name', $name)->firstOrFail());
        $userId = User::query()->where('role', 'admin')->value('id');

        $complete = collect();
        $catalogue = $this->catalogue();
        $firstUse = [];
        $moments = $this->spreadMoments(count($catalogue));

        foreach ($catalogue as $index => $row) {
            $parties = $this->parties($row['parties'] ?? [], $row['sync'] ?? false, $row['territories'] ?? null, $writers, $publishers);
            $moment = $moments[$index];
            $work = $this->atMoment(
                $moment,
                fn (): Work => $createWork([
                    ...$this->workAttributes($row, $index, $parties),
                    'parties' => array_column($parties, 'data'),
                ], $userId),
            );

            foreach ($parties as $party) {
                $earliest = $firstUse[$party['master']::class][$party['master']->id] ?? $moment;
                $firstUse[$party['master']::class][$party['master']->id] = $moment->min($earliest);
            }

            if (! ($row['incomplete'] ?? false)) {
                $complete->push($work);
            }
        }

        $this->backdateMastersBeforeFirstWork($firstUse);
        $this->addHistory($complete, $changeParty, $correctParty, $removeParty, $userId);
    }

    /**
     * The publishers and writers are seeded before the works, so some of them
     * were created after the first work they appear on. Move those back to the
     * day before that work (but not before the start of the window), so no work
     * ever refers to a master that did not exist yet.
     *
     * @param  array<class-string<Writer|Publisher>, array<int, CarbonImmutable>>  $firstUse  The moment of the first work, by model class and id.
     */
    private function backdateMastersBeforeFirstWork(array $firstUse): void
    {
        $windowStart = CarbonImmutable::now()->subDays(self::SPREAD_WINDOW_DAYS)->startOfDay();

        foreach ($firstUse as $model => $moments) {
            foreach ($moments as $id => $moment) {
                $created = $moment->subDay()->max($windowStart);

                $model::query()
                    ->whereKey($id)
                    ->where('created_at', '>', $created)
                    ->update(['created_at' => $created, 'updated_at' => $created]);
            }
        }
    }

    /**
     * Territory changes, corrections and one removal, so the history and the
     * ended involvement have something to show.
     *
     * @param  Collection<int, Work>  $works
     */
    private function addHistory(Collection $works, ChangePartyFromDate $changeParty, CorrectParty $correctParty, RemoveParty $removeParty, ?int $userId): void
    {
        $works->take(5)->each(fn (Work $work) => $this->atMoment($this->afterCreation($work, 2), fn () => $changeParty($this->firstParty($work), [
            'territories_controlled' => ['MY', 'SG'],
            'effective_date' => now()->subWeek()->toDateString(),
            'remarks' => 'Fake sample: territories narrowed after a renegotiation.',
        ], $userId)));

        $works->slice(5, 4)->each(fn (Work $work) => $this->atMoment($this->afterCreation($work, 1), fn () => $correctParty($this->firstParty($work), [
            'territories_controlled' => ['MY'],
            'remarks' => 'Fake sample: territory entered wrongly.',
        ], $userId)));

        $works->slice(9, 1)->each(fn (Work $work) => $this->atMoment($this->afterCreation($work, 3), fn () => $removeParty($this->firstParty($work), [
            'effective_date' => now()->subDays(3)->toDateString(),
            'remarks' => 'Fake sample: writer withdrew from the work.',
        ], $userId)));
    }

    /**
     * A moment some days after the work was created, so a change is recorded
     * after the work it belongs to.
     */
    private function afterCreation(Work $work, int $days): CarbonImmutable
    {
        return $this->notAfterNow($work->created_at->addDays($days));
    }

    private function firstParty(Work $work): WorkParty
    {
        return $work->parties()->whereNull('superseded_at')->whereNull('effective_end_date')->firstOrFail();
    }

    /**
     * Resolve the party tuples of a work to the masters and the rows to create.
     *
     * @param  list<array{0: string, 1: string, 2: int, 3?: list<string>|null, 4?: bool}>  $tuples
     * @param  list<string>|null  $defaultTerritories  Used by parties that do not name their own territories.
     * @param  Collection<string, Writer>  $writers
     * @param  Collection<string, Publisher>  $publishers
     * @return list<array{master: Writer|Publisher, data: array<string, mixed>}>
     */
    private function parties(array $tuples, bool $hasSyncShares, ?array $defaultTerritories, Collection $writers, Collection $publishers): array
    {
        $parties = [];

        foreach ($tuples as $party) {
            [$key, $roleCode, $share] = $party;
            $role = PartyRole::from($roleCode);
            $master = $role->isPublisherRole() ? $publishers->get($key) : $writers->get($key);

            if ($master === null) {
                throw new LogicException("Unknown party key [{$key}] in the work catalogue.");
            }

            $parties[] = [
                'master' => $master,
                'data' => [
                    $role->isPublisherRole() ? 'publisher_id' : 'writer_id' => $master->id,
                    'role' => $role,
                    'performance_share' => $share,
                    'mechanical_share' => $share,
                    'sync_share' => $hasSyncShares ? $share : null,
                    'is_controlled' => $party[4] ?? true,
                    'territories_controlled' => $party[3] ?? $defaultTerritories ?? ['WW'],
                ],
            ];
        }

        return $parties;
    }

    /**
     * The work's own fields and ISRCs. Administration starts two weeks before
     * the release, but never before the agreement of any party on the work. A
     * work that was not released yet starts in March 2026.
     *
     * @param  array<string, mixed>  $row
     * @param  list<array{master: Writer|Publisher, data: array<string, mixed>}>  $parties
     * @return array<string, mixed>
     */
    private function workAttributes(array $row, int $index, array $parties): array
    {
        $released = isset($row['released']) ? CarbonImmutable::parse($row['released']) : null;
        $status = $row['status'];
        $isRegistered = $status !== RegistrationStatus::Draft;
        $isAccepted = $status === RegistrationStatus::Accepted;

        $administrationStart = collect(array_map(fn (array $party): CarbonImmutable => CarbonImmutable::parse($party['master']->agreement_start_date), $parties))
            ->push($released?->subDays(14) ?? CarbonImmutable::parse('2026-03-02'))
            ->max();

        [$hours, $minutes, $seconds] = [0, ...array_map('intval', explode(':', $row['duration']))];

        return [
            'macp_work_id' => fake()->unique()->numerify('MACP-4#######'),
            'english_title' => $row['title'],
            'other_title' => $row['other'] ?? null,
            'iswc' => $isRegistered ? 'T'.fake()->numerify('##########') : null,
            'work_type' => $row['type'] ?? 'ORG',
            'genre' => $row['genre'],
            'language' => $row['language'],
            'duration' => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
            'artist_name' => $row['artist'],
            'original_work_title' => $row['original'] ?? null,
            'sub_title_work' => $row['subtitle'] ?? null,
            'society_no' => $isAccepted ? fake()->numerify('##########') : null,
            'administration_start_date' => $administrationStart->toDateString(),
            'registration_date' => $isRegistered ? $administrationStart->addDays(21)->toDateString() : null,
            'registration_status' => $status,
            'is_public_domain' => $row['public_domain'] ?? false,
            'song_link' => $released === null ? null : 'https://songs.example.com/'.Str::slug($row['title']),
            'remark' => $row['remark'] ?? null,
            'release_date' => $released?->toDateString(),
            'compass_work_internal_no' => ($row['compass'] ?? false) ? fake()->numerify('#########') : null,
            'compass_work_type' => ($row['compass'] ?? false) ? 'Original' : null,
            'compass_start_date' => ($row['compass'] ?? false) ? $administrationStart->addMonth()->toDateString() : null,
            'wami_work_internal_no' => ($row['wami'] ?? false) ? 'W'.fake()->numerify('#######') : null,
            'wami_work_type' => ($row['wami'] ?? false) ? 'Original' : null,
            'conflict_status' => $row['conflict'][0] ?? ConflictStatus::NoConflict,
            'conflict_notes' => $row['conflict'][1] ?? null,
            'isrcs' => $this->isrcs($index, $released, ($row['versions'] ?? 1)),
        ];
    }

    /**
     * ISRCs of the form `MY` + registrant + year + serial, one per version
     * (for example an album version and a radio edit).
     *
     * @return list<string>
     */
    private function isrcs(int $index, ?CarbonImmutable $released, int $versions): array
    {
        $registrant = self::REGISTRANT_CODES[$index % count(self::REGISTRANT_CODES)];
        $year = $released?->format('y') ?? '26';

        return array_map(
            fn (int $version): string => sprintf('MY%s%s%05d', $registrant, $year, 10000 + $index * 7 + $version),
            range(0, $versions - 1),
        );
    }

    /**
     * The curated catalogue. A party is `[key, role, share, territories, isControlled]`
     * where the last two are optional. Writers on a work who are linked to a
     * publisher use that publisher, and works without one use Taja Archive.
     * The last three works are incomplete on purpose.
     *
     * @return list<array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            [
                'title' => 'Last Rain', 'other' => 'Hujan Terakhir', 'language' => 'Malay', 'genre' => 'Pop Ballad',
                'artist' => 'Aqilah Sofea', 'duration' => '3:48', 'released' => '2025-02-14',
                'status' => RegistrationStatus::Accepted, 'versions' => 2,
                'parties' => [['hafiz', 'C', 25], ['ain', 'A', 25], ['archive', 'E', 50]],
            ],
            [
                'title' => 'Waiting for the Monsoon', 'other' => 'Tunggu Tengkujuh', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Rizqi Amani', 'duration' => '3:35', 'released' => '2025-04-04',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['hafiz', 'CA', 30], ['maryam', 'CA', 20], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'Neon Rain on Jalan Alor', 'language' => 'English', 'genre' => 'Indie Pop',
                'artist' => 'Mira Lee', 'duration' => '3:21', 'released' => '2025-05-23',
                'status' => RegistrationStatus::Accepted, 'sync' => true,
                'remark' => 'Placed in a regional travel campaign.',
                'parties' => [['jason', 'C', 25], ['dewi', 'A', 25], ['archive', 'E', 50]],
            ],
            [
                'title' => 'Kopitiam Midnight', 'other' => '午夜咖啡店', 'language' => 'Mandarin', 'genre' => 'Mandopop',
                'artist' => 'Jolene Chua', 'duration' => '4:02', 'released' => '2025-06-13',
                'status' => RegistrationStatus::Accepted, 'versions' => 2,
                'parties' => [['weiliang', 'C', 25], ['huishan', 'A', 25], ['kopitiam', 'E', 50]],
            ],
            [
                'title' => 'Going Home to the Village', 'other' => 'Pulang ke Kampung', 'language' => 'Malay', 'genre' => 'Folk Pop',
                'artist' => 'Anak Desa', 'duration' => '4:10', 'released' => '2025-07-11',
                'status' => RegistrationStatus::Submitted,
                'parties' => [['zulhakimi', 'CA', 25], ['aminah', 'CA', 25], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'Sunset at Penang Hill', 'other' => 'Senja di Bukit Bendera', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Farhana Yusuf', 'duration' => '3:52', 'released' => '2025-08-01',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['adam', 'C', 25], ['nadiah', 'A', 25], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'Dance Under the Monsoon Moon', 'language' => 'English', 'genre' => 'Dance Pop',
                'artist' => 'Nova Kian', 'duration' => '3:15', 'released' => '2025-08-22',
                'status' => RegistrationStatus::Submitted,
                'parties' => [['sufian', 'C', 20], ['iskandar', 'AR', 10], ['nadiah', 'A', 20], ['archive', 'E', 50]],
            ],
            [
                'title' => 'Festival of Lights', 'other' => 'தீப ஒளி', 'language' => 'Tamil', 'genre' => 'Festive',
                'artist' => 'Shalini Devi', 'duration' => '4:20', 'released' => '2025-09-05',
                'status' => RegistrationStatus::Accepted, 'wami' => true,
                'parties' => [['ravi', 'C', 25], ['kalai', 'A', 25], ['harmoni', 'E', 50]],
            ],
            [
                'title' => "A Mother's Longing", 'other' => 'Rindu Seorang Ibu', 'language' => 'Malay', 'genre' => 'Ballad',
                'artist' => 'Hanis Zulaikha', 'duration' => '4:27', 'released' => '2024-05-10',
                'status' => RegistrationStatus::Accepted,
                'remark' => 'The composer has passed away, so his share is paid to his heir.',
                'parties' => [['faridah', 'A', 30], ['rahim', 'C', 20], ['orkid', 'E', 50]],
            ],
            [
                'title' => 'One Breath', 'other' => 'Satu Tarikan Nafas', 'language' => 'Malay', 'genre' => 'Pop Rock',
                'artist' => 'Studio Hijau', 'duration' => '4:05', 'released' => '2025-09-26',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['kahyan', 'CA', 25], ['zulhakimi', 'CA', 25], ['langitbiru', 'E', 50]],
            ],
            [
                'title' => 'Our Land', 'other' => 'Tanah Kita', 'language' => 'Malay', 'genre' => 'Folk Pop',
                'artist' => 'Sino Borneo', 'duration' => '3:58', 'released' => '2025-10-10',
                'status' => RegistrationStatus::Submitted, 'territories' => ['MY'],
                'parties' => [['iqbal', 'AR', 15], ['preeya', 'CA', 35], ['pelangi', 'E', 50]],
            ],
            [
                'title' => 'Falling in Love Three Times', 'other' => 'Tiga Kali Jatuh Cinta', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Nadine Amira', 'duration' => '3:30', 'released' => '2025-10-31',
                'status' => RegistrationStatus::Conflict,
                'conflict' => [ConflictStatus::ShareConflict, 'Another publisher claims 10% of the lyrics. Waiting for the society to rule.'],
                'parties' => [['maryam', 'CA', 20], ['zulhakimi', 'C', 15], ['ain', 'A', 15], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'Starlight Serenade', 'language' => 'English', 'genre' => 'R&B',
                'artist' => 'Zara Quinn', 'duration' => '3:44', 'released' => '2025-11-14',
                'status' => RegistrationStatus::Accepted, 'compass' => true,
                'remark' => 'The Singapore co-writer is represented by his own society.',
                'parties' => [['benjamin', 'CA', 30, null, false], ['jason', 'C', 20], ['langitbiru', 'E', 50]],
            ],
            [
                'title' => 'Every Spring', 'other' => '每一个春天', 'language' => 'Mandarin', 'genre' => 'Ballad',
                'artist' => 'Elaine Kho', 'duration' => '4:15', 'released' => '2025-12-05',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['weiliang', 'C', 30], ['huishan', 'A', 20], ['kopitiam', 'E', 50]],
            ],
            [
                'title' => 'Breaking the Silence', 'other' => 'Pecah Hening', 'language' => 'Malay', 'genre' => 'Rock',
                'artist' => 'Garis Hitam', 'duration' => '4:05', 'released' => null,
                'status' => RegistrationStatus::Draft,
                'remark' => 'Recording still in progress, release planned for next year.',
                'parties' => [['sufian', 'C', 25], ['hafiz', 'A', 25], ['archive', 'E', 50]],
            ],
            [
                'title' => 'Going Home to the Village (Reggae Remix)', 'other' => 'Pulang ke Kampung (Remix Reggae)', 'language' => 'Malay', 'genre' => 'Reggae',
                'artist' => 'Anak Desa x DJ Brisk', 'duration' => '3:40', 'released' => '2026-01-16',
                'status' => RegistrationStatus::Amended, 'type' => 'MOD',
                'original' => 'Going Home to the Village', 'subtitle' => 'Reggae Remix',
                'conflict' => [ConflictStatus::ShareConflict, 'The original publisher asked for a share of the new arrangement.'],
                'parties' => [['iskandar', 'AR', 20], ['zulhakimi', 'CA', 15], ['aminah', 'CA', 15], ['serinada', 'E', 50]],
                'versions' => 2,
            ],
            [
                'title' => 'Moonlit Rooftop', 'language' => 'English', 'genre' => 'Acoustic Pop',
                'artist' => 'Elliot Han', 'duration' => '3:27', 'released' => '2026-01-30',
                'status' => RegistrationStatus::Accepted, 'sync' => true,
                'parties' => [['jessie', 'CA', 35], ['benjamin', 'CA', 15], ['langitbiru', 'E', 50]],
            ],
            [
                'title' => 'Coastal Dance', 'other' => 'Tarian Pesisir', 'language' => 'Malay', 'genre' => 'Traditional',
                'artist' => 'Orkestra Pesisir', 'duration' => '2:58', 'released' => '2026-02-13',
                'status' => RegistrationStatus::Accepted, 'type' => 'ARR', 'public_domain' => true,
                'original' => 'Tarian Pesisir (Traditional)',
                'remark' => 'Public domain folk melody. Only the new arrangement earns a share.',
                'parties' => [['iskandar', 'AR', 50], ['archive', 'E', 50]],
            ],
            [
                'title' => "A Wanderer's Loneliness", 'other' => 'Sepi Seorang Perantau', 'language' => 'Malay', 'genre' => 'Ballad',
                'artist' => 'Zafri Hakim', 'duration' => '4:33', 'released' => '2022-11-20',
                'status' => RegistrationStatus::Accepted,
                'remark' => 'Back-catalogue work. The composer retired and his agreement has ended.',
                'parties' => [['rosli', 'C', 30], ['faridah', 'A', 20], ['orkid', 'E', 50]],
            ],
            [
                'title' => 'Tropical Heartbeat', 'language' => 'English', 'genre' => 'Dance',
                'artist' => 'Kai & Luna', 'duration' => '3:12', 'released' => '2026-02-27',
                'status' => RegistrationStatus::Submitted,
                'remark' => 'Sub-published in Singapore, Brunei and Indonesia.',
                'parties' => [['yusri', 'C', 25], ['dewi', 'A', 25], ['archive', 'E', 30], ['cahaya', 'SE', 20, ['SG', 'BN', 'ID']]],
            ],
            [
                'title' => 'Love at the Harbour', 'other' => 'Cinta di Pelabuhan', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Aleya Rose', 'duration' => '3:49', 'released' => '2026-03-13',
                'status' => RegistrationStatus::Rejected,
                'conflict' => [ConflictStatus::SocietyQuery, 'The society asked for the IPI number of the author to be confirmed.'],
                'parties' => [['adam', 'C', 25], ['ain', 'A', 25], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'A Promise Under the Moon', 'other' => '月光下的约定', 'language' => 'Mandarin', 'genre' => 'Ballad',
                'artist' => 'Ethan Goh', 'duration' => '4:08', 'released' => '2026-04-03',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['weiliang', 'C', 25], ['kahyan', 'A', 25], ['kopitiam', 'E', 50]],
            ],
            [
                'title' => 'Waiting for Morning', 'other' => 'Menanti Pagi', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Sheila Dania', 'duration' => '3:37', 'released' => '2026-04-24',
                'status' => RegistrationStatus::Submitted,
                'parties' => [['maryam', 'CA', 25], ['nadiah', 'A', 25], ['serinada', 'E', 50]],
            ],
            [
                'title' => "City Lights Don't Sleep", 'language' => 'English', 'genre' => 'Synth Pop',
                'artist' => 'Marcus Tay', 'duration' => '3:56', 'released' => null,
                'status' => RegistrationStatus::Draft,
                'parties' => [['benjamin', 'CA', 25], ['jason', 'C', 25], ['langitbiru', 'E', 50]],
            ],
            [
                'title' => 'Fireworks Over the Strait', 'other' => 'Bunga Api di Selat', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Sofea Daniya', 'duration' => '3:39', 'released' => '2024-09-06',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['haziq', 'A', 25], ['hafiz', 'C', 25], ['mutiara', 'E', 50]],
            ],
            [
                'title' => 'Teh Tarik Blues', 'language' => 'English', 'genre' => 'Blues Pop',
                'artist' => 'Rafi & The Roadies', 'duration' => '3:26', 'released' => '2024-10-18',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['jason', 'C', 25], ['kahyan', 'A', 25], ['archive', 'E', 50]],
            ],
            [
                'title' => 'Greetings from the East', 'other' => 'Salam Dari Timur', 'language' => 'Malay', 'genre' => 'Folk Pop',
                'artist' => 'Pantai Sunyi', 'duration' => '4:01', 'released' => '2024-11-22',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['aminah', 'CA', 25], ['zulhakimi', 'CA', 25], ['pantai', 'E', 50]],
            ],
            [
                'title' => 'Porch of Longing', 'other' => 'Serambi Rindu', 'language' => 'Malay', 'genre' => 'Ballad',
                'artist' => 'Wan Aqilah', 'duration' => '4:18', 'released' => '2024-12-13',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['maryam', 'CA', 25], ['nadiah', 'A', 25], ['pantai', 'E', 50]],
            ],
            [
                'title' => 'Heart of Borneo', 'other' => 'Hati Borneo', 'language' => 'Malay', 'genre' => 'Ethnic Pop',
                'artist' => 'Rhythm of Rajang', 'duration' => '3:47', 'released' => '2025-01-10',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['iqbal', 'AR', 10], ['preeya', 'CA', 20], ['kahyan', 'A', 20], ['borneo', 'E', 50]],
            ],
            [
                'title' => 'Forest Breeze', 'other' => 'Bayu Rimba', 'language' => 'Instrumental', 'genre' => 'Ambient',
                'artist' => 'Studio Rimba', 'duration' => '4:58', 'released' => '2025-01-24',
                'status' => RegistrationStatus::Accepted, 'sync' => true,
                'remark' => 'Instrumental library track for advertising and film.',
                'parties' => [['iskandar', 'AR', 20], ['sufian', 'C', 30], ['rimba', 'E', 50]],
            ],
            [
                'title' => 'Morning Kopi', 'language' => 'English', 'genre' => 'Acoustic',
                'artist' => 'Ellis Tan', 'duration' => '3:09', 'released' => '2025-02-07',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['jessie', 'CA', 30], ['jason', 'C', 20], ['langitbiru', 'E', 50]],
            ],
            [
                'title' => 'Endless Dream', 'other' => 'Mimpi Tak Bertepi', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Hana Qistina', 'duration' => '3:33', 'released' => '2025-02-28',
                'status' => RegistrationStatus::Submitted,
                'parties' => [['zulhakimi', 'C', 25], ['ain', 'A', 25], ['gema', 'E', 50]],
            ],
            [
                'title' => 'Our Festive Song', 'other' => 'Lagu Raya Kita', 'language' => 'Malay', 'genre' => 'Festive',
                'artist' => 'Keluarga Bahagia', 'duration' => '3:55', 'released' => '2025-03-14',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['faridah', 'A', 20], ['hafiz', 'C', 15], ['aminah', 'CA', 15], ['orkid', 'E', 50]],
            ],
            [
                'title' => 'Prosperous Spring', 'other' => '新春财运', 'language' => 'Mandarin', 'genre' => 'Festive',
                'artist' => 'Wen Jie & Friends', 'duration' => '3:20', 'released' => '2025-01-03',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['weiliang', 'C', 20], ['kahyan', 'A', 20], ['huishan', 'A', 10], ['kopitiam', 'E', 50]],
            ],
            [
                'title' => 'Light the Lamps', 'other' => 'விளக்கேற்று', 'language' => 'Tamil', 'genre' => 'Festive',
                'artist' => 'Meena Kumari', 'duration' => '3:44', 'released' => '2025-10-17',
                'status' => RegistrationStatus::Accepted, 'wami' => true,
                'parties' => [['ravi', 'C', 20], ['kalai', 'A', 20], ['preeya', 'CA', 10], ['harmoni', 'E', 50]],
            ],
            [
                'title' => 'Third Love', 'other' => 'Cinta Ketiga', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Irfan Zulkarnain', 'duration' => '3:50', 'released' => '2025-03-28',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['adam', 'C', 30], ['ain', 'A', 20], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'Wild Hearts Run', 'language' => 'English', 'genre' => 'Pop Rock',
                'artist' => 'Kestrel', 'duration' => '3:58', 'released' => '2025-04-18',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['yusri', 'C', 20], ['benjamin', 'CA', 15], ['jason', 'C', 15], ['langitbiru', 'E', 50]],
            ],
            [
                'title' => 'Streetlights', 'other' => 'Lampu Jalan', 'language' => 'Malay', 'genre' => 'Indie',
                'artist' => 'Ruang Biru', 'duration' => '3:42', 'released' => '2025-05-02',
                'status' => RegistrationStatus::Submitted,
                'parties' => [['haziq', 'CA', 30], ['nadiah', 'A', 20], ['mutiara', 'E', 50]],
            ],
            [
                'title' => 'Tide of Memory', 'language' => 'English', 'genre' => 'Ballad',
                'artist' => 'Celeste Ong', 'duration' => '4:11', 'released' => '2025-05-16',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['benjamin', 'CA', 25], ['jessie', 'CA', 25], ['langitbiru', 'E', 50]],
            ],
            [
                'title' => 'Fireflies', 'other' => 'Kelip Kelip', 'language' => 'Malay', 'genre' => "Children's",
                'artist' => 'Kumpulan Ceria', 'duration' => '2:45', 'released' => '2025-06-06',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['aminah', 'CA', 30], ['zulhakimi', 'C', 20], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'A Thousand Lanterns', 'language' => 'English', 'genre' => 'Cinematic Pop',
                'artist' => 'Aria Lim', 'duration' => '3:53', 'released' => '2025-06-20',
                'status' => RegistrationStatus::Conflict,
                'conflict' => [ConflictStatus::ShareConflict, 'The composer and the arranger disagree about the split.'],
                'parties' => [['sufian', 'C', 20], ['iskandar', 'AR', 20], ['hafiz', 'A', 10], ['archive', 'E', 50]],
            ],
            [
                'title' => 'Weekend in Melaka', 'other' => 'Hujung Minggu di Melaka', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Aizat Rafiq', 'duration' => '3:28', 'released' => '2025-07-04',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['zulhakimi', 'CA', 20], ['haziq', 'A', 15], ['ain', 'A', 15], ['gema', 'E', 50]],
            ],
            [
                'title' => 'Beach Nostalgia', 'other' => 'Nostalgia Pantai', 'language' => 'Malay', 'genre' => 'Retro',
                'artist' => 'Orkes Pantai', 'duration' => '4:12', 'released' => '2025-07-25',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['faridah', 'A', 25], ['maryam', 'C', 25], ['orkid', 'E', 50]],
            ],
            [
                'title' => 'Mandarin Rain', 'other' => '雨中思念', 'language' => 'Mandarin', 'genre' => 'Mandopop',
                'artist' => 'Crystal Sim', 'duration' => '3:57', 'released' => '2025-08-08',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['weiliang', 'C', 25], ['huishan', 'A', 25], ['kopitiam', 'E', 50]],
            ],
            [
                'title' => 'Sky Without Borders', 'language' => 'English', 'genre' => 'Pop',
                'artist' => 'Luna Bright', 'duration' => '3:36', 'released' => '2025-08-29',
                'status' => RegistrationStatus::Submitted,
                'remark' => 'Sub-published in Singapore, Brunei and Indonesia.',
                'parties' => [['dewi', 'A', 20], ['yusri', 'C', 20], ['benjamin', 'CA', 10], ['archive', 'E', 30], ['cahaya', 'SE', 20, ['SG', 'BN', 'ID']]],
            ],
            [
                'title' => 'Cape of Hope', 'other' => 'Tanjung Harapan', 'language' => 'Malay', 'genre' => 'Folk Pop',
                'artist' => 'Pelangi Voices', 'duration' => '4:00', 'released' => '2025-09-12',
                'status' => RegistrationStatus::Accepted, 'territories' => ['MY'],
                'parties' => [['iqbal', 'AR', 15], ['preeya', 'CA', 35], ['pelangi', 'E', 50]],
            ],
            [
                'title' => 'Celebrate Love', 'other' => 'Raikan Cinta', 'language' => 'Malay', 'genre' => 'Wedding',
                'artist' => 'Duo Seri', 'duration' => '3:41', 'released' => '2025-09-19',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['hafiz', 'C', 25], ['nadiah', 'A', 25], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'Electric Kampung', 'language' => 'English', 'genre' => 'Electro Folk',
                'artist' => 'Kampung Circuit', 'duration' => '3:19', 'released' => '2025-10-03',
                'status' => RegistrationStatus::Accepted, 'sync' => true,
                'parties' => [['sufian', 'C', 25], ['jason', 'C', 25], ['archive', 'E', 50]],
            ],
            [
                'title' => "Mother's Prayer", 'other' => 'Doa Ibu', 'language' => 'Malay', 'genre' => 'Nasyid',
                'artist' => 'Munsyid Harmoni', 'duration' => '4:30', 'released' => '2025-10-24',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['aminah', 'A', 25], ['zulhakimi', 'C', 25], ['teratai', 'E', 50]],
            ],
            [
                'title' => 'Waves of Feeling', 'other' => 'Ombak Rasa', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Nadya Farhana', 'duration' => '3:34', 'released' => '2025-11-07',
                'status' => RegistrationStatus::Rejected,
                'conflict' => [ConflictStatus::Duplicate, 'The society says it matches another registered work.'],
                'parties' => [['kahyan', 'CA', 25], ['zulhakimi', 'CA', 25], ['borneo', 'E', 50]],
            ],
            [
                'title' => 'Last Train to Sentral', 'language' => 'English', 'genre' => 'Synth Pop',
                'artist' => 'Metro Dreams', 'duration' => '3:47', 'released' => '2025-11-28',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['jessie', 'CA', 25], ['jason', 'C', 15], ['benjamin', 'A', 10], ['langitbiru', 'E', 50]],
            ],
            [
                'title' => 'Lamp of the Heart', 'other' => 'Pelita Hati', 'language' => 'Malay', 'genre' => 'Ballad',
                'artist' => 'Insyirah Hamzah', 'duration' => '4:05', 'released' => '2025-12-12',
                'status' => RegistrationStatus::Accepted,
                'parties' => [['maryam', 'CA', 25], ['haziq', 'A', 25], ['mutiara', 'E', 50]],
            ],
            [
                'title' => 'Spring Breeze', 'other' => '春风十里', 'language' => 'Mandarin', 'genre' => 'Mandopop',
                'artist' => 'Hao Ren', 'duration' => '3:49', 'released' => '2026-02-06',
                'status' => RegistrationStatus::Amended,
                'remark' => 'Lyricist share corrected after the first registration.',
                'parties' => [['weiliang', 'C', 30], ['kahyan', 'A', 20], ['kopitiam', 'E', 50]],
            ],
            [
                'title' => 'Love Through the Years', 'other' => 'Kasih Sepanjang Masa', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Zarina Rashid', 'duration' => '3:56', 'released' => '2026-03-27',
                'status' => RegistrationStatus::Submitted,
                'parties' => [['adam', 'C', 25], ['aminah', 'A', 25], ['serinada', 'E', 50]],
            ],
            [
                'title' => 'Midnight Mamak', 'language' => 'English', 'genre' => 'Hip Hop',
                'artist' => 'MC Lokal', 'duration' => '3:12', 'released' => '2026-04-17',
                'status' => RegistrationStatus::Accepted, 'sync' => true,
                'parties' => [['yusri', 'C', 25], ['haziq', 'A', 25], ['rimba', 'E', 50]],
            ],
            [
                'title' => 'Shadow in the Mirror', 'other' => 'Bayang di Cermin', 'language' => 'Malay', 'genre' => 'Pop',
                'artist' => 'Dian Safiya', 'duration' => '3:41', 'released' => null,
                'status' => RegistrationStatus::Draft, 'incomplete' => true,
                'remark' => 'Splits are still being confirmed with the writers.',
            ],
            [
                'title' => 'Midnight Ferry', 'language' => 'English', 'genre' => 'Indie Pop',
                'artist' => 'The Paper Lanterns', 'duration' => '3:18', 'released' => '2026-05-08',
                'status' => RegistrationStatus::Submitted, 'incomplete' => true,
                'remark' => 'Lyricist and publisher shares are not confirmed yet.',
                'parties' => [['jason', 'C', 60]],
            ],
            [
                'title' => 'Three Seasons', 'other' => '三个季节', 'language' => 'Mandarin', 'genre' => 'Mandopop',
                'artist' => 'Jolene Chua', 'duration' => '3:54', 'released' => '2026-05-29',
                'status' => RegistrationStatus::Submitted, 'incomplete' => true,
                'remark' => 'The publisher share is still to be agreed.',
                'parties' => [['weiliang', 'C', 40], ['huishan', 'A', 30]],
            ],
            [
                'title' => 'Parting Without Meeting', 'other' => 'Berpisah Tak Bersua', 'language' => 'Malay', 'genre' => 'Ballad',
                'artist' => 'Faizal Hanif', 'duration' => '3:50', 'released' => '2026-06-05',
                'status' => RegistrationStatus::Submitted, 'incomplete' => true,
                'remark' => 'The lyricist and publisher shares are missing.',
                'parties' => [['haziq', 'C', 50]],
            ],
            [
                'title' => 'Autumn in Kuala Lumpur', 'language' => 'English', 'genre' => 'Jazz',
                'artist' => 'Mei & The Quartet', 'duration' => '3:22', 'released' => null,
                'status' => RegistrationStatus::Draft, 'incomplete' => true,
                'remark' => 'Waiting for the signed split sheet.',
            ],
        ];
    }
}
