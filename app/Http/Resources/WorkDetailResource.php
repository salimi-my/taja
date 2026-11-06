<?php

namespace App\Http\Resources;

use App\Models\Work;
use App\Models\WorkIsrc;
use Illuminate\Http\Request;

/**
 * @mixin Work
 */
class WorkDetailResource extends WorkResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'duration' => $this->duration,
            'artist_name' => $this->artist_name,
            'original_work_title' => $this->original_work_title,
            'sub_title_work' => $this->sub_title_work,
            'society_no' => $this->society_no,
            'administration_start_date' => $this->administration_start_date?->toDateString(),
            'administration_end_date' => $this->administration_end_date?->toDateString(),
            'song_link' => $this->song_link,
            'remark' => $this->remark,
            'release_date' => $this->release_date?->toDateString(),
            'compass_work_internal_no' => $this->compass_work_internal_no,
            'compass_work_type' => $this->compass_work_type,
            'compass_start_date' => $this->compass_start_date?->toDateString(),
            'wami_work_internal_no' => $this->wami_work_internal_no,
            'wami_work_type' => $this->wami_work_type,
            'conflict_notes' => $this->conflict_notes,
            'isrcs' => $this->isrcs->map(fn (WorkIsrc $isrc): string => $isrc->isrc)->values(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
