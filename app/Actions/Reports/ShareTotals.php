<?php

namespace App\Actions\Reports;

use App\Actions\Works\ValidatePartyShares;
use App\Models\WorkParty;

/**
 * Adds up the shares of a set of party rows per rights type. Sync and lyrics
 * are `null` when no row has a value for them.
 */
class ShareTotals
{
    /**
     * @param  iterable<WorkParty>  $parties
     * @return array{performance: float, mechanical: float, sync: float|null, lyrics: float|null}
     */
    public function __invoke(iterable $parties): array
    {
        $totals = ['performance' => 0.0, 'mechanical' => 0.0, 'sync' => null, 'lyrics' => null];

        foreach ($parties as $party) {
            foreach (ValidatePartyShares::SHARE_COLUMNS as $type => $column) {
                $value = $party->{$column};

                if ($value !== null) {
                    $totals[$type] = round(($totals[$type] ?? 0.0) + (float) $value, 2);
                }
            }
        }

        return $totals;
    }
}
