<?php

namespace App\Jobs;

use App\Actions\Imports\InvalidImportFile;
use App\Actions\Imports\ProcessCsvImport;
use App\Enums\CsvImportStatus;
use App\Models\CsvImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Validates (`commit: false`) or imports (`commit: true`) the stored file of a
 * CSV import. A large file takes a long time, so it runs on the queue and the
 * page follows its progress.
 */
class ProcessCsvImportJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(public CsvImport $csvImport, public bool $commit)
    {
        $this->timeout = (int) config('imports.job_timeout');
    }

    public function handle(ProcessCsvImport $process): void
    {
        try {
            $process($this->csvImport, $this->commit);
        } catch (InvalidImportFile $exception) {
            $this->markFailed($exception->getMessage());
        }
    }

    /**
     * Called when the job crashed or timed out.
     */
    public function failed(Throwable $exception): void
    {
        $this->markFailed($this->commit
            ? 'The import stopped unexpectedly. Some rows may already have been imported.'
            : 'The file could not be validated. Upload it again.');
    }

    private function markFailed(string $message): void
    {
        CsvImport::query()
            ->whereKey($this->csvImport->id)
            ->whereIn('status', [CsvImportStatus::Validating, CsvImportStatus::Importing])
            ->update(['status' => CsvImportStatus::Failed, 'failure_message' => $message]);
    }
}
