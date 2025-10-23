# Production Readiness: Final Testing & Deployment

This document outlines the complete checklist and procedures to make the system production-ready and safely deploy.

## Testing Checklist

1. 18 Core Functionalities
- Student management: create, update, transfer, archive, search.
- Teacher management: onboarding, schedule, salary records.
- Classes & subjects: assign, timetable, attendance, reports.
- Exams & results: templates, grading systems, result publishing.
- Fees & payments: invoicing, payment tracking, receipts.
- Library: books catalog, issue/return, penalties.
- Communication: announcements, messages, notifications.
- Transport: routes, bus locations API, assignments.
- Inventory & maintenance: items, disposal, schedules.
- Hostel: buildings, rooms, allocations/vacate, occupancy.
- Admissions: applications, status, documents.
- Attendance: daily class-wise, student-wise.
- Timetable: creation, conflict detection.
- Reports: student/teacher/fees/exam consolidated.
- User management: roles, permissions, profile.
- Audit logs: changes tracked and review.
- Settings: academic year, grading, feature toggles.
- Backup & restore: manual and scheduled validation.

2. User Roles & Access Control
- Verify access via `module` middleware and role checks.
- Admin: full access; Principal: academic; Warden: Hostel; Librarian: Library; Transport Manager: Transport; Teacher/Student/Parent: scoped access.
- Attempt unauthorized access; expect 403 or redirect.

3. Mobile Responsiveness
- Validate key pages on `375px`, `414px`, `768px`, `1024px`.
- Check navbar, tables (wrap/stack), forms, modals.
- Fix oversflows with utility classes, responsive tables.

4. Security & Data Protection
- Set `APP_ENV=production`, `APP_DEBUG=false`.
- Enforce HTTPS at web server (Apache/Nginx) and `TrustedProxy` if behind proxy.
- Rate limit sensitive routes (already in ModuleMiddleware).
- Validate inputs; CSRF enabled; escape outputs.
- Use per-feature authorization gates/policies.
- Configure `SESSION_DRIVER`, `CACHE_DRIVER`, `QUEUE_CONNECTION`.
- Logs: `LOG_CHANNEL=daily`, restrict log verbosity.

5. Performance
- Apply database indexes (Hostel, Library, others already present).
- Cache config, routes, views.
- Use eager loading for heavy lists.
- Paginate long tables (default 25/50).

## Deployment Preparation

1. Environment Configuration
- Copy `.env.production.example` to server `.env` and set secrets.
- Ensure correct `APP_URL`, mail settings, database credentials.
- Configure cache/queue drivers (Redis recommended), session lifetime.

2. Database Optimization
- Run migrations and index migrations:
  - `php artisan migrate --force`
  - Or targeted: `php artisan migrate --force --path=database/migrations/2025_10_23_120600_add_indexes_to_hostel_tables.php`
- Analyze slow queries; add indexes as needed.

3. Security Hardening
- Web server: redirect HTTP→HTTPS, HSTS, TLS 1.2+.
- Limit request size; set upload limits appropriately.
- Disable directory listing and expose headers.
- Keep OS/PHP/Laravel dependencies updated.

4. Backup & Recovery
- Nightly database backup using `mysqldump`.
- Verify restore quarterly on a staging environment.
- Store backups offsite; encrypt at rest.

5. Deployment Steps (Zero-Downtime Preferred)
- Put site in maintenance mode: `php artisan down --render="errors::503"`.
- Pull latest code; install deps: `composer install --no-dev --optimize-autoloader`.
- Run migrations: `php artisan migrate --force`.
- Cache:
  - `php artisan optimize:clear`
  - `php artisan config:cache` (if config is serializable; otherwise skip)
  - `php artisan route:cache` (skip if duplicate route names; fix conflicts first)
  - `php artisan view:cache`
- Warm key pages via health-checks.
- Bring site up: `php artisan up`.

## Post-Deployment Validation
- Check error logs for spikes.
- Verify critical flows (login, dashboard, hostel occupancy, issue/return books).
- Confirm job queues are running and mail delivery.

## Troubleshooting
- If `php artisan route:list` fails with memory limit, run with higher memory: `php -d memory_limit=512M artisan route:list -n`.
- For failing tests in CI, disable Xdebug and increase memory.

## Staging/UAT
- Mirror production config with masked data.
- Run full test suite before production.
