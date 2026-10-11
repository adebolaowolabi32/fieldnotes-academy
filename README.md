# Fieldnotes Academy

A working Laravel learning platform by [Cynthia Owolabi](https://adebolaowolabi32.github.io/). Browse courses, enroll, read lessons, answer knowledge checks, and earn a personal completion certificate. Instructors create and publish courses and import registered learners through a preview-first, resumable queue workflow.

## Public demo

**[Try Fieldnotes Academy](https://adebolaowolabi32.github.io/fieldnotes-academy/)** — open the learner preview directly. Explore courses, enroll as fictional learner Alex Morgan, complete quizzes and print a demo certificate. Progress is stored in your browser, with an in-memory fallback when storage is unavailable. Use **Reset demo** to start over.

Like my other portfolio demos, this preview is hosted on GitHub Pages. It runs browser JavaScript with fictional data. Authentication, instructor authoring, private certificates, queued notifications and CSV import processing are implemented in the runnable Laravel app below. Preview quiz answers are intentionally public and its progress is stored in editable browser state.

## Run locally

Requires PHP 8.4+, Composer 2, PDO SQLite, mbstring, DOM/XML and standard Laravel extensions. The interface uses Blade templates and hand-written CSS. Fonts fall back to system fonts when offline.

```sh
composer run setup
php artisan serve
# In a second terminal, process imports and completion notifications:
php artisan queue:work --tries=3 --timeout=120
```

Open http://127.0.0.1:8000. Setup uses SQLite and a database-backed queue. The default mail driver writes to `storage/logs/laravel.log`; email delivery is recorded in that log. Demo seeding is allowed only in local/testing environments.

| Role | Email | Password |
| --- | --- | --- |
| Learner | learner@example.test | fieldnotes-demo-2026 |
| Instructor | instructor@example.test | fieldnotes-demo-2026 |
| Second learner | maya@example.test | fieldnotes-demo-2026 |

These are disposable local demo accounts. Registration always creates a learner; instructor accounts are provisioned separately.

## Try the full journey

1. Sign in as the learner. Open **My learning → The art of noticing**.
2. Submit a wrong answer, then the correct answer. The first creates an attempt; only the second completes the lesson.
3. Pass all three checks. Open your certificate from My learning. Print or save it as PDF using the browser.
4. Sign in as the instructor. Open **Instructor studio**, create a draft, add lessons and publish it. Published curricula are immutable to preserve the course requirements attached to existing certificates.
5. Open **Import learners** for a published course. Upload `examples/learners.csv`, review the preview, then start the import with the queue worker running.
6. Refresh the import page. Undo removes only enrollments created by that import that have no learning activity. Previously enrolled learners and learners who attempted a quiz are preserved.

The three sample courses contain nine complete reading lessons. Their content is original demonstration material; completion certificates record progress through the sample courses.

## Laravel architecture and decisions

- **Laravel 13 / PHP 8.4 / Blade / SQLite.** The browser submits ordinary CSRF-protected forms. Laravel stores application data in SQLite.
- **Authorization:** a course policy checks instructor status and course ownership. Lesson access is scoped to the authenticated learner's enrollment. Certificate access is owner-only.
- **Assessment:** correct answers are read on the server and excluded from model serialization. Each attempt is retained. Unique completion constraints prevent repeated submissions from increasing progress.
- **Completion:** the enrollment row is locked while recording an attempt and deciding completion. A UUID certificate is assigned once. The completion notification is queued after transaction commit.
- **Imports:** `packages/csv-kit` is a standalone Composer library containing the streaming CSV reader. The application owns account lookup, preview limits, durable cursors, authorization, queue delivery, and rollback policy.
- **Resume:** each enrollment and cursor update share one database transaction. A retried job skips committed row numbers. A unique `(user_id, course_id)` key makes duplicates harmless.
- **Undo:** import provenance identifies newly created enrollments. Undo locks the batch and enrollment rows, then deletes only unused rows. It never removes a pre-existing enrollment.
- **Private files:** imports are stored on the private local disk, never exposed through a public storage link.

### Limitations

This portfolio application supports reading lessons, quizzes, certificates and learner imports. Paid courses, video hosting, password reset, email verification, editing published courses and invitation-based provisioning would require additional implementation. Imported learners must already be registered. CSVs are limited to 1 MB / 5,000 rows; preview stops after 25 account errors. The parser fails fast on malformed CSV rows. Import history requires a refresh. Imported files need a retention policy before production use.

SQLite is the zero-setup development database. Automated tests cover retry/replay behavior on SQLite. Multi-worker concurrency on PostgreSQL or MySQL still needs validation. Validate locking and contention on the intended deployment database before scaling. Certificate emails are queued after the database commit; a process crash between commit and dispatch can lose the email while preserving the certificate.

## Verification

```sh
php artisan test --compact
vendor/bin/pint --format agent
composer validate --strict
```

Tests check certificate ownership, count each quiz once toward progress, and preserve unique enrollments across import retries. They also cover instructor permissions, course publishing and safe import undo. See [`tests/Feature`](tests/Feature) for the individual scenarios.

## The extracted PHP package

`packages/csv-kit` has its own Composer metadata and PHPUnit suite. See its README for standalone installation and the checkpoint contract. It runs independently of Laravel. The standalone release is available on [Packagist](https://packagist.org/packages/cynthiaowolabi/csv-kit) and in the [CSV Kit repository](https://github.com/adebolaowolabi32/csv-kit). This application still uses the bundled copy through a Composer path repository.

## Build the browser preview

```sh
npm ci --prefix demo
npm --prefix demo run build
npm --prefix demo test
cd demo && npx playwright install chromium && npm run test:browser
```

`demo/` contains the browser preview; the build copies only its assets plus public CSS/favicon into `dist/`. The Pages workflow tests desktop and mobile learner journeys before deploying `dist/`. The PHP application and its private data stay in the backend deployment.

To regenerate the committed fictional course fixture after changing the seeder, run `php artisan demo:export`. It seeds a separate in-memory SQLite database, never exports the application's learner data, and refuses production environments.

## PHP deployment

Use a PHP-capable host with a persistent database and a supervised queue worker. Set a unique `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, the public `APP_URL`, database credentials, and a mail transport. Run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, and `php artisan optimize`. Serve only `public/`. Do not run the demo seeder on a public deployment or expose its shared accounts.

## License

MIT © Cynthia Owolabi. The sample courses, learners and code were created for this portfolio.
