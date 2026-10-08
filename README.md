# Fieldnotes Academy

A working Laravel learning platform by [Cynthia Owolabi](https://adebolaowolabi32.github.io/). Browse courses, enroll, read lessons, answer knowledge checks, and earn a personal completion certificate. Instructors create and publish courses and import registered learners through a preview-first, resumable queue workflow.

## Run locally

Requires PHP 8.4+, Composer 2, PDO SQLite, mbstring, DOM/XML and standard Laravel extensions. No Node build is required: the interface uses Blade and hand-written CSS. Fonts fall back to system fonts when offline.

```sh
composer run setup
php artisan serve
# In a second terminal, process imports and completion notifications:
php artisan queue:work --tries=3 --timeout=120
```

Open http://127.0.0.1:8000. Setup uses SQLite and a database-backed queue. The default mail driver writes to `storage/logs/laravel.log`; it does not send real email. Demo seeding is allowed only in local/testing environments.

| Role | Email | Password |
| --- | --- | --- |
| Learner | learner@example.test | fieldnotes-demo-2026 |
| Instructor | instructor@example.test | fieldnotes-demo-2026 |
| Second learner | maya@example.test | fieldnotes-demo-2026 |

These are disposable local demo accounts. Registration always creates a learner; instructor status cannot be submitted through registration.

## Try the full journey

1. Sign in as the learner. Open **My learning → The art of noticing**.
2. Submit a wrong answer, then the correct answer. The first creates an attempt; only the second completes the lesson.
3. Pass all three checks. Open your certificate from My learning. Print or save it as PDF using the browser.
4. Sign in as the instructor. Open **Instructor studio**, create a draft, add lessons and publish it. Published curricula are immutable so a later edit cannot invalidate existing certificates.
5. Open **Import learners** for a published course. Upload `examples/learners.csv`, review the preview, then start the import with the queue worker running.
6. Refresh the import page. Undo removes only enrollments created by that import that have no learning activity. Previously enrolled learners and learners who attempted a quiz are preserved.

The three sample courses contain nine complete reading lessons. Their content is original demonstration material; completion certificates are learning milestones, not accredited qualifications.

## Architecture and decisions

- **Laravel 13 / PHP 8.4 / Blade / SQLite.** The browser submits ordinary CSRF-protected forms. Data is stored by Laravel, not simulated in browser storage.
- **Authorization:** a course policy checks instructor status and course ownership. Lesson access is scoped to the authenticated learner's enrollment. Certificate access is owner-only.
- **Assessment:** correct answers are read on the server and excluded from model serialization. Each attempt is retained. Unique completion constraints prevent repeated submissions from increasing progress.
- **Completion:** the enrollment row is locked while recording an attempt and deciding completion. A UUID certificate is assigned once. The completion notification is queued after transaction commit.
- **Imports:** `packages/csv-kit` is a standalone Composer library containing the streaming CSV reader. The application owns account lookup, preview limits, durable cursors, authorization, queue delivery, and rollback policy.
- **Resume:** each enrollment and cursor update share one database transaction. A retried job skips committed row numbers. A unique `(user_id, course_id)` key makes duplicates harmless.
- **Undo:** import provenance identifies newly created enrollments. Undo locks the batch and enrollment rows, then deletes only unused rows. It never removes a pre-existing enrollment.
- **Private files:** imports are stored on the private local disk, never exposed through a public storage link.

### Deliberate boundaries

This is a portfolio application, not a hosted production LMS. It does not implement paid courses, video hosting, password reset, email verification, course editing after publication, or invite-based account provisioning. Imported learners must already be registered. CSVs are limited to 1 MB / 5,000 rows; preview stops after 25 account errors. The parser fails fast on malformed CSV rows. Import history requires a refresh. Imported files need a retention policy before production use.

SQLite is the zero-setup development database. Automated tests cover retry/replay behavior, but do not establish multi-worker concurrency guarantees on PostgreSQL or MySQL. Validate locking and contention on the intended deployment database before scaling. Certificate email dispatch uses Laravel's after-commit queue behavior, not a transactional outbox; a process crash between commit and dispatch can lose the email while preserving the certificate.

## Verification

```sh
php artisan test --compact
vendor/bin/pint --format agent
composer validate --strict
```

The suite covers catalogue visibility, registration privilege boundaries, authentication, idempotent enrollment, private lessons/certificates, server-side grading, repeated completion, instructor ownership, draft publishing, sanitized Markdown, CSV validation, queued imports, checkpoint resume, replay, and safe undo. Browser smoke checks exercise sign-in and saved lesson progress.

## The extracted PHP package

`packages/csv-kit` has its own Composer metadata and PHPUnit suite. See its README for standalone installation and the checkpoint contract. It has no Laravel dependency. It is included through Composer's path repository so this checkout is reproducible without publishing to Packagist.

## Deployment

Use a PHP-capable host with a persistent database and a supervised queue worker. Set a unique `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, the public `APP_URL`, database credentials, and a mail transport. Run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, and `php artisan optimize`. Serve only `public/`. Do not run the demo seeder on a public deployment or expose its shared accounts. GitHub Pages cannot execute this PHP backend.

## License

MIT. Built independently for Cynthia Owolabi's portfolio; no employer code or data is included.
