<?php

namespace App\Enums;

use App\Concerns\MatchesLooseValues;

enum RightsChangeType: string
{
    use MatchesLooseValues;

    case WorkCreated = 'work_created';
    case WorkUpdated = 'work_updated';
    case PartyAdded = 'party_added';
    case PartyRemoved = 'party_removed';
    case ShareUpdated = 'share_updated';
    case TerritoryChanged = 'territory_changed';
    case DatesChanged = 'dates_changed';
    case Correction = 'correction';
    case Imported = 'imported';

    /**
     * Get the human-readable label for the case.
     */
    public function label(): string
    {
        return match ($this) {
            self::WorkCreated => 'Work created',
            self::WorkUpdated => 'Work updated',
            self::PartyAdded => 'Party added',
            self::PartyRemoved => 'Party removed',
            self::ShareUpdated => 'Share updated',
            self::TerritoryChanged => 'Territory changed',
            self::DatesChanged => 'Dates changed',
            self::Correction => 'Correction',
            self::Imported => 'Imported',
        };
    }

    /**
     * Get every case as a value/label pair, e.g. for select inputs and filters.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
