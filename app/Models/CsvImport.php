<?php

namespace App\Models;

use App\Enums\CsvImportStatus;
use App\Enums\CsvImportType;
use Carbon\CarbonImmutable;
use Database\Factories\CsvImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One uploaded CSV file: what was found when it was previewed and what came
 * of confirming it.
 *
 * @property int $id
 * @property int $user_id
 * @property CsvImportType $type
 * @property string $original_filename
 * @property string $stored_path
 * @property CsvImportStatus $status
 * @property int $progress Percent of the file read by the current pass, 0 to 100.
 * @property int $total_rows
 * @property int $valid_rows
 * @property int $error_rows
 * @property int $error_count The full number of errors; `errors` only keeps the first ones.
 * @property int $warning_count The full number of warnings; `warnings` only keeps the first ones.
 * @property list<array{line: int, column: string|null, message: string}>|null $warnings
 * @property list<array{line: int, column: string|null, message: string}>|null $errors
 * @property int $imported_count
 * @property CarbonImmutable|null $imported_at
 * @property string|null $failure_message
 * @property string|null $layout_error A problem with the layout of the whole file that stops it being confirmed.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'type',
    'original_filename',
    'stored_path',
    'status',
    'progress',
    'total_rows',
    'valid_rows',
    'error_rows',
    'error_count',
    'warning_count',
    'warnings',
    'errors',
    'imported_count',
    'imported_at',
    'failure_message',
    'layout_error',
])]
class CsvImport extends Model
{
    /** @use HasFactory<CsvImportFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CsvImportType::class,
            'status' => CsvImportStatus::class,
            'warnings' => 'array',
            'errors' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    /**
     * The user who uploaded the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the file is validated and waiting to be confirmed or cancelled.
     */
    public function isPreviewed(): bool
    {
        return $this->status === CsvImportStatus::Previewed;
    }

    /**
     * Where the file of skipped rows (with an `Error` column) is stored, on the `local` disk.
     */
    public function errorsFilePath(): string
    {
        return "imports/errors/{$this->id}.csv";
    }
}
