<?php

namespace App\Concerns;

use App\Enums\PartyRole;
use App\Rules\Percent;
use App\Rules\TerritoryList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait WorkPartyValidationRules
{
    /**
     * Get the validation rules of the rights fields of one party, without any
     * key prefix. The create form repeats them under `parties.*`.
     *
     * @return array<string, array<int, ValidationRule|Closure|array<mixed>|string>>
     */
    public static function partyFieldRules(): array
    {
        return [
            'role' => ['required', Rule::enum(PartyRole::class)],
            'performance_share' => ['required', new Percent],
            'mechanical_share' => ['required', new Percent],
            'sync_share' => ['nullable', new Percent],
            'lyrics_share' => ['nullable', new Percent],
            'is_controlled' => ['required', 'boolean'],
            'territories_controlled' => ['required', new TerritoryList(mustExist: true)],
            'territory_exclusions' => ['nullable', new TerritoryList(mustExist: true)],
        ];
    }

    /**
     * Get the validation rules that pick the writer or publisher of a party.
     *
     * @return array<string, array<int, ValidationRule|Closure|array<mixed>|string>>
     */
    protected function partyIdentityRules(): array
    {
        $existing = fn (string $table) => Rule::exists($table, 'id')->whereNull('deleted_at');

        return [
            'writer_id' => ['nullable', 'integer', $existing('writers')],
            'publisher_id' => ['nullable', 'integer', $existing('publishers')],
            'contributor_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Turn the territory text of a party into lists (or `null` when blank).
     *
     * @param  array<string, mixed>  $party
     * @return array<string, mixed>
     */
    protected function parsePartyTerritories(array $party): array
    {
        foreach (['territories_controlled', 'territory_exclusions'] as $field) {
            if (array_key_exists($field, $party)) {
                $list = TerritoryList::parse($party[$field]);
                $party[$field] = $list === [] ? null : $list;
            }
        }

        return $party;
    }

    /**
     * A writer role needs a writer and a publisher role needs a publisher.
     *
     * @param  array<string, mixed>  $party
     * @param  string  $prefix  The key prefix of the party in the request, e.g. `parties.0.`.
     * @return list<array{field: string, message: string}>
     */
    protected function partyTypeErrors(array $party, string $prefix = ''): array
    {
        $isWriterRole = PartyRole::from($party['role'])->isWriterRole();
        $field = $isWriterRole ? 'writer_id' : 'publisher_id';
        $other = $isWriterRole ? 'publisher_id' : 'writer_id';

        if (blank($party[$field] ?? null)) {
            return [[
                'field' => $prefix.$field,
                'message' => $isWriterRole ? 'Pick a writer for this role.' : 'Pick a publisher for this role.',
            ]];
        }

        if (filled($party[$other] ?? null)) {
            return [[
                'field' => $prefix.$other,
                'message' => $isWriterRole ? 'A writer role cannot have a publisher.' : 'A publisher role cannot have a writer.',
            ]];
        }

        return [];
    }
}
