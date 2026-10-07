# Backups and monitoring

Schedule the CLI jobs from the server (never through a public URL):

```text
php crons/backup-database.php
php crons/monitor-failures.php
php crons/reconcile-payments.php
php crons/analytics-maintenance.php
php crons/scan-uploads.php
```

Run the backup daily and copy the resulting compressed file to separate encrypted storage. Keep a retention policy, test restoring a backup monthly, and alert when `backup.log` records `FAILED` or when a scheduled backup is missing. Run the monitor hourly and connect its output to the host's email, logging, or incident-alert service.

Run `php crons/reconcile-payments.php` hourly as well. It detects inconsistent order and transaction states for manual investigation, while Paystack verification remains idempotent for replayed callbacks.

Run `php crons/analytics-maintenance.php` daily to enforce analytics retention after `analytics_events` has been migrated. Run `php crons/scan-uploads.php` every few minutes after setting `MALWARE_SCANNER_COMMAND` to the production scanner; do not treat upload scanning as complete until the scanner is configured and pending records are processed.

The backup job uses a lock file and a transaction-safe `mysqldump`; it does not delete old backups automatically. Restrict `storage/backups` and `storage/logs` from direct web access.
