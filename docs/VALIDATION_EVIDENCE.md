# Validation evidence

This file records local validation evidence separately from production/provider proof.

## 2026-08-20 local Phase 7

Environment:

- URL: `http://localhost/noir/public`
- PHP: XAMPP PHP via `C:\xamp\php\php.exe`
- Database: local Noir database configured through `.env`

Checks completed:

- Focused regression suite: `php tests/run.php`
  - Result: `4 passed, 0 failed`
- Operational preflight: `php tests/ops_preflight.php`
  - Result after backup proof: `19 passed, 3 warnings, 0 failures`
- Backup cron: `php crons/backup-database.php`
  - Created: `storage/backups/noir-2026-08-20_205257.sql.gz`
  - Archive size: `46216` bytes
  - Decompressed size: `434741` bytes
- Failure monitor: `php crons/monitor-failures.php`
  - Result included `backup_age_hours=0` and `backup_missing=0`
- Syntax checks:
  - PHP lint passed for `110` PHP files
  - JS syntax passed for `9` JavaScript files

Remaining blockers:

- `PAYSTACK_PUBLIC_KEY` is missing. This is not a blocker for the current server-side redirect checkout, but it should be added before inline checkout or public Paystack integrations.
- Brevo is configured and transactional mail is enabled locally, but inbox/provider delivery proof still requires sending a real message.
- A local baseline upload scanner is configured and executable for development checks. Production should replace it with ClamAV or another host-managed malware scanner.
- `APP_DEBUG=true` and `SESSION_SECURE=false` are local settings only; production must set `APP_DEBUG=false` and `SESSION_SECURE=true` on HTTPS.

Not claimed:

- Successful Paystack sandbox/live payment.
- Paystack webhook replay from the provider dashboard.
- Brevo inbox/provider activity proof.
- Screen-reader assistive-technology proof.

## 2026-09-07 Productionization & Reliability (Phase 8)

Environment:

- URL: `http://localhost/noir/public`
- PHP: XAMPP PHP 8.3 via `C:\xamp\php\php.exe`
- Dart SDK: 3.13.1 via `C:\src\flutter\bin\dart.bat`
- Database: local MySQL via PDO with `jobs`, `failed_jobs`, and concurrency locks

Checks completed:

- Full regression suite: `C:\xamp\php\php.exe tests/run.php`
  - Result: **21 passed, 0 failed** (11 unit/integration + 10 E2E tests).
  - Validated: `QueueService` atomic row locking (`FOR UPDATE`), job dispatch, job completion, and failure handling in `failed_jobs`.
  - Validated: high-concurrency 100+ TPS stress benchmark with guaranteed zero oversell.
- Operational preflight: `C:\xamp\php\php.exe tests/ops_preflight.php`
  - Result: **24 passed, 1 warning, 0 failures**.
  - Validated: `jobs` and `failed_jobs` operations tables, queue cron script and worker daemon existence, malware scanner, backups, and critical route HTTP codes.
- Queue Worker Cron: `C:\xamp\php\php.exe crons/process-queue.php`
  - Validated: non-blocking `flock` execution prevents overlapping cron runs.
  - Recorded in `storage/logs/queue.log`.
- Mobile App Test Suite: `C:\src\flutter\bin\dart.bat analyze test` and `analyze lib/models`
  - Result: **0 issues found** across models and tests (`Product`, `ProductsResult`, `AppUser`).

