# Operational test record

## Automated checks completed

- PHP syntax checks passed for delivery and finance controllers, both dashboard views, and route definitions.
- Migration runner status completed successfully and reported the expected unbaselined local schema.
- Delivery status endpoint is protected by `DeliveryMiddleware` and CSRF middleware.
- Finance dashboard and CSV export are protected by `FinanceMiddleware`.
- Finance filters whitelist payment statuses (`paid`, `pending`, `failed`) and normalize invalid dates.
- Delivery status input is whitelist-validated before updating an order.

## Live checks required on the deployment environment

1. Sign in as a delivery manager and change a test order from pending to processing; confirm the customer notification appears.
2. Continue the same order through shipped and delivered; confirm the customer order timeline updates.
3. Sign in as a finance manager, filter by a known date range and payment status, and confirm totals match the transaction records.
4. Download the finance CSV and verify it opens with the expected order, customer, payment, total, currency, and date columns.
5. Confirm a customer or unrelated staff role receives `403`/role redirection for both protected areas.

These checks require authenticated browser sessions and real database records, so they are not claimed as completed locally.

## Production validation before the next phase

Record the tester, timestamp, environment URL, order number, payment reference, and observed log entries for each item.

1. Paystack live/test-mode payment:
   - Start checkout as a guest and confirm the summary shows delivery timing, shipping cost, subtotal, and total before redirecting to Paystack.
   - Complete a successful Paystack payment and confirm `orders.payment_status='paid'`, `orders.status='processing'`, `payment_transactions.status='paid'`, and the cart is cleared.
   - Cancel or fail a Paystack attempt, return to checkout, and use the retry form. Confirm the retry creates a new Paystack reference linked to the same order, not a duplicate order.

2. Paystack webhook:
   - Configure the deployed webhook URL as `/payments/paystack/webhook`.
   - Trigger or replay a `charge.success` event from Paystack and confirm the HMAC signature is accepted only with the configured secret.
   - Replay the same event and confirm the order remains paid without duplicate state changes.

3. Brevo transactional email:
   - Send a welcome email, password-reset OTP, contact-form message, and staff approval/rejection email through the production Brevo key.
   - Confirm delivery in the recipient inbox and in Brevo activity logs.
   - Confirm `storage/logs/mail-delivery.log` remains empty for successful sends and records rejected sends without exposing secrets.

4. OTP password reset:
   - Request an OTP for an existing customer and verify the email contains a six-digit code.
   - Confirm the code resets the password once, expires after the configured window, and cannot be reused.
   - Trigger repeated requests and confirm throttling protects the endpoint.

5. Backups and restore:
   - Run `php crons/backup-database.php` on the production host and confirm the archive is created outside public access.
   - Restore the newest backup into a separate database and run a smoke test against that restored copy.
   - Confirm the hourly monitor logs missing or failed backups clearly enough for host alerting.

6. Staff workflow:
   - Approve and reject a staff application as an admin; confirm role assignment, email notification, and audit log entries.
   - Sign in as delivery and finance staff and confirm each role reaches only its own dashboard.
   - Attempt cross-role access and confirm the app blocks or redirects it.

7. Analytics and observability:
   - Open the storefront, submit a search, open a product, add an item to cart, start checkout, and trigger both failed and successful payment paths.
   - Confirm `storage/logs/storefront-analytics.log` records `search_submitted`, `product_viewed`, `product_clicked`, `cart_intent`, `checkout_started`, `checkout_failure`, `payment_retry_clicked`, and `checkout_success` where applicable.
   - Open `/admin/reports` as an administrator and confirm the funnel, discovery, payment, product, and failure-log panels update for the selected date range.
   - Run `php crons/monitor-failures.php` and confirm `storage/logs/monitor.log` includes mail, payment, reconciliation, storefront analytics, AI, staff, review, and backup health counts.
