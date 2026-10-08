<?php

namespace App\Http\Controllers;

use App\Jobs\ImportLearners;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ImportBatch;
use App\Models\User;
use Fieldnotes\CsvKit\Reader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ImportController extends Controller
{
    public function index(Course $course): View
    {
        Gate::authorize('update', $course);

        return view('imports', ['course' => $course, 'batches' => ImportBatch::where('course_id', $course->id)->latest()->get()]);
    }

    public function preview(Request $request, Course $course, Reader $reader): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($course->published, 422, 'Publish the course first.');
        $request->validate(['csv' => 'required|file|max:1024|mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel']);
        $path = $request->file('csv')->store('imports');
        $errors = [];
        $total = 0;
        try {
            foreach ($reader->rows(Storage::path($path)) as $row => $data) {
                $total = $row;
                if ($row > 5000) {
                    $errors[] = 'Maximum 5,000 rows per import.';
                    break;
                }
                if (! User::where('email', $data['email'])->exists()) {
                    $errors[] = 'Row '.$row.': no registered learner with that email.';
                }
                if (count($errors) >= 25) {
                    $errors[] = 'Preview stopped after 25 errors. Fix these and upload again.';
                    break;
                }
            }
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }
        if ($total === 0 && ! $errors) {
            $errors[] = 'Add at least one learner.';
        }
        ImportBatch::create(['course_id' => $course->id, 'user_id' => auth()->id(), 'path' => $path, 'errors' => $errors, 'total' => $total]);

        return back()->with('success', $errors ? 'Preview found issues. No learners have been enrolled.' : 'Preview ready. Review the row count, then start the import.');
    }

    public function start(ImportBatch $batch): RedirectResponse
    {
        Gate::authorize('update', $batch->course);
        DB::transaction(function () use ($batch) {
            $batch = ImportBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($batch->status, ['preview', 'failed']) && ! $batch->errors, 409);
            $batch->update(['status' => 'queued']);
            ImportLearners::dispatch($batch->id)->afterCommit();
        });

        return back()->with('success', 'Import queued. Refresh this page to see progress.');
    }

    public function undo(ImportBatch $batch): RedirectResponse
    {
        Gate::authorize('update', $batch->course);
        $removed = DB::transaction(function () use ($batch) {
            $batch = ImportBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($batch->status, ['completed', 'failed']), 409);
            $count = 0;
            foreach (Enrollment::where('import_batch_id', $batch->id)->orderBy('id')->lockForUpdate()->get() as $enrollment) {
                // Keep any enrollment that a learner has used, including unsuccessful quiz attempts.
                if (! $enrollment->completions()->exists() && ! $enrollment->attempts()->exists()) {
                    $enrollment->delete();
                    $count++;
                }
            }
            $batch->update(['status' => 'undone']);

            return $count;
        }, 3);

        return back()->with('success', "Removed $removed unused enrollments. Existing enrollments and learning activity were preserved.");
    }
}
