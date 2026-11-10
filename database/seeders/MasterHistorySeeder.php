<?php

namespace Database\Seeders;

use App\Actions\Masters\RecordMasterHistory;
use App\Actions\Masters\UpdatePublisher;
use App\Actions\Masters\UpdateWriter;
use App\Enums\MasterChangeType;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Writer;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\GeneratesMalaysianDetails;
use Database\Seeders\Concerns\SpreadsCreationOverTime;
use Illuminate\Database\Seeder;

class MasterHistorySeeder extends Seeder
{
    use GeneratesMalaysianDetails, SpreadsCreationOverTime;

    /**
     * Give every seeded writer and publisher a `Created` entry at the moment it
     * was created, then add a few named changes (a renegotiated deal, new bank
     * details and a new pseudonym) through the same actions the app uses. Run
     * after the `WorkSeeder`, which may move the creation of a master back.
     */
    public function run(RecordMasterHistory $recordHistory, UpdateWriter $updateWriter, UpdatePublisher $updatePublisher): void
    {
        $userId = User::query()->where('role', 'admin')->value('id');

        foreach ([...Publisher::query()->get(), ...Writer::query()->get()] as $master) {
            $this->atMoment($master->created_at, fn () => $recordHistory(
                $master,
                MasterChangeType::Created,
                new: RecordMasterHistory::withoutEmptyValues(RecordMasterHistory::snapshot($master)),
                userId: $userId,
            ));
        }

        $this->renegotiatePublisherDeal($updatePublisher, $userId);
        $this->changeWriterBank($updateWriter, $userId);
        $this->addWriterPseudonym($updateWriter, $userId);
    }

    private function renegotiatePublisherDeal(UpdatePublisher $updatePublisher, ?int $userId): void
    {
        $publisher = Publisher::query()->where('name', 'Seri Nada Publishing Sdn Bhd')->first();

        if ($publisher === null) {
            return;
        }

        $this->atMoment($this->afterCreation($publisher->created_at, 4), fn () => $updatePublisher($publisher, [
            'taja_percentage_deal' => 65,
            'client_percentage_deal' => 35,
        ], $userId));
    }

    private function changeWriterBank(UpdateWriter $updateWriter, ?int $userId): void
    {
        $writer = Writer::query()->where('name', 'Hafiz Zainuddin bin Samad')->first();

        if ($writer === null) {
            return;
        }

        $index = 0;

        while ($this->bankAt($index)['name'] === $writer->bank_name) {
            $index++;
        }

        $bank = $this->bankAt($index);

        $this->atMoment($this->afterCreation($writer->created_at, 3), fn () => $updateWriter($writer, [
            'bank_name' => $bank['name'],
            'bank_account_number' => $this->accountNumberAt($bank),
            'swift_code' => $bank['swift'],
            'bank_address' => $bank['address'],
            'aliases' => $this->aliasesOf($writer),
        ], $userId));
    }

    private function addWriterPseudonym(UpdateWriter $updateWriter, ?int $userId): void
    {
        $writer = Writer::query()->where('name', 'Nurul Ain binti Razak')->first();

        if ($writer === null) {
            return;
        }

        $this->atMoment($this->afterCreation($writer->created_at, 2), fn () => $updateWriter($writer, [
            'aliases' => [
                ...$this->aliasesOf($writer),
                ['name' => 'Ain Razak', 'ipi_base_number' => fake()->numerify('00#########')],
            ],
        ], $userId));
    }

    /**
     * The pseudonyms a writer has now, in the shape the update action takes.
     *
     * @return list<array{name: string, ipi_base_number: string|null}>
     */
    private function aliasesOf(Writer $writer): array
    {
        return array_values(
            $writer->aliases()
                ->get(['name', 'ipi_base_number'])
                ->map(fn ($alias): array => ['name' => $alias->name, 'ipi_base_number' => $alias->ipi_base_number])
                ->all(),
        );
    }

    /**
     * A moment some days after the record was created, never in the future.
     */
    private function afterCreation(CarbonImmutable $createdAt, int $days): CarbonImmutable
    {
        return $this->notAfterNow($createdAt->addDays($days));
    }
}
