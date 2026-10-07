<?php
declare(strict_types=1);

namespace Noir\Core;

final class Mailer
{
    /**
     * Resolve the store brand name dynamically.
     * Every subject line and header uses this instead of a hardcoded string.
     */
    private function brand(): string
    {
        return (string)store_setting('store_name', 'Noir & Co.');
    }

    /**
     * Build the "From:" header value.
     */
    private function fromHeader(): string
    {
        $from = (string)config('mail.from_address', 'no-reply@noir.test');
        return "From: " . config('mail.from_name', $this->brand()) . " <{$from}>\r\n"
             . "Content-Type: text/html; charset=UTF-8\r\n"
             . "MIME-Version: 1.0\r\n";
    }

    /**
     * Send via Brevo first, fallback to PHP mail, return result.
     */
    private function dispatch(string $email, string $name, string $subject, string $html, string $replyTo = ''): bool
    {
        $sent = $this->sendBrevo($email, $name, $subject, $html);
        if (!$sent && config('mail.enabled', false)) {
            $headers = $this->fromHeader();
            if ($replyTo) {
                $headers .= "Reply-To: {$replyTo}\r\n";
            }
            $sent = @mail($email, $subject, $html, $headers);
        }
        return $sent || config('env', 'local') === 'local';
    }

    /**
     * Write a log entry.
     */
    private function log(string $file, string $line): void
    {
        $logDir = BASE_PATH . '/storage/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0775, true);
        file_put_contents($logDir . '/' . $file, $line, FILE_APPEND | LOCK_EX);
    }

    // ───────────────────────────── Customer Emails ─────────────────────────────

    public function sendCustomerWelcome(string $email, string $name, string $loginUrl): bool
    {
        $brand   = $this->brand();
        $subject = "Welcome to {$brand}";
        $message = \Noir\Core\EmailTemplates::customerWelcome($name, $loginUrl);
        $ok = $this->dispatch($email, $name, $subject, $message);
        $this->log('welcome-emails.log', sprintf("[%s] %s <%s>\nHTML email sent\n\n", date('Y-m-d H:i:s'), $name, $email));
        return $ok;
    }

