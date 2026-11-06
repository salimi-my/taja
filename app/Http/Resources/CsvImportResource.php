<?php

namespace App\Http\Resources;

use App\Enums\CsvImportStatus;
use App\Models\CsvImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CsvImport
 */
class CsvImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $listed = (int) config('imports.max_listed_issues');

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'original_filename' => $this->original_filename,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_processing' => $this->status->isProcessing(),
            'progress' => $this->progress,
            'failure_message' => $this->failure_message,
            'layout_error' => $this->layout_error,
            'has_errors_file' => $this->error_rows > 0 && $this->status !== CsvImportStatus::Cancelled && $this->status !== CsvImportStatus::Failed,
            'total_rows' => $this->total_rows,
            'valid_rows' => $this->valid_rows,
            'error_rows' => $this->error_rows,
            'imported_count' => $this->imported_count,
            'errors' => array_slice($this->errors ?? [], 0, $listed),
            'errors_total' => $this->error_count,
            'warnings' => array_slice($this->warnings ?? [], 0, $listed),
            'warnings_total' => $this->warning_count,
            'uploaded_by' => $this->user->name ?? null,
            'uploaded_by_avatar_url' => $this->user->avatar_url ?? null,
            'imported_at' => $this->imported_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
