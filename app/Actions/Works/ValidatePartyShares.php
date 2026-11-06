<?php

namespace App\Actions\Works;

use App\Enums\PartyRole;
use App\Rules\TerritoryList;
use Carbon\CarbonImmutable;

/**
 * Checks the parties of one work against the share rules (business rules 4
 * and 5): per rights type the shares in effect at the same time must not
 * exceed 100, and the same party cannot be listed twice with the same role for
 * overlapping dates and territories.
 *
 * Each party is an array with `writer_id`, `publisher_id`, `role`
 * (a `PartyRole`), the four `*_share` values, `territories_controlled`,
 * `effective_start_date` and `effective_end_date` (`null` = still in effect).
 * The function is pure, so the form requests and the CSV import both use it.
 *
 * @phpstan-type PartyRow array{
 *     writer_id?: int|null,
 *     publisher_id?: int|null,
 *     role: PartyRole,
 *     performance_share?: int|float|string|null,
 *     mechanical_share?: int|float|string|null,
 *     sync_share?: int|float|string|null,
 *     lyrics_share?: int|float|string|null,
 *     territories_controlled?: list<string>,
 *     effective_start_date: CarbonImmutable|string,
 *     effective_end_date?: CarbonImmutable|string|null,
 * }
 */
class ValidatePartyShares
{
    /**
     * The share columns checked, keyed by the rights type label.
     *
     * @var array<string, string>
     */
    public const SHARE_COLUMNS = [
        'performance' => 'performance_share',
        'mechanical' => 'mechanical_share',
        'sync' => 'sync_share',
        'lyrics' => 'lyrics_share',
    ];

    /**
     * @param  list<PartyRow>  $parties
     * @return list<array{field: string, message: string}> The errors, keyed by the form field they belong to.
     */
    public function __invoke(array $parties): array
    {
        return [...$this->shareSumErrors($parties), ...$this->duplicateErrors($parties)];
    }

    /**
     * The highest total of each rights type over the life of the work.
     * A total can only grow on the day a row starts, so those are the days checked.
     *
     * @param  list<PartyRow>  $parties
     * @return list<array{field: string, message: string}>
     */
    private function shareSumErrors(array $parties): array
    {
        $errors = [];

        foreach (self::SHARE_COLUMNS as $label => $column) {
            $rows = [];

            foreach ($parties as $party) {
                $value = $party[$column] ?? null;

                if ($value !== null && $value !== '') {
                    $rows[] = ['party' => $party, 'share' => (float) $value];
                }
            }

            $highest = 0.0;

            foreach ($rows as $row) {
                $day = $this->start($row['party']);
                $total = 0.0;

                foreach ($rows as $other) {
                    if ($this->coversDay($other['party'], $day)) {
                        $total += $other['share'];
                    }
                }

                $highest = max($highest, $total);
            }

            if ($highest > 100.0 + 0.001) {
                $errors[] = [
                    'field' => 'parties',
                    'message' => sprintf(
                        'The %s shares add up to %s%%, which is more than 100%%.',
                        $label,
                        rtrim(rtrim(number_format($highest, 2, '.', ''), '0'), '.'),
                    ),
                ];
            }
        }

        return $errors;
    }

    /**
     * @param  list<PartyRow>  $parties
     * @return list<array{field: string, message: string}>
     */
    private function duplicateErrors(array $parties): array
    {
        $errors = [];

        foreach ($parties as $index => $party) {
            for ($earlier = 0; $earlier < $index; $earlier++) {
                if (! $this->isDuplicate($parties[$earlier], $party)) {
                    continue;
                }

                $field = ($party['writer_id'] ?? null) !== null ? 'writer_id' : 'publisher_id';

                $errors[] = [
                    'field' => "parties.{$index}.{$field}",
                    'message' => 'This party is already listed with the same role for overlapping dates and territories.',
                ];

                break;
            }
        }

        return $errors;
    }

    /**
     * @param  PartyRow  $first
     * @param  PartyRow  $second
     */
    private function isDuplicate(array $first, array $second): bool
    {
        $firstWriter = $first['writer_id'] ?? null;
        $firstPublisher = $first['publisher_id'] ?? null;

        $sameParty = $firstWriter !== null
            ? $firstWriter === ($second['writer_id'] ?? null)
            : $firstPublisher !== null && $firstPublisher === ($second['publisher_id'] ?? null);

        return $sameParty
            && $first['role'] === $second['role']
            && $this->periodsOverlap($first, $second)
            && $this->territoriesOverlap($first['territories_controlled'] ?? [], $second['territories_controlled'] ?? []);
    }

    /**
     * @param  PartyRow  $row
     */
    private function start(array $row): CarbonImmutable
    {
        return CarbonImmutable::parse($row['effective_start_date'])->startOfDay();
    }

    /**
     * @param  PartyRow  $row
     */
    private function end(array $row): ?CarbonImmutable
    {
        $end = $row['effective_end_date'] ?? null;

        return $end === null ? null : CarbonImmutable::parse($end)->startOfDay();
    }

    /**
     * @param  PartyRow  $row
     */
    private function coversDay(array $row, CarbonImmutable $day): bool
    {
        $end = $this->end($row);

        return $this->start($row)->lte($day) && ($end === null || $end->gte($day));
    }

    /**
     * @param  PartyRow  $first
     * @param  PartyRow  $second
     */
    private function periodsOverlap(array $first, array $second): bool
    {
        $firstEnd = $this->end($first);
        $secondEnd = $this->end($second);

        return ($firstEnd === null || $firstEnd->gte($this->start($second)))
            && ($secondEnd === null || $secondEnd->gte($this->start($first)));
    }

    /**
     * Worldwide overlaps with every territory.
     *
     * @param  list<string>  $first
     * @param  list<string>  $second
     */
    private function territoriesOverlap(array $first, array $second): bool
    {
        if (in_array(TerritoryList::WORLDWIDE, $first, true) || in_array(TerritoryList::WORLDWIDE, $second, true)) {
            return true;
        }

        return array_intersect($first, $second) !== [];
    }
}
