# Escalate Classes

PHP/MySQL local-development milestone. This is separate from the phone-friendly design preview.

## Run in XAMPP
1. Use PHP 8.1+ with pdo_mysql and mbstring, MySQL/MariaDB and Apache.
2. Copy this folder to `C:\xampp\htdocs\escalate-classes`.
3. Copy `.env.example` to `.env`. Set your own database username and password. Never commit `.env`.
4. Create an empty `escalate_classes` database and import `database/schema.sql` once using phpMyAdmin. Do not import over an existing database.
5. From the project folder, run `php bin/verify.php`.
6. Run `php bin/create-admin.php YOUR_USERNAME YOUR_EMAIL`. Enter a new password when prompted. Do not use a real production password for local testing.
7. Start Apache/MySQL. Open `http://localhost/escalate-classes/public/` on the SAME computer running XAMPP.
8. Admin login: `http://localhost/escalate-classes/public/?page=admin`.
9. Add an active academic session before accepting enquiries. Review contact details in admin settings.

## Implemented in source
Five public pages; PDO persistence for enquiries; active-session gating; duplicate flag; CSRF; generic login errors and throttling; admin enquiry list/search/status filters/pagination; status/follow-up/notes/archive; CSV export; central contacts, structured content and class settings; admin dark toggle.

## Not completed
SMTP delivery and parent receipts, password recovery, gallery/media uploads, announcement editor, dashboard aggregate statistics, session editing/closure, backups/restore UI, enquiry deletion confirmation, full SEO, deployment, formal security review and end-to-end database testing. CSV currently exports all unarchived enquiries rather than applying the current screen filters. Do not deploy this milestone to production.

Phone and contact placeholders are intentional. Earlier planning content conflicts on founder identity; no founder name has been assumed. No student records, real credentials or passwords are included.

## Verification status
PHP 8.5 WebAssembly syntax checks passed for all five PHP files. JavaScript syntax checking passed. Six database-free GET page smoke tests and invalid-CSRF rejection also passed under PHP 8.5 WebAssembly. MySQL connections and end-to-end application checks remain unverified. Do not treat this as a tested release. The separate visual preview was rendered and navigated, but that does not validate PHP execution.

## Required acceptance checks
- Run PHP syntax checks: `php -l` for every `.php` file, then `php bin/verify.php`.
- Import schema into a disposable database; create admin and confirm login, throttling, logout and expired sessions.
- With no active session, verify public submissions cannot save.
- Create an active session; submit a test enquiry, verify it exists exactly once and HTML is escaped.
- Try an invalid CSRF token, inactive class/session and oversized values; verify rejection.
- Test search/filter/pagination, notes, follow-up and archive. Verify non-admin access fails.
- Export CSV and verify spreadsheet formula inputs are prefixed safely.
- Review 320/390/1280px layouts and keyboard interaction.

Run the database-free smoke tests from the project root with `python tests/smoke.py /path/to/php`. No test creates records.
