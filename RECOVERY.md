# Local backup and recovery

Admin > Manage website > Backup creates a consistent database snapshot and downloads SQL. Backup files are also stored in storage/backups, outside public/. They contain confidential enquiry data, reset token hashes, notification queue contents and administrator password hashes. Protect them as private records and never commit or publish them. This is manual backup, not scheduled backup.

Back up public/uploads separately, preserving filenames. Keep .env privately outside the backup. No credentials are embedded in the SQL dump.

## Recovery test
1. Stop application writes. Keep the original database untouched.
2. Create a separate empty database, for example escalate_recovery_test, using phpMyAdmin.
3. Import the backup SQL into that separate database. Do not import an untrusted SQL file.
4. Make a separate local application copy. Set its DB_NAME to the recovery database; set SMTP_HOST empty so it cannot send queued mail.
5. Restore uploads into that copy's public/uploads. Compare enquiry totals and samples, classes/sessions, notes, settings, notices, and image references against the original.
6. Log in with a test administrator, create a synthetic enquiry and export CSV. Confirm historical session relationships.
7. Only after reviewing the test, plan an explicit maintenance window for a real recovery. Never run an automatic web restore over current records.

SQL backup download and import into a separate recovery database were executed against synthetic records. Enquiry, note and class counts matched. A full manual image/settings recovery drill remains recommended. Upload files are not included in database backups.
