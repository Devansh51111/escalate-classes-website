# Free shared-host test deployment (InfinityFree)

Used only for a private synthetic preview. Not a production recommendation.

- Upload `app/`, `storage/`, `public/` and `.env` into `htdocs/`. A root `.htaccess` rewrites every request into `public/`, so `app/`, `storage/` and `.env` are never served.
- Put the whole site behind HTTP Basic auth (`AuthUserFile` needs the absolute path to `.htpasswd`).
- `putenv()` is disabled on this host. The app reads `.env` through `app_env()`, so it works either way.
- Host terms forbid stored backups: set `BACKUP_STORE=false` in `.env`. Backups then download only and nothing is kept on the server.
- No SSH or cron: import `database/schema.sql` once through a temporary, token-protected installer that deletes itself, or phpMyAdmin. Never leave a setup script online.
- SMTP stays empty. No real emails are sent.
