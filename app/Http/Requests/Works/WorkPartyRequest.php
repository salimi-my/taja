<?php

namespace App\Http\Requests\Works;

use App\Concerns\WorkPartyValidationRules;
use App\Enums\PartyRole;
use App\Models\Work;
use App\Models\WorkParty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The shared part of the requests that add, change, correct or remove a party
 * of an existing work. Only users who may update the work can make them.
 */
abstract class WorkPartyRequest extends FormRequest
{
    use WorkPartyValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('work'));
    }

    /**
     * The work the request is made on.
     */
    protected function work(): Work
    {
        /** @var Work $work */
        $work = $this->route('work');

        return $work;
    }

    /**
     * The party row the request is made on (not set when adding a party).
     */
    protected function party(): WorkParty
    {
        /** @var WorkParty $party */
        $party = $this->route('party');

        return $party;
    }

    /**
     * Turn the territory text into lists.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(array_intersect_key(
            $this->parsePartyTerritories($this->all()),
            array_flip(['territories_controlled', 'territory_exclusions']),
        ));
    }

    /**
     * A row can change its role, but a writer row stays a writer and a
     * publisher row stays a publisher.
     */
    protected function checkRoleMatchesParty(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $isWriterRole = PartyRole::from($this->input('role'))->isWriterRole();

        if ($isWriterRole !== $this->party()->isWriter()) {
            $validator->errors()->add(
                'role',
                $this->party()->isWriter()
                    ? 'A writer row needs a writer role.'
                    : 'A publisher row needs a publisher role.',
            );
        }
    }
}
