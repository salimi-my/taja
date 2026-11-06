<?php

namespace App\Actions\Works;

use App\Models\Work;
use App\Models\WorkParty;
use Illuminate\Validation\ValidationException;

/**
 * Checks every row of a work that is still on record (not superseded) against
 * the share and duplicate rules. The party actions call it after writing and
 * inside their transaction, so a failure rolls the change back.
 */
class ValidateWorkParties
{
    public function __construct(private ValidatePartyShares $validateShares) {}

    /**
     * @throws ValidationException With the share sums on `shares` and a duplicate on `party`.
     */
    public function __invoke(Work $work): void
    {
        $rows = $work->parties()
            ->whereNull('superseded_at')
            ->get()
            ->map(fn (WorkParty $party): array => [
                'writer_id' => $party->writer_id,
                'publisher_id' => $party->publisher_id,
                'role' => $party->role,
                'performance_share' => $party->performance_share,
                'mechanical_share' => $party->mechanical_share,
                'sync_share' => $party->sync_share,
                'lyrics_share' => $party->lyrics_share,
                'territories_controlled' => $party->territories_controlled,
                'effective_start_date' => $party->effective_start_date,
                'effective_end_date' => $party->effective_end_date,
            ])
            ->all();

        $rows = array_values($rows);

        $errors = [];

        foreach (($this->validateShares)($rows) as $error) {
            $errors[$error['field'] === 'parties' ? 'shares' : 'party'][] = $error['message'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
