<?php

namespace Tests\Feature;

use App\Jobs\ImportLearners;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ImportBatch;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\CourseCompleted;
use Fieldnotes\CsvKit\Reader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademyTest extends TestCase
{
    use RefreshDatabase;

    private function course(bool $published = true, ?User $owner = null): Course
    {
        $owner ??= User::factory()->create(['is_instructor' => true]);

        return Course::factory()->create(['user_id' => $owner->id, 'published' => $published]);
    }

    private function lesson(Course $course, int $position = 1): Lesson
    {
        return Lesson::factory()->create(['course_id' => $course->id, 'position' => $position]);
    }

    public function test_catalog_filters_courses_and_hides_drafts(): void
    {
        $course = $this->course();
        $this->lesson($course);
        $draft = $this->course(false);
        $this->get('/')->assertOk()->assertSee($course->title)->assertDontSee($draft->title);
        $this->get('/?q=zzzzzzzz')->assertSee('No courses found.');
        $this->get('/courses/'.$draft->id)->assertNotFound();
    }

    public function test_registration_cannot_grant_instructor_privileges(): void
    {
        $this->post('/register', ['name' => 'New Learner', 'email' => 'new@example.test', 'password' => 'a-long-demo-password', 'password_confirmation' => 'a-long-demo-password', 'is_instructor' => true])->assertRedirect('/learning');
        $this->assertFalse(User::where('email', 'new@example.test')->firstOrFail()->is_instructor);
    }

    public function test_login_logout_and_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'correct-password'])->assertRedirect('/learning');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_enrollment_is_idempotent_and_requires_authentication(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course);
        $user = User::factory()->create();
        $this->post(route('enroll', $course))->assertRedirect('/login');
        $this->actingAs($user)->post(route('enroll', $course))->assertRedirect(route('lesson', $lesson));
        $this->post(route('enroll', $course))->assertRedirect();
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_non_enrolled_users_cannot_read_or_answer_lessons(): void
    {
        $lesson = $this->lesson($this->course());
        $this->actingAs(User::factory()->create())->get(route('lesson', $lesson))->assertNotFound();
        $this->post(route('answer', $lesson), ['answer' => 1])->assertNotFound();
    }

    public function test_answers_are_graded_on_server_and_completion_is_idempotent(): void
    {
        Notification::fake();
        $course = $this->course();
        $lesson = $this->lesson($course);
        $second = $this->lesson($course, 2);
        $user = User::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
        $this->actingAs($user)->post(route('answer', $lesson), ['answer' => 0, 'passed' => true])->assertSessionHas('feedback');
        $this->assertDatabaseCount('lesson_completions', 0);
        $this->post(route('answer', $lesson), ['answer' => 1])->assertSessionHas('success');
        $this->post(route('answer', $lesson), ['answer' => 1]);
        $this->assertDatabaseCount('lesson_completions', 1);
        $this->assertNull($enrollment->fresh()->certificate);
        $this->post(route('answer', $second), ['answer' => 1]);
        $this->assertNotNull($enrollment->fresh()->certificate);
        $certificate = $enrollment->fresh()->certificate;
        $this->post(route('answer', $second), ['answer' => 1]);
        $this->assertSame($certificate, $enrollment->fresh()->certificate);
        Notification::assertSentToTimes($user, CourseCompleted::class, 1);
        $this->get(route('certificate', $enrollment))->assertOk()->assertSee($user->name);
    }

    public function test_invalid_quiz_choice_does_not_write_an_attempt(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course);
        $user = User::factory()->create();
        Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
        $this->actingAs($user)->post(route('answer', $lesson), ['answer' => 50])->assertSessionHasErrors('answer');
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_certificate_is_private_and_requires_completion(): void
    {
        $user = User::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $this->course()->id]);
        $this->actingAs($user)->get(route('certificate', $enrollment))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('certificate', $enrollment))->assertForbidden();
    }

    public function test_dashboard_does_not_leak_another_learners_enrollment(): void
    {
        $course = $this->course();
        Enrollment::factory()->create(['course_id' => $course->id]);
        $this->actingAs(User::factory()->create())->get('/learning')->assertOk()->assertDontSee($course->title);
    }

    public function test_instructor_ownership_is_enforced(): void
    {
        $course = $this->course();
        $this->actingAs(User::factory()->create())->get('/studio')->assertForbidden();
        $other = User::factory()->create(['is_instructor' => true]);
        $this->actingAs($other)->get(route('studio.edit', $course))->assertForbidden();
        $this->post(route('studio.publish', $course))->assertForbidden();
        $this->get(route('imports', $course))->assertForbidden();
    }

    public function test_instructor_can_create_add_lesson_and_publish(): void
    {
        $user = User::factory()->create(['is_instructor' => true]);
        $this->actingAs($user)->post(route('studio.store'), ['title' => 'Test course', 'description' => 'A thoughtful course.', 'category' => 'Writing', 'level' => 'Beginner'])->assertRedirect();
        $course = Course::firstOrFail();
        $this->post(route('studio.publish', $course))->assertStatus(422);
        $data = ['title' => 'Lesson one', 'body' => 'A lesson body.', 'minutes' => 5, 'question' => 'Which answer?', 'options' => ['One', 'Two', 'Three'], 'correct' => 1];
        $this->post(route('studio.lesson', $course), $data)->assertRedirect();
        $this->post(route('studio.publish', $course))->assertRedirect();
        $this->assertTrue($course->fresh()->published);
        $this->post(route('studio.lesson', $course), $data)->assertStatus(409);
    }

    public function test_lesson_renders_sanitized_markdown_and_no_correct_answer_metadata(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course);
        $lesson->update(['body' => "<script>alert('xss')</script>\n\n**Safe**"]);
        $user = User::factory()->create();
        Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
        $this->actingAs($user)->get(route('lesson', $lesson))->assertOk()->assertDontSee('<script>', false)->assertSee('<strong>Safe</strong>', false)->assertDontSee('"correct"', false);
    }

    public function test_import_preview_rejects_unknown_learners_without_enrolling(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['is_instructor' => true]);
        $course = $this->course(true, $owner);
        $this->lesson($course);
        $this->actingAs($owner)->post(route('imports.preview', $course), ['csv' => UploadedFile::fake()->createWithContent('learners.csv', "email\nmissing@example.test\n")])->assertRedirect();
        $batch = ImportBatch::firstOrFail();
        $this->assertNotEmpty($batch->errors);
        $this->assertDatabaseCount('enrollments', 0);
        $this->post(route('imports.start', $batch))->assertStatus(409);
    }

    public function test_valid_preview_queues_import_and_duplicate_start_is_blocked(): void
    {
        Storage::fake('local');
        Queue::fake();
        $owner = User::factory()->create(['is_instructor' => true]);
        $learner = User::factory()->create();
        $course = $this->course(true, $owner);
        $this->lesson($course);
        $this->actingAs($owner)->post(route('imports.preview', $course), ['csv' => UploadedFile::fake()->createWithContent('learners.csv', "email\n{$learner->email}\n")])->assertRedirect();
        $batch = ImportBatch::firstOrFail();
        $this->assertSame([], $batch->errors);
        $this->post(route('imports.start', $batch))->assertRedirect();
        Queue::assertPushed(ImportLearners::class);
        $this->post(route('imports.start', $batch))->assertStatus(409);
    }

    public function test_import_resumes_and_undo_preserves_existing_and_used_enrollments(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['is_instructor' => true]);
        $course = $this->course(true, $owner);
        $lesson = $this->lesson($course);
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        $existing = Enrollment::factory()->create(['user_id' => $a->id, 'course_id' => $course->id]);
        Storage::put('imports/test.csv', "email\n{$a->email}\n{$b->email}\n{$c->email}\n{$b->email}\n");
        $batch = ImportBatch::factory()->create(['user_id' => $owner->id, 'course_id' => $course->id, 'path' => 'imports/test.csv', 'status' => 'queued', 'cursor' => 1, 'total' => 4]);
        $job = new ImportLearners($batch->id);
        $job->handle(new Reader);
        $job->handle(new Reader);
        $this->assertSame(4, $batch->fresh()->cursor);
        $this->assertSame('completed', $batch->fresh()->status);
        $this->assertDatabaseCount('enrollments', 3);
        $used = Enrollment::where('user_id', $b->id)->firstOrFail();
        $used->attempts()->create(['lesson_id' => $lesson->id, 'answer' => 0, 'passed' => false]);
        $this->actingAs($owner)->post(route('imports.undo', $batch))->assertRedirect();
        $this->assertModelExists($existing);
        $this->assertModelExists($used);
        $this->assertDatabaseMissing('enrollments', ['user_id' => $c->id]);
        $this->post(route('imports.start', $batch))->assertStatus(409);
    }

    public function test_other_instructor_cannot_start_or_undo_import(): void
    {
        $batch = ImportBatch::factory()->create(['status' => 'completed']);
        $other = User::factory()->create(['is_instructor' => true]);
        $this->actingAs($other)->post(route('imports.start', $batch))->assertForbidden();
        $this->post(route('imports.undo', $batch))->assertForbidden();
    }

    public function test_demo_pages_render(): void
    {
        $this->seed();
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $owner = User::where('email', 'instructor@example.test')->firstOrFail();
        $course = Course::firstOrFail();
        $this->actingAs($owner)->get('/studio')->assertOk();
        $this->get(route('studio.edit', $course))->assertOk();
        $this->get(route('imports', $course))->assertOk();
        $this->get(route('course', $course))->assertOk();
        $this->actingAs(User::where('email', 'learner@example.test')->firstOrFail())->get('/learning')->assertOk();
        $this->get(route('lesson', $course->lessons->first()))->assertOk();
    }
}
