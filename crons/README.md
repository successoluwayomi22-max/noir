# Scheduled jobs

Place each scheduled task in this directory as a CLI PHP script. Run them from the server scheduler, for example:

`php crons/abandoned-cart-recovery.php`

Production jobs:

- `php crons/backup-database.php` creates a compressed MySQL backup in `storage/backups`, uses a lock to prevent overlap, and records success/failure in `storage/logs/backup.log`. Schedule it daily and copy/encrypt backups to storage separate from the web server.
- `php crons/monitor-failures.php` records failure counts from mail, payment, reconciliation, storefront analytics, AI, staff, review, and backup logs in `storage/logs/monitor.log`. Schedule it hourly and connect the output to the host alerting service.
- `php crons/reconcile-payments.php` reports mismatches between order payment status and gateway transaction status. Schedule it hourly and alert on any non-zero result; it never changes payment data automatically.
- `php crons/analytics-maintenance.php` applies `ANALYTICS_RETENTION_DAYS` retention to database-backed storefront analytics. Schedule it daily after migrations are applied.
- `php crons/process-queue.php` drains up to 50 pending jobs from the database queue (`jobs` table). Uses a lock to prevent overlapping executions and logs results in `storage/logs/queue.log`. Schedule it every minute in cron or Windows Task Scheduler if not running the background daemon.
- `php bin/worker.php` runs as a continuous, long-running daemon queue worker (for Supervisor or background services). Use `--once` to process until empty.

Jobs should be idempotent, log their result, and use a lock to prevent overlapping executions.

