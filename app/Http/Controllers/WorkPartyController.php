<?php

namespace App\Http\Controllers;

use App\Actions\Works\AddParty;
use App\Actions\Works\ChangePartyFromDate;
use App\Actions\Works\CorrectParty;
use App\Actions\Works\RemoveParty;
use App\Http\Requests\Works\ChangeWorkPartyRequest;
use App\Http\Requests\Works\CorrectWorkPartyRequest;
use App\Http\Requests\Works\RemoveWorkPartyRequest;
use App\Http\Requests\Works\StoreWorkPartyRequest;
use App\Models\Work;
use App\Models\WorkParty;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Adds, changes, corrects and removes the parties of an existing work. Rows
 * are never overwritten, and every action logs its change in the rights
 * history.
 */
class WorkPartyController extends Controller
{
    /**
     * Add a party to the work from the given date.
     */
    public function store(StoreWorkPartyRequest $request, Work $work, AddParty $addParty): RedirectResponse
    {
        $addParty($work, $request->safe()->except('remarks'), $request->user()->id, remarks: $request->validated('remarks'));

        return $this->back($work, __('Party added.'));
    }

    /**
     * Change the party's rights from a date: the open row ends the day before.
     */
    public function change(ChangeWorkPartyRequest $request, Work $work, WorkParty $party, ChangePartyFromDate $changeParty): RedirectResponse
    {
        $changeParty($party, $request->validated(), $request->user()->id);

        return $this->back($work, __('Party changed.'));
    }

    /**
     * Correct a row that was entered wrongly. The old row stays in the history.
     */
    public function correct(CorrectWorkPartyRequest $request, Work $work, WorkParty $party, CorrectParty $correctParty): RedirectResponse
    {
        $correctParty($party, $request->validated(), $request->user()->id);

        return $this->back($work, __('Party corrected.'));
    }

    /**
     * Take the party off the work from a date.
     */
    public function destroy(RemoveWorkPartyRequest $request, Work $work, WorkParty $party, RemoveParty $removeParty): RedirectResponse
    {
        $removeParty($party, $request->validated(), $request->user()->id);

        return $this->back($work, __('Party removed.'));
    }

    private function back(Work $work, string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return redirect()->route('works.show', $work);
    }
}
