# Escalate Classes

PHP/MySQL local-development build, separate from the phone-friendly design preview. Not a deployed production website.

## XAMPP setup
1. Use PHP 8.1+ with PDO MySQL, mbstring, fileinfo and OpenSSL, Apache and MySQL/MariaDB.
2. Copy the folder into XAMPP htdocs. Copy .env.example to .env and set a database user/password and APP_URL. Never commit .env.
3. Create a new empty database and import database/schema.sql once. Do not import over an existing database.
4. Run `php bin/verify.php`. Run `php bin/create-admin.php YOUR_USERNAME YOUR_EMAIL` and enter a new local password. No default admin or password exists.
5. Start Apache/MySQL and open `/escalate-classes/public/` on that same computer. Admin: `?page=admin`.
6. Add an active academic session, review contacts, and upload/assign images in admin before accepting enquiries. Keep unverified contact placeholders empty.

## Implemented
Five public pages; source-backed review swipe/arrow track; approved cream/coral/charcoal design, background-free logo, actual exterior photo and Since 2016 wording. Enquiries persist with active-class/session checks, validation, duplicate flags and CSRF. Single-admin login with throttling, timeout and session invalidation on password reset. Real overview totals, search/status/class/session/due filters, sorting, pagination, filtered formula-safe CSV, full enquiry details, notes, follow-up, archive and confirmed delete. Session add/edit/close, class add/edit/order/activation, structured content/contact settings, scheduled announcements, validated image upload/slot assignment, manual consistent SQL backup, password-reset SMTP queue, notification status, persistent admin dark mode. Basic unique metadata, canonical URLs, sitemap endpoint and robots rules.

## Email
Set SMTP_* in private .env. SMTP supports STARTTLS or implicit TLS; unencrypted mode is allowed only to a local mock server. After reviewing queued messages, an operator explicitly runs `php bin/process-mail.php --send`. At most 20 queued/failed messages and 3 attempts per record. Sent means SMTP accepted it, not delivered to inbox. SMTP credentials are not in database settings. Enquiry is saved before queueing alerts; queue failure cannot undo it. Mail is not sent by this repository or preview automatically.

Parent receipts remain blocked by a requirement mismatch: specified enquiry fields contain no parent email. No recipient was invented and no new collection field was silently added. Admin alerts/reset are implemented; parent-receipt collection needs a decision.

## Verification actually run
Native PHP 8.4.14 and disposable MariaDB 10.6. Schema import, admin creation, public GETs, protected-route redirect, login, invalid CSRF rejection, session creation, actual enquiry save, HTML escaping, status/note/follow-up changes, filtered CSV/formula defense, published/future notice visibility, SQL backup download, session closure preserving enquiries and blocking new intake, class addition, valid PNG upload/assignment, disguised PHP rejection, queued reset token, password change, single-use reset rejection, updated-password login, confirmed deletion/archive and logout all passed. SMTP accepted a synthetic message on a local mock server. All PHP files lint clean, JS syntax passes. Database-free smoke tests also passed.

Actual PHP-rendered public and admin layouts were inspected in Chrome. Hosted preview carousel Next arrow was clicked and horizontal movement measured. Mobile layout checked at 320/390px and desktop at 1280px. Synthetic test records only. No real email or account grant changes.

## Remaining acceptance / limits
Real XAMPP Apache filesystem permissions and deployment environment; actual SMTP provider/TLS/inbox delivery; recovery import comparison; broader security/accessibility review and browser testing. Parent-email receipts unresolved above. Private fee records, audit history, gallery, PWA and maintenance mode were optional, not enabled. No public fee data. Structured data and social preview image are not yet added. No production hosting/domain selected. Keep private backup/upload/log files out of Git. More classroom/student photos are pending from owner.

See SECURITY.md, RECOVERY.md and tests/README.md. Test scripts use disposable credentials/records and must never target real data.
