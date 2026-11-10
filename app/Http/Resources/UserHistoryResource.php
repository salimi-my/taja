<?php

namespace App\Http\Resources;

use App\Actions\Users\SummarizeUserHistory;
use App\Models\UserHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserHistory
 */
class UserHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'change_type' => $this->change_type->value,
            'change_type_label' => $this->change_type->label(),
            'summary' => app(SummarizeUserHistory::class)($this->resource),
            'recorded_at' => $this->recorded_at->toIso8601String(),
            'actor_name' => $this->actor->name ?? $this->actor_name,
            'actor_avatar_url' => $this->actor?->avatar_url,
            'remarks' => $this->remarks,
            'subject' => [
                'id' => $this->subject_id,
                'name' => $this->subject_name,
                'email' => $this->subject_email,
            ],
        ];
    }
}
