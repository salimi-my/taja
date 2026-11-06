<?php

namespace App\Actions\Works;

use App\Models\Publisher;
use App\Models\Writer;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Decides the effective start date of a party that is created together with a
 * work (form or import). Keeping the rule in one place lets the client change
 * it later (decision D14).
 */
class ResolveInitialEffectiveDate
{
    /**
     * The first available of: the work's administration start date, the
     * party's agreement start date, and today.
     */
    public function __invoke(
        CarbonInterface|string|null $administrationStartDate,
        Writer|Publisher|null $party = null,
    ): CarbonImmutable {
        if ($administrationStartDate !== null) {
            return CarbonImmutable::parse($administrationStartDate)->startOfDay();
        }

        if ($party !== null) {
            return CarbonImmutable::parse($party->agreement_start_date)->startOfDay();
        }

        return CarbonImmutable::today();
    }
}
