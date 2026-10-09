<?php

namespace App\Jobs;

use App\Models\Enrollment;
use App\Models\ImportBatch;
use App\Models\User;
use Fieldnotes\CsvKit\Reader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportLearners implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $batchId) {}

    public function handle(Reader $reader): void
    {
        $batch = ImportBatch::findOrFail($this->batchId);
        if (! in_array($batch->status, ['queued', 'running'])) {
            return;
        }
        foreach ($reader->rows(Storage::path($batch->path), $batch->cursor) as $row => $data) {
            $continue = DB::transaction(function () use ($row, $data) {
                $locked = ImportBatch::whereKey($this->batchId)->lockForUpdate()->firstOrFail();
                if (! in_array($locked->status, ['queued', 'running'])) {
                    return false;
                }
                if ($row <= $locked->cursor) {
                    return true;
                }
                $user = User::where('email', $data['email'])->firstOrFail();
                Enrollment::firstOrCreate(['user_id' => $user->id, 'course_id' => $locked->course_id], ['import_batch_id' => $locked->id]);
                $locked->update(['cursor' => $row, 'status' => 'running']);

                return true;
            }, 3);
            if (! $continue) {
                return;
            }
        }
        ImportBatch::whereKey($this->batchId)->whereIn('status', ['queued', 'running'])->update(['status' => 'completed']);
    }

    public function failed(?Throwable $e): void
    {
        ImportBatch::whereKey($this->batchId)->whereIn('status', ['queued', 'running'])->update(['status' => 'failed']);
    }
}
