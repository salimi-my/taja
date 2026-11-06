<?php

namespace App\Http\Resources;

use App\Enums\CsvImportStatus;
use App\Models\CsvImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * One import in the import history list. It leaves out the error and warning
 * details, which only the import's own page needs.
 *
 * @mixin CsvImport
 */
class CsvImportListResource extends JsonResource
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
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'original_filename' => $this->original_filename,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_processing' => $this->status->isProcessing(),
            'progress' => $this->progress,
            'total_rows' => $this->total_rows,
            'valid_rows' => $this->valid_rows,
            'error_rows' => $this->error_rows,
            'imported_count' => $this->imported_count,
            'has_errors_file' => $this->error_rows > 0 && $this->status !== CsvImportStatus::Cancelled && $this->status !== CsvImportStatus::Failed,
            'has_original_file' => Storage::disk('local')->exists($this->stored_path),
            'can_delete' => ! $this->status->isProcessing(),
            'uploaded_by' => $this->user->name ?? null,
            'uploaded_by_avatar_url' => $this->user?->avatar_url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
