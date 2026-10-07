# Noir & Co. production checklist

Before deployment:

1. Set `APP_ENV=production` and `APP_DEBUG=false`.
2. Generate a new secret `APP_KEY`; never reuse the local development key.
3. Set production MySQL credentials and run every SQL file in `database/migrations` once, in filename order.
4. Use an HTTPS domain and set `SESSION_SECURE=true`.
5. Use a verified Brevo domain sender and a production Brevo API key.
6. Replace the Paystack test key with live keys and configure the exact HTTPS webhook URL.
7. Schedule encrypted daily MySQL backups and test restoring one regularly.
8. Keep `storage/logs` outside public web access and monitor `mail-delivery.log`, `payment-failures.log`, `payment-reconciliation.log`, `storefront-analytics.log`, `analytics-maintenance.log`, and the PHP error log.
9. Restrict database, `.env`, and upload directories from direct web access.
10. Configure `MALWARE_SCANNER_COMMAND` and confirm `php crons/scan-uploads.php` processes queued uploads. The local baseline scanner is acceptable for development checks only; production should use ClamAV or another host-managed malware scanner.
11. Set `ANALYTICS_RETENTION_DAYS` and schedule `php crons/analytics-maintenance.php`.

Application hardening checks:

- Confirm the deployed environment provides `APP_KEY` with at least 32 random characters. The bootstrap now refuses production when it is missing or too short and forces debug output off.
- Set `APP_URL` to the canonical HTTPS URL and verify the Paystack callback and webhook use that exact host and path.
- Configure the web server document root to `public/`; do not serve the repository root.
- Run a smoke test after deployment: home page, registration/login, password reset, checkout callback, contact form, and each staff dashboard.
- Confirm the uploads directory serves images/documents only and cannot execute PHP or CGI files; the repository includes an uploads deny rule for Apache.
- Run `php database/migrate.php migrate` before relying on database-backed analytics, upload scanning queue records, security alerts, returns, shipments, fulfilments, reservations, secret rotation tasks, performance audits, or accessibility audits.
- Create and track production secret rotation tasks for Paystack, Brevo, Google OAuth, AI provider, database, and `APP_KEY`.

Do not apply the production values to the local `.env` until the deployment host and credentials are ready.
