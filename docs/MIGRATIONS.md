# Database migrations

Run migrations from the project directory with the PHP CLI. The runner reads `.env`, applies SQL files in filename order, and records each applied file and SHA-256 checksum in `schema_migrations`.

```text
php database/migrate.php status
php database/migrate.php dry-run
php database/migrate.php migrate
php database/migrate.php baseline
```

`status` is read-only. `dry-run` lists pending files without changing the database. `migrate` applies each pending file in its own transaction. If an already-applied migration is edited, the runner stops instead of silently running a different schema change.

For an existing database that was created before this runner, verify that all migration files have already been applied, then run `baseline` once. This records the current migration set without executing SQL. Do not use `baseline` on a new or partially migrated database; use `migrate` instead.

Run `status` and take a database backup before applying migrations in production. The script is CLI-only and must not be served from the web root.
