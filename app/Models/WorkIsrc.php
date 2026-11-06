<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WorkIsrcFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $work_id
 * @property string $isrc
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Work $work
 */
#[Fillable(['isrc'])]
class WorkIsrc extends Model
{
    /** @use HasFactory<WorkIsrcFactory> */
    use HasFactory;

    /**
     * The work this ISRC belongs to.
     *
     * @return BelongsTo<Work, $this>
     */
    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
