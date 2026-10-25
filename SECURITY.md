# Security and release status
Local development only. Use a virtual host whose document root points to `public/`. Never serve the repository root in production. Keep .env, logs, backups and database scripts inaccessible. Configure HTTPS and SESSION_SECURE=true for any network deployment. Verify Apache access restrictions rather than assuming .htaccess is enabled.

No external email is sent by this milestone. No default administrator password exists. Create an administrator through the CLI. Rate limits use a hash of login identifier and remote address; behind a proxy, configure and test reliable client attribution before deployment.

Parent and student details are confidential. Use a disposable local database for tests. Back up privately; do not include database dumps in Git. The milestone is not production ready and has not received a security audit.