    public function sendPasswordReset(string $email, string $name, string $resetUrl, string $otpCode = ''): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} verification code";
        $otpCode = $otpCode !== '' ? $otpCode : strtoupper(substr(md5(uniqid($email, true)), 0, 6));
        $message = \Noir\Core\EmailTemplates::passwordResetOTP($name, $otpCode, $resetUrl, 15);
        $ok = $this->dispatch($email, $name, $subject, $message);
        $otpLog = config('env', 'local') === 'local' ? " OTP: {$otpCode}" : '';
        $this->log('password-resets.log', sprintf("[%s] %s <%s>%s\nHTML email sent\n\n", date('Y-m-d H:i:s'), $name, $email, $otpLog));
        return $ok;
    }

    public function sendContactMessage(string $to, string $senderName, string $senderEmail, string $subject, string $html): bool
    {
        $ok = $this->dispatch($to, $senderName, $subject, $html, $senderEmail);
        $this->log('contact-messages.log', sprintf("[%s] %s <%s> %s\n%s\n\n", date('Y-m-d H:i:s'), $senderName, $senderEmail, $subject, strip_tags($html)));
        return $ok;
    }

    public function sendAbandonedCartReminder(string $email, string $customerName, array $cartItems, float $total, string $currency, string $cartUrl): bool
    {
        $brand   = $this->brand();
        $subject = "Your selection is waiting in your bag — {$brand}";
        $message = \Noir\Core\EmailTemplates::abandonedCart($customerName, $cartItems, $total, $currency, $cartUrl);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('abandoned-cart-emails.log', sprintf("[%s] Forgotten cart reminder sent to %s <%s> (%d items)\n", date('Y-m-d H:i:s'), $customerName, $email, count($cartItems)));
        return $ok;
    }

    // ──────────────────────── Staff Application Emails ─────────────────────────

    public function sendStaffApproval(string $email, string $name, string $roleName, string $loginUrl): bool
    {
        $brand   = $this->brand();
        $subject = "Your Staff Application Has Been Approved — {$brand}";
        $message = \Noir\Core\EmailTemplates::staffApproval($name, $roleName, $loginUrl);
        $ok = $this->dispatch($email, $name, $subject, $message);
        $this->log('staff-emails.log', sprintf("[%s] Staff application APPROVED email sent to %s <%s> (Role: %s)\n", date('Y-m-d H:i:s'), $name, $email, $roleName));
        return $ok;
    }

    public function sendStaffRejection(string $email, string $name, string $reason): bool
    {
        $brand   = $this->brand();
        $subject = "Staff Application Status — {$brand}";
        $message = \Noir\Core\EmailTemplates::staffRejection($name, $reason);
        $ok = $this->dispatch($email, $name, $subject, $message);
        $this->log('staff-emails.log', sprintf("[%s] Staff application REJECTED email sent to %s <%s> (Reason: %s)\n", date('Y-m-d H:i:s'), $name, $email, $reason));
        return $ok;
    }

    // ──────────────────────────── Brevo API Client ─────────────────────────────

    private function sendBrevo(string $email, string $name, string $subject, string $html): bool
    {
        $apiKey = trim((string)config('mail.brevo_api_key', ''));
        if ($apiKey === '' || !function_exists('curl_init')) return false;
        $payload = json_encode(['sender'=>['name'=>(string)config('mail.from_name', $this->brand()),'email'=>(string)config('mail.from_address','no-reply@noir.test')],'to'=>[['email'=>$email,'name'=>$name]],'subject'=>$subject,'htmlContent'=>$html], JSON_UNESCAPED_SLASHES);
        $curl = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($curl, [CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['accept: application/json','api-key: '.$apiKey,'content-type: application/json'],CURLOPT_POSTFIELDS=>$payload]);
        $body = curl_exec($curl); $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); $error = curl_error($curl); curl_close($curl);
        if ($body === false || $status < 200 || $status >= 300) {
            $logDir = BASE_PATH . '/storage/logs';
            if (!is_dir($logDir)) mkdir($logDir, 0775, true);
            $detail = $body !== false ? substr((string)$body, 0, 500) : $error;
            file_put_contents($logDir . '/mail-delivery.log', sprintf("[%s] Brevo rejected %s (HTTP %d): %s\n", date('Y-m-d H:i:s'), $email, $status, $detail), FILE_APPEND | LOCK_EX);
            return false;
        }
        return true;
    }

    // ────────────────────────── Review Notification ────────────────────────────

    public function sendReviewNotification(string $customerName, string $customerEmail, string $product, int $rating, string $review): bool
    {
        $subject = 'New customer review submitted - ' . $product;
        $message = \Noir\Core\EmailTemplates::newOrderStaffNotification([
            'customer_name' => $customerName,
            'order_number' => 'REVIEW-' . $product,
            'total' => 0,
            'currency' => '',
            'payment_status' => 'Review',
            'order_date' => date('Y-m-d H:i:s')
        ]);
        $to = (string)config('mail.reviews_to', config('mail.from_address', 'no-reply@noir.test'));
        $ok = $this->dispatch($to, $customerName, $subject, $message, $customerEmail);
        $this->log('reviews.log', sprintf("[%s] %s <%s> reviewed %s (%d/5)\nHTML email sent\n\n", date('Y-m-d H:i:s'), $customerName, $customerEmail, $product, $rating));
        return $ok;
    }

    // ───────────────────── Staff Lifecycle Notifications ───────────────────────

    public function sendStaffRoleChange(string $email, string $name, string $oldRole, string $newRole, string $dashboardUrl): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} role has been updated";
        $message = \Noir\Core\EmailTemplates::staffRoleChange($name, $oldRole, $newRole, $dashboardUrl);
        $ok = $this->dispatch($email, $name, $subject, $message);
        $this->log('role-changes.log', sprintf("[%s] %s <%s> role changed from %s to %s\n\n", date('Y-m-d H:i:s'), $name, $email, $oldRole, $newRole));
        return $ok;
    }

    public function sendStaffSuspension(string $email, string $name, string $reason = ''): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} staff account has been suspended";
        $message = \Noir\Core\EmailTemplates::staffSuspension($name, $reason);
        $ok = $this->dispatch($email, $name, $subject, $message);
        $this->log('suspensions.log', sprintf("[%s] %s <%s> suspended\n\n", date('Y-m-d H:i:s'), $name, $email));
        return $ok;
    }

    public function sendStaffReactivation(string $email, string $name, string $dashboardUrl): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} staff account has been reactivated";
        $message = \Noir\Core\EmailTemplates::staffReactivation($name, $dashboardUrl);
        $ok = $this->dispatch($email, $name, $subject, $message);
        $this->log('reactivations.log', sprintf("[%s] %s <%s> reactivated\n\n", date('Y-m-d H:i:s'), $name, $email));
        return $ok;
    }

    public function sendStaffDirectMessage(string $email, string $name, string $subject, string $bodyText, string $senderName = 'System Administration'): bool
    {
        $brand   = $this->brand();
        $fullSubject = "[{$brand} Staff] " . $subject;
        $html = \Noir\Core\EmailTemplates::staffDirectMessage($name, $subject, $bodyText, $senderName);
        $ok = $this->dispatch($email, $name, $fullSubject, $html);
        $this->log('staff-messages.log', sprintf("[%s] Message sent to %s <%s> by %s: %s\n\n", date('Y-m-d H:i:s'), $name, $email, $senderName, $subject));
        return $ok;
    }

    // ──────────────────────────── Order Lifecycle ──────────────────────────────

    public function sendOrderConfirmation(string $email, string $customerName, array $order, array $items): bool
    {
        $brand   = $this->brand();
        $subject = 'Order ' . ($order['order_number'] ?? '') . " confirmed — {$brand}";
        $order['customer_name'] = $customerName;
        $message = \Noir\Core\EmailTemplates::orderConfirmation($order, $items);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('order-confirmations.log', sprintf("[%s] %s <%s> order %s confirmed\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number'] ?? ''));
        return $ok;
    }

    public function sendPaymentConfirmation(string $email, string $customerName, array $order): bool
    {
        $subject = 'Payment confirmed for order ' . ($order['order_number'] ?? '');
        $order['customer_name'] = $customerName;
        $order['payment_date'] = date('Y-m-d H:i:s');
        $message = \Noir\Core\EmailTemplates::paymentConfirmation($order);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('payment-confirmations.log', sprintf("[%s] %s <%s> payment confirmed for %s\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number'] ?? ''));
        return $ok;
    }

    public function sendPaymentFailed(string $email, string $customerName, array $order): bool
    {
        $subject = 'Payment could not be completed';
        $order['customer_name'] = $customerName;
        $message = \Noir\Core\EmailTemplates::paymentFailed($order);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('payment-failures.log', sprintf("[%s] %s <%s> payment failed for %s\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number'] ?? ''));
        return $ok;
    }

    public function sendOrderProcessing(string $email, string $customerName, array $order): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} order is being prepared";
        $order['customer_name'] = $customerName;
        $message = \Noir\Core\EmailTemplates::orderProcessing($order);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('order-processing.log', sprintf("[%s] %s <%s> order %s processing\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number']));
        return $ok;
    }

    public function sendOrderShipped(string $email, string $customerName, array $order, string $trackingNumber, string $trackingUrl): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} order has shipped";
        $order['customer_name'] = $customerName;
        $message = \Noir\Core\EmailTemplates::orderShipped($order, $trackingNumber, $trackingUrl);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('order-shipped.log', sprintf("[%s] %s <%s> order %s shipped with tracking %s\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number'], $trackingNumber));
        return $ok;
    }

    public function sendOutForDelivery(string $email, string $customerName, array $order, string $trackingUrl = ''): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} order is out for delivery";
        $order['customer_name'] = $customerName;
        $message = \Noir\Core\EmailTemplates::outForDelivery($order, $trackingUrl);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('out-for-delivery.log', sprintf("[%s] %s <%s> order %s out for delivery\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number']));
        return $ok;
    }

    public function sendOrderDelivered(string $email, string $customerName, array $order): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} order has been delivered";
        $order['customer_name'] = $customerName;
        $message = \Noir\Core\EmailTemplates::orderDelivered($order);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('order-delivered.log', sprintf("[%s] %s <%s> order %s delivered\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number']));
        return $ok;
    }

    public function sendOrderCancelled(string $email, string $customerName, array $order, string $reason = ''): bool
    {
        $subject = 'Order ' . $order['order_number'] . ' has been cancelled';
        $order['customer_name'] = $customerName;
        $message = \Noir\Core\EmailTemplates::orderCancelled($order, $reason);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('order-cancelled.log', sprintf("[%s] %s <%s> order %s cancelled\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number']));
        return $ok;
    }

    public function sendRefundCompleted(string $email, string $customerName, array $order): bool
    {
        $brand   = $this->brand();
        $subject = "Your {$brand} refund has been processed";
        $order['customer_name'] = $customerName;
        $order['refund_date'] = date('Y-m-d H:i:s');
        $message = \Noir\Core\EmailTemplates::refundCompleted($order);
        $ok = $this->dispatch($email, $customerName, $subject, $message);
        $this->log('refunds.log', sprintf("[%s] %s <%s> refund processed for order %s\n\n", date('Y-m-d H:i:s'), $customerName, $email, $order['order_number']));
        return $ok;
    }

    // ──────────────────── Staff / Admin Notifications ──────────────────────────

    public function sendNewCustomerNotification(string $email, array $customer): bool
    {
        $brand   = $this->brand();
        $subject = "New customer registered on {$brand}";
        $message = \Noir\Core\EmailTemplates::newCustomerNotification($customer);
        $ok = $this->dispatch($email, $customer['name'] ?? 'Admin', $subject, $message);
        $this->log('new-customers.log', sprintf("[%s] new customer %s <%s>\n\n", date('Y-m-d H:i:s'), $customer['name'], $customer['email']));
        return $ok;
    }

    public function sendNewOrderStaffNotification(string $email, array $order): bool
    {
        $subject = 'New order received — ' . $order['order_number'];
        $message = \Noir\Core\EmailTemplates::newOrderStaffNotification($order);
        $ok = $this->dispatch($email, 'Admin', $subject, $message);
        $this->log('new-orders.log', sprintf("[%s] new order %s\n\n", date('Y-m-d H:i:s'), $order['order_number']));
        return $ok;
    }

    public function sendPaymentApprovalNeeded(string $email, array $order): bool
    {
        $subject = 'Payment approval required — ' . $order['order_number'];
        $order['payment_date'] = date('Y-m-d H:i:s');
        $message = \Noir\Core\EmailTemplates::paymentApprovalNeeded($order);
        $ok = $this->dispatch($email, 'Admin', $subject, $message);
        $this->log('payment-approvals.log', sprintf("[%s] payment approval needed for %s\n\n", date('Y-m-d H:i:s'), $order['order_number']));
        return $ok;
    }

    public function sendNewStaffApplication(string $email, array $application): bool
    {
        $subject = 'New staff application received';
        $message = \Noir\Core\EmailTemplates::newStaffApplication($application);
        $ok = $this->dispatch($email, $application['name'] ?? 'Applicant', $subject, $message);
        $this->log('staff-applications.log', sprintf("[%s] new application from %s <%s>\n\n", date('Y-m-d H:i:s'), $application['name'], $application['email']));
        return $ok;
    }

    // ───────────────────────── Company Communications ─────────────────────────

    public function sendCompanyAnnouncement(string $email, string $title, string $message, string $ctaText = '', string $ctaUrl = '', string $bannerImage = ''): bool
    {
        $subject     = $title;
        $htmlMessage = \Noir\Core\EmailTemplates::companyAnnouncement($title, $message, $ctaText, $ctaUrl, $bannerImage);
        $ok = $this->dispatch($email, 'Member', $subject, $htmlMessage);
        $this->log('announcements.log', sprintf("[%s] announcement sent to %s\n\n", date('Y-m-d H:i:s'), $email));
        return $ok;
    }

    public function sendCompanyImportantUpdate(string $email, string $title, string $message): bool
    {
        $brand       = $this->brand();
        $subject     = "Important update from {$brand}";
        $htmlMessage = \Noir\Core\EmailTemplates::companyImportantUpdate($title, $message);
        $ok = $this->dispatch($email, 'Member', $subject, $htmlMessage);
        $this->log('important-updates.log', sprintf("[%s] important update sent to %s\n\n", date('Y-m-d H:i:s'), $email));
        return $ok;
    }
}
