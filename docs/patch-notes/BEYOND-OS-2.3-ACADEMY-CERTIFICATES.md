# Beyond OS 2.3 — Academy & Certificates

## Release scope

- Shared Beyond-ID learner dashboard across three launch pathways.
- Sequential lesson progress and completion tracking.
- Final pathway assessments with an 80% passing score.
- Automatic Beyond-issued certificate creation after a passing assessment.
- Cryptographically random, unique credential IDs.
- Public certificate verification with revocation-state support.
- Printable/downloadable certificate layouts and Web Share support.
- Achievement badges displayed in Beyond ID.
- Essential Math, Web Development Foundations, and Personal Finance Foundations.
- Explicit language distinguishing Beyond-issued skills certificates from degrees, accreditation, and professional licences.
- Responsive mobile layouts, keyboard focus states, semantic forms, CSRF protection, ownership checks, and parameter validation.

## Data migration

Apply `sql/beyond-os-2.3-academy-certificates.sql` to the production MySQL database after a backup. The application currently provisions matching tables in protected SQLite academy storage for compatibility with the existing learning engine.

## Completion test

1. Sign in with Beyond ID and open `/academy/dashboard.php`.
2. Enter a pathway and confirm only the first unfinished lesson is available.
3. Complete all five lessons and confirm progress reaches 100%.
4. Submit a failing assessment and confirm that no credential is issued.
5. Submit a passing assessment and confirm the certificate and badge appear.
6. Open the public verification URL in a signed-out browser.
7. Confirm another signed-in user cannot open the private certificate page.
8. Print the certificate to PDF and test Web Share or copy-link fallback.
9. Repeat on a phone-sized viewport and complete the flow by keyboard only.

## Rollback

Remove the new Academy certificate pages and shared include, restore the prior Academy and Beyond ID dashboard files, and leave the new tables in place to preserve learner records. The tables can be archived later after exporting credentials and progress.
