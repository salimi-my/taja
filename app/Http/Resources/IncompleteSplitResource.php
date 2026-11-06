<?php

namespace App\Http\Resources;

use App\Actions\Reports\IncompleteSplitReport;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A work of the incomplete split report with its totals and the reasons.
 *
 * @mixin Work
 */
class IncompleteSplitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $report = app(IncompleteSplitReport::class);

        return [
            'id' => $this->id,
            'tap_number' => $this->tap_number,
            'macp_work_id' => $this->macp_work_id,
            'title' => $this->displayTitle(),
            'registration_status' => $this->registration_status->value,
            'registration_status_label' => $this->registration_status->label(),
            'parties_count' => (int) $this->getAttribute('current_parties_count'),
            'totals' => [
                'performance' => $report->total($this->resource, 'performance'),
                'mechanical' => $report->total($this->resource, 'mechanical'),
                'sync' => $report->total($this->resource, 'sync'),
                'lyrics' => $report->total($this->resource, 'lyrics'),
            ],
            'reasons' => $report->reasons($this->resource),
        ];
    }
}
