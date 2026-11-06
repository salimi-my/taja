<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WriterAliasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $writer_id
 * @property string $name
 * @property string|null $ipi_base_number
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Writer $writer
 */
#[Fillable(['name', 'ipi_base_number', 'position'])]
class WriterAlias extends Model
{
    /** @use HasFactory<WriterAliasFactory> */
    use HasFactory;

    /**
     * The writer this pseudonym belongs to.
     *
     * @return BelongsTo<Writer, $this>
     */
    public function writer(): BelongsTo
    {
        return $this->belongsTo(Writer::class);
    }
}
