<?php

namespace App\Actions\Lists;

use App\Models\Work;
use App\Models\WorkIsrc;
use App\Models\WorkParty;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The work list: the query the datatable pages through and the CSV export
 * downloads in full, so the screen and the file cannot drift.
 */
class WorkList
{
    /**
     * @param  array{search: string, registration_statuses: list<string>, conflict_statuses: list<string>, languages: list<string>, genres: list<string>, start_date: string, end_date: string, sort: string, direction: string}  $filters
     * @return Builder<Work>
     */
    public function query(array $filters): Builder
    {
        return Work::query()
            ->with('currentParties')
            ->when($filters['search'] !== '', fn (Builder $query) => $query->search($filters['search']))
            ->when($filters['registration_statuses'] !== [], fn (Builder $query) => $query->whereIn('registration_status', $filters['registration_statuses']))
            ->when($filters['conflict_statuses'] !== [], fn (Builder $query) => $query->whereIn('conflict_status', $filters['conflict_statuses']))
            ->when($filters['languages'] !== [], fn (Builder $query) => $query->whereIn('language', $filters['languages']))
            ->when($filters['genres'] !== [], fn (Builder $query) => $query->whereIn('genre', $filters['genres']))
            ->when($filters['start_date'] !== '', fn (Builder $query) => $query->whereDate('registration_date', '>=', $filters['start_date']))
            ->when($filters['end_date'] !== '', fn (Builder $query) => $query->whereDate('registration_date', '<=', $filters['end_date']))
            ->when(
                $filters['direction'] === 'desc',
                fn (Builder $query) => $query->orderByDesc($filters['sort']),
                fn (Builder $query) => $query->orderBy($filters['sort']),
            )
            ->orderBy('id');
    }

    /**
     * Every work column in the order of the import template, with the
     * contributors of today summarised in one cell. The import file has one row
     * per contributor, the export has one row per work, like the table.
     *
     * @return list<string>
     */
    public function csvHeaders(): array
    {
        return [
            'TAP_Number', 'Work_ID', 'English_Work_Title', 'Others_Work_Title', 'ISWC', 'ISRC', 'Status', 'Genre',
            'Language', 'Duration', 'Artist_Name', 'Ori_Work_Title', 'Sub_Title_Work', 'Society_No', 'Contributors',
            'Administration_Start_Date', 'Administration_End_Date', 'Registration_Date', 'Registration_Status',
            'Public_Domain', 'Song_Link', 'Remark', 'Release_Date', 'COMPASS_Work_Internal_No', 'COMPASS_Work_Type',
            'COMPASS_Start_Date', 'WAMI_Work_Internal_No', 'WAMI_Work_Type', 'Conflict_Status', 'Conflict_Notes',
        ];
    }

    /**
     * @param  iterable<Work>  $works
     * @return Generator<int, list<scalar|null>>
     */
    public function csvRows(iterable $works): Generator
    {
        foreach ($works as $work) {
            yield [
                $work->tap_number,
                $work->macp_work_id,
                $work->english_title,
                $work->other_title,
                $work->iswc,
                $work->isrcs->map(fn (WorkIsrc $isrc): string => $isrc->isrc)->implode('|'),
                $work->work_type,
                $work->genre,
                $work->language,
                $work->duration,
                $work->artist_name,
                $work->original_work_title,
                $work->sub_title_work,
                $work->society_no,
                $work->currentParties->map(fn (WorkParty $party): string => $party->contributor_name)->unique()->implode('|'),
                $work->administration_start_date?->toDateString(),
                $work->administration_end_date?->toDateString(),
                $work->registration_date?->toDateString(),
                $work->registration_status->label(),
                $work->is_public_domain,
                $work->song_link,
                $work->remark,
                $work->release_date?->toDateString(),
                $work->compass_work_internal_no,
                $work->compass_work_type,
                $work->compass_start_date?->toDateString(),
                $work->wami_work_internal_no,
                $work->wami_work_type,
                $work->conflict_status->label(),
                $work->conflict_notes,
            ];
        }
    }
}
