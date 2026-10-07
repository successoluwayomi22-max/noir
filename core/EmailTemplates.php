<?php
declare(strict_types=1);

namespace Noir\Core;

final class EmailTemplates
{
    private static function getBrandColors(): array
    {
        return [
            'primary' => '#687158',
            'accent' => '#b9c1a4',
            'dark' => '#252820',
            'paper' => '#f7f7f2',
            'muted' => '#85877d',
            'white' => '#ffffff',
        ];
    }

    private static function renderHeader(string $title = ''): string
    {
        $colors = self::getBrandColors();
        $storeName = store_setting('store_name', 'Noir & Co.');
        $storeLogo = store_setting('store_logo');
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        
        $logoHtml = $storeLogo 
            ? '<img src="' . e($publicBase . $storeLogo) . '" alt="' . e($storeName) . '" style="max-height: 40px;">'
            : '<span style="font-size: 18px; font-weight: 600; letter-spacing: 0.1em; color: ' . $colors['dark'] . ';">' . e($storeName) . '</span>';
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . e($title) . '</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background-color: ' . $colors['paper'] . '; padding: 30px 40px; text-align: center; border-bottom: 1px solid #e5e5e5; }
        .content { padding: 40px; }
        .footer { background-color: ' . $colors['dark'] . '; color: ' . $colors['paper'] . '; padding: 30px 40px; text-align: center; font-size: 12px; }
        .footer a { color: ' . $colors['accent'] . '; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
        .button { display: inline-block; background-color: ' . $colors['primary'] . '; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 14px; }
        .button:hover { background-color: #596249; }
        .divider { border-top: 1px solid #e5e5e5; margin: 30px 0; }
        .info-card { background-color: ' . $colors['paper'] . '; border: 1px solid #e5e5e5; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .info-card-label { font-size: 11px; color: ' . $colors['muted'] . '; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
        .info-card-value { font-size: 16px; color: ' . $colors['dark'] . '; font-weight: 500; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .status-success { background-color: #e7efe3; color: #55704e; }
        .status-pending { background-color: #fff4e5; color: #a4793d; }
        .status-error { background-color: #f2dddd; color: #a35e5e; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .content { padding: 20px !important; }
            .header { padding: 20px !important; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            ' . $logoHtml . '
        </div>
        <div class="content">';
    }

    private static function renderFooter(bool $includeUnsubscribe = false): string
    {
        $colors = self::getBrandColors();
        $storeName = store_setting('store_name', 'Noir & Co.');
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        
        $footer = '<div class="footer">
            <p style="margin: 0 0 10px 0;"><strong>' . e($storeName) . '</strong></p>
            <p style="margin: 0 0 10px 0;">Premium Unisex Professional Wear</p>
            <p style="margin: 0 0 20px 0;">Need help? Contact our customer support team</p>
            <p style="margin: 0;">
                <a href="' . e($publicBase . '/') . '">Visit Website</a> | 
                <a href="' . e($publicBase . '/store#catalogue') . '">Shop Collection</a>
            </p>';
        
        if ($includeUnsubscribe) {
            $footer .= '<p style="margin: 20px 0 0 0; font-size: 11px; color: ' . $colors['muted'] . ';">
                <a href="{{unsubscribe_url}}">Unsubscribe</a> | 
                <a href="{{preferences_url}}">Email Preferences</a>
            </p>';
        }
        
        $footer .= '<p style="margin: 20px 0 0 0; font-size: 10px; color: ' . $colors['muted'] . ';">
            © ' . date('Y') . ' ' . e($storeName) . '. All rights reserved.
        </p>
    </div>
</body>
</html>';
        
        return $footer;
    }

    private static function renderButton(string $text, string $url): string
    {
        return '<div style="text-align: center; margin: 30px 0;">
            <a href="' . e($url) . '" class="button">' . e($text) . '</a>
        </div>';
    }

    private static function renderDivider(): string
    {
        return '<div class="divider"></div>';
    }

    private static function renderInfoCard(string $label, string $value): string
    {
        return '<div class="info-card">
            <div class="info-card-label">' . e($label) . '</div>
            <div class="info-card-value">' . e($value) . '</div>
        </div>';
    }

    private static function renderStatusBadge(string $status, string $type = 'success'): string
    {
        return '<span class="status-badge status-' . e($type) . '">' . e($status) . '</span>';
    }

    // 1. Customer Welcome Email (Luxury VIP Edition)
    public static function customerWelcome(string $name, string $loginUrl): string
    {
        $firstName = explode(' ', trim($name))[0];
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $storeName = store_setting('store_name', 'Noir & Co.');
        
        $html = self::renderHeader('Welcome to ' . $storeName);
        $html .= '<h1 style="color: #252820; font-size: 26px; font-weight: 700; margin: 0 0 16px 0; letter-spacing: -0.5px;">Welcome to ' . e($storeName) . ', ' . e($firstName) . '</h1>
        <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 0 0 20px 0;">
            Thank you for joining our maison. Your membership is now active across both the <strong>Web Storefront</strong> and our <strong>Mobile App</strong>.
        </p>
        
        <div style="background-color: #f7f7f2; border: 1px solid #e8e9e0; border-radius: 12px; padding: 24px; margin: 24px 0;">
            <p style="font-size: 11px; font-weight: 700; color: #687158; text-transform: uppercase; letter-spacing: 1.2px; margin: 0 0 14px 0;">Your Membership Privileges</p>
            
            <div style="margin-bottom: 12px; display: flex; align-items: flex-start;">
                <span style="font-size: 16px; margin-right: 10px;">✨</span>
                <div>
                    <strong style="color: #252820; font-size: 14px;">Private Capsule Drops</strong>
                    <p style="color: #777; font-size: 13px; margin: 2px 0 0 0; line-height: 1.4;">Early access to limited luxury editions before general public release.</p>
                </div>
            </div>

            <div style="margin-bottom: 12px; display: flex; align-items: flex-start;">
                <span style="font-size: 16px; margin-right: 10px;">🔄</span>
                <div>
                    <strong style="color: #252820; font-size: 14px;">Real-Time Synced Bag & Wishlist</strong>
                    <p style="color: #777; font-size: 13px; margin: 2px 0 0 0; line-height: 1.4;">Save pieces on the mobile app and complete checkout seamlessly on web or phone.</p>
                </div>
            </div>

            <div style="display: flex; align-items: flex-start;">
                <span style="font-size: 16px; margin-right: 10px;">⚡</span>
                <div>
                    <strong style="color: #252820; font-size: 14px;">Live Courier Tracking</strong>
                    <p style="color: #777; font-size: 13px; margin: 2px 0 0 0; line-height: 1.4;">Milestone-by-milestone dispatch updates straight to your device.</p>
                </div>
            </div>
        </div>';
        
        $html .= '<div style="text-align: center; margin: 30px 0;">
            <a href="' . e($publicBase . '/store') . '" class="button" style="margin-right: 10px; margin-bottom: 10px;">Explore Collection ↗</a>
            <a href="' . e($loginUrl) . '" style="display: inline-block; background-color: #252820; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 14px;">My Account</a>
        </div>';
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 13px; margin: 0; line-height: 1.5;">
            Need styling advice or have sizing questions? Simply reply to this email or reach our concierge team anytime.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 1b. Forgotten / Abandoned Cart Reminder Email
    public static function abandonedCart(string $name, array $items, float $total, string $currency, string $cartUrl): string
    {
        $firstName = explode(' ', trim($name))[0];
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $storeName = store_setting('store_name', 'Noir & Co.');
        
        $html = self::renderHeader('Your selection is waiting — ' . $storeName);
        $html .= '<h1 style="color: #252820; font-size: 26px; font-weight: 700; margin: 0 0 14px 0; letter-spacing: -0.5px;">You left something exceptional in your bag</h1>
        <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 0 0 20px 0;">
            Hello ' . e($firstName) . ', we noticed you left pieces in your shopping bag. Because our collections are produced in strictly limited capsule runs, items are not reserved indefinitely.
        </p>';
        
        // Cart Items Table
        $html .= '<div style="background-color: #f7f7f2; border: 1px solid #e8e9e0; border-radius: 12px; padding: 20px; margin: 24px 0;">
            <p style="font-size: 11px; font-weight: 700; color: #687158; text-transform: uppercase; letter-spacing: 1.2px; margin: 0 0 16px 0;">Your Reserved Bag</p>';
            
        foreach ($items as $item) {
            $img = !empty($item['featured_image']) ? $item['featured_image'] : '';
            if (!empty($img) && !str_starts_with($img, 'http')) {
                $img = $publicBase . $img;
            }
            $imgTag = !empty($img) ? '<img src="' . e($img) . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; margin-right: 14px;" alt="' . e($item['name']) . '">' : '';
            
            $html .= '<div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e3db;">
                <div style="display: flex; align-items: center;">
                    ' . $imgTag . '
                    <div>
                        <strong style="color: #252820; font-size: 14px; display: block;">' . e($item['name']) . '</strong>
                        <span style="color: #777; font-size: 12px;">Qty: ' . (int)($item['quantity'] ?? 1) . '</span>
                    </div>
                </div>
                <div style="font-weight: 600; color: #252820; font-size: 14px;">
                    ' . e($currency) . ' ' . number_format((float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1), 2) . '
                </div>
            </div>';
        }
        
        $html .= '<div style="display: flex; justify-content: space-between; padding-top: 14px; font-size: 16px; font-weight: 700; color: #252820;">
            <span>Subtotal</span>
            <span>' . e($currency) . ' ' . number_format($total, 2) . '</span>
        </div>
        </div>';
        
        $html .= '<div style="text-align: center; margin: 30px 0;">
            <a href="' . e($cartUrl) . '" class="button" style="padding: 16px 36px; font-size: 15px; font-weight: 600;">Complete My Order ↗</a>
        </div>';
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 13px; text-align: center; margin: 0;">
            Enjoy complimentary express insured shipping on all qualifying orders.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 1c. Staff Application Approved Email
    public static function staffApproval(string $name, string $roleName, string $loginUrl): string
    {
        $firstName = explode(' ', trim($name))[0];
        $storeName = store_setting('store_name', 'Noir & Co.');
        
        $html = self::renderHeader('Staff Application Approved — ' . $storeName);
        $html .= '<h1 style="color: #252820; font-size: 26px; font-weight: 700; margin: 0 0 14px 0; letter-spacing: -0.5px;">Application Approved</h1>
        <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 0 0 20px 0;">
            Congratulations ' . e($firstName) . ', your application to join the team at <strong>' . e($storeName) . '</strong> has been officially approved.
        </p>';
        
        $html .= '<div style="background-color: #f7f7f2; border: 1px solid #e8e9e0; border-radius: 12px; padding: 24px; margin: 24px 0;">
            <p style="font-size: 11px; font-weight: 700; color: #687158; text-transform: uppercase; letter-spacing: 1.2px; margin: 0 0 10px 0;">Assigned Position</p>
            <div style="font-size: 20px; font-weight: 700; color: #252820; margin-bottom: 12px;">' . e($roleName) . '</div>
            <p style="color: #777; font-size: 13px; line-height: 1.5; margin: 0;">
                Your staff portal account has been activated. You may now sign in using the credentials you provided during application.
            </p>
        </div>';
        
        $html .= '<div style="text-align: center; margin: 30px 0;">
            <a href="' . e($loginUrl) . '" class="button" style="padding: 16px 36px; font-size: 15px; font-weight: 600;">Sign In to Staff Portal ↗</a>
        </div>';
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 13px; margin: 0;">
            If you have any questions regarding your onboarding or permissions, please contact your administrator.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 1d. Staff Application Rejected Email
    public static function staffRejection(string $name, string $reason): string
    {
        $firstName = explode(' ', trim($name))[0];
        $storeName = store_setting('store_name', 'Noir & Co.');
        
        $html = self::renderHeader('Staff Application Status — ' . $storeName);
        $html .= '<h1 style="color: #252820; font-size: 24px; font-weight: 700; margin: 0 0 14px 0;">Staff Application Update</h1>
        <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 0 0 20px 0;">
            Dear ' . e($firstName) . ', thank you for your interest in joining the team at <strong>' . e($storeName) . '</strong>.
        </p>
        <p style="color: #555; line-height: 1.6; font-size: 15px; margin: 0 0 20px 0;">
            After careful review of your application, we regret to inform you that we are unable to offer you a position at this time.
        </p>';
        
        if (!empty($reason) && $reason !== 'Application was not approved.') {
            $html .= '<div style="background-color: #fcf8f7; border: 1px solid #f2d6d0; border-radius: 10px; padding: 18px; margin: 20px 0;">
                <strong style="color: #a43a28; font-size: 13px; text-transform: uppercase; letter-spacing: 0.8px; display: block; margin-bottom: 6px;">Feedback from Administrator</strong>
                <p style="color: #555; font-size: 14px; margin: 0; line-height: 1.5;">' . e($reason) . '</p>
            </div>';
        }
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 13px; margin: 0;">
            We appreciate the time you took to apply and wish you the best in your future endeavors.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 2. Password Reset OTP Email
    public static function passwordResetOTP(string $name, string $otpCode, string $resetUrl, int $expiryMinutes = 15): string
    {
        $firstName = explode(' ', trim($name))[0];
        
        $html = self::renderHeader('Your Noir & Co. verification code');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Reset your password</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            We received a request to reset your password for your Noir & Co. account.
        </p>';
        
        $html .= '<div class="info-card" style="text-align: center;">
            <div class="info-card-label">Your verification code</div>
            <div style="font-size: 32px; font-weight: 600; letter-spacing: 0.2em; color: #687158; margin: 10px 0;">' . e($otpCode) . '</div>
            <p style="color: #777; font-size: 13px; margin: 10px 0 0 0;">This code expires in ' . $expiryMinutes . ' minutes</p>
        </div>';
        
        $html .= '<p style="color: #777; font-size: 14px; margin: 20px 0;">
            <strong>For your security, never share this code with anyone.</strong>
        </p>';
        
        $html .= self::renderButton('Reset Password', $resetUrl);
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            If you did not request this password reset, please ignore this message or contact support if you believe your account may be at risk.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }



    // 5. Staff Role Change Email
    public static function staffRoleChange(string $name, string $oldRole, string $newRole, string $dashboardUrl): string
    {
        $firstName = explode(' ', trim($name))[0];
        
        $html = self::renderHeader('Your Noir & Co. role has been updated');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Your role has been updated</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your role within the Noir & Co. system has been updated.
        </p>';
        
        $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0;">
            ' . self::renderInfoCard('Previous Role', $oldRole) . '
            ' . self::renderInfoCard('New Role', $newRole) . '
        </div>';
        
        $html .= '<p style="color: #555; line-height: 1.6; margin: 20px 0;">
            Your new role may provide different access levels and permissions within the Noir & Co. administration system.
        </p>';
        
        $html .= self::renderButton('Access Staff Dashboard', $dashboardUrl);
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 6. Staff Suspension Email
    public static function staffSuspension(string $name, string $reason = ''): string
    {
        $firstName = explode(' ', trim($name))[0];
        
        $html = self::renderHeader('Your Noir & Co. staff account has been suspended');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Account Access Update</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your Noir & Co. staff account has been suspended. Access to the staff dashboard and related systems is currently unavailable.
        </p>';
        
        if ($reason) {
            $html .= self::renderInfoCard('Reason', $reason);
        }
        
        $html .= '<p style="color: #555; line-height: 1.6; margin: 20px 0;">
            If you believe this is an error or need clarification, please contact the administration team.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 7. Staff Reactivation Email
    public static function staffReactivation(string $name, string $dashboardUrl): string
    {
        $firstName = explode(' ', trim($name))[0];
        
        $html = self::renderHeader('Your Noir & Co. staff account has been reactivated');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Your account is active again</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your Noir & Co. staff account has been reactivated. Your access to the staff dashboard has been restored.
        </p>';
        
        $html .= self::renderButton('Access Staff Dashboard', $dashboardUrl);
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            If you have any questions, please reach out to your supervisor or the administration team.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 8. Order Confirmation Email
    public static function orderConfirmation(array $order, array $items): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Order ' . e($order['order_number']) . ' confirmed — Noir & Co.');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Thank you for your order, ' . e($firstName) . '</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your order has been successfully placed. We\'ll send you updates as your order progresses.
        </p>';
        
        $html .= self::renderDivider();
        
        // Order Summary
        $html .= '<h2 style="color: #252820; font-size: 18px; margin: 0 0 15px 0;">Order Summary</h2>';
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        $html .= self::renderInfoCard('Order Date', date('F j, Y', strtotime($order['order_date'] ?? 'now')));
        $html .= self::renderInfoCard('Payment Status', self::renderStatusBadge($order['payment_status'] ?? 'Pending', 'pending'));
        
        // Items
        $html .= '<h3 style="color: #252820; font-size: 16px; margin: 25px 0 15px 0;">Items</h3>';
        foreach ($items as $item) {
            $html .= '<div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e5e5;">
                <div>
                    <div style="font-weight: 500; color: #252820;">' . e($item['name']) . '</div>
                    <div style="font-size: 13px; color: #777;">Qty: ' . (int)$item['quantity'] . '</div>
                </div>
                <div style="font-weight: 500; color: #252820;">' . e($order['currency'] ?? '₦') . number_format((float)$item['total'], 2) . '</div>
            </div>';
        }
        
        // Totals
        $html .= '<div style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; padding: 8px 0; color: #555;">
                <span>Subtotal</span>
                <span>' . e($order['currency'] ?? '₦') . number_format((float)($order['subtotal'] ?? 0), 2) . '</span>
            </div>';
        
        if (!empty($order['shipping_amount'])) {
            $html .= '<div style="display: flex; justify-content: space-between; padding: 8px 0; color: #555;">
                <span>Shipping</span>
                <span>' . e($order['currency'] ?? '₦') . number_format((float)$order['shipping_amount'], 2) . '</span>
            </div>';
        }
        
        if (!empty($order['discount_amount'])) {
            $html .= '<div style="display: flex; justify-content: space-between; padding: 8px 0; color: #687158;">
                <span>Discount</span>
                <span>-' . e($order['currency'] ?? '₦') . number_format((float)$order['discount_amount'], 2) . '</span>
            </div>';
        }
        
        $html .= '<div style="display: flex; justify-content: space-between; padding: 12px 0; border-top: 2px solid #252820; font-weight: 600; font-size: 18px; color: #252820;">
            <span>Total</span>
            <span>' . e($order['currency'] ?? '₦') . number_format((float)($order['total'] ?? 0), 2) . '</span>
        </div>
        </div>';
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('View Order', $publicBase . '/customer/orders/' . e($order['order_number']));
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            If you have any questions about your order, please contact our customer support team.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 9. Payment Confirmation Email
    public static function paymentConfirmation(array $order): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Payment confirmed for order ' . e($order['order_number']));
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Payment confirmed</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your payment for order ' . e($order['order_number']) . ' has been successfully received.
        </p>';
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        $html .= self::renderInfoCard('Payment Amount', e($order['currency'] ?? '₦') . number_format((float)($order['total'] ?? 0), 2));
        $html .= self::renderInfoCard('Payment Date', date('F j, Y', strtotime($order['payment_date'] ?? 'now')));
        
        if (!empty($order['payment_method'])) {
            $html .= self::renderInfoCard('Payment Method', $order['payment_method']);
        }
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('View Order', $publicBase . '/customer/orders/' . e($order['order_number']));
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 10. Payment Failed Email
    public static function paymentFailed(array $order): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Payment could not be completed');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Payment unsuccessful</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            We were unable to complete your payment for order ' . e($order['order_number']) . '. This may be due to insufficient funds, an expired card, or a temporary issue with your payment provider.
        </p>';
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        $html .= self::renderInfoCard('Amount', e($order['currency'] ?? '₦') . number_format((float)($order['total'] ?? 0), 2));
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('Try Payment Again', $publicBase . '/store/checkout');
        $html .= self::renderButton('View Order', $publicBase . '/customer/orders/' . e($order['order_number']));
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            If you continue to experience issues, please contact your payment provider or our customer support team for assistance.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 11. Order Processing Email
    public static function orderProcessing(array $order): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Your Noir & Co. order is being prepared');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">We\'re preparing your order</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Good news, ' . e($firstName) . '. Your order ' . e($order['order_number']) . ' is now being processed and prepared for shipment.
        </p>';
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('View Order', $publicBase . '/customer/orders/' . e($order['order_number']));
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            We\'ll send you another email as soon as your order ships.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 12. Order Shipped Email
    public static function orderShipped(array $order, string $trackingNumber, string $trackingUrl): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Your Noir & Co. order has shipped');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Your order is on the way</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Great news, ' . e($firstName) . '. Your order ' . e($order['order_number']) . ' has been shipped and is on its way to you.
        </p>';
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        $html .= self::renderInfoCard('Tracking Number', $trackingNumber);
        
        $html .= self::renderButton('Track My Order', $trackingUrl);
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            You can track your package\'s journey using the tracking number above. If you have any questions about your delivery, please don\'t hesitate to reach out.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 13. Out for Delivery Email
    public static function outForDelivery(array $order, string $trackingUrl = ''): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Your Noir & Co. order is out for delivery');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Your order is on its way to you</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your order ' . e($order['order_number']) . ' is out for delivery and should arrive soon.
        </p>';
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        
        if ($trackingUrl) {
            $html .= self::renderButton('Track Delivery', $trackingUrl);
        }
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            Please ensure someone is available to receive the package. If you won\'t be available, consider contacting the carrier directly.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 14. Order Delivered Email
    public static function orderDelivered(array $order): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Your Noir & Co. order has been delivered');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Your order has arrived</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your order ' . e($order['order_number']) . ' has been delivered. We hope you enjoy your new pieces from Noir & Co.
        </p>';
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('View Order', $publicBase . '/customer/orders/' . e($order['order_number']));
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            If you have any issues with your delivery or if anything doesn\'t meet your expectations, please contact our customer support team.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 15. Order Cancelled Email
    public static function orderCancelled(array $order, string $reason = ''): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Order ' . e($order['order_number']) . ' has been cancelled');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Your order has been cancelled</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your order ' . e($order['order_number']) . ' has been cancelled.
        </p>';
        
        if ($reason) {
            $html .= self::renderInfoCard('Reason', $reason);
        }
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        
        if (!empty($order['refund_status'])) {
            $html .= self::renderInfoCard('Refund Status', self::renderStatusBadge($order['refund_status'], 'pending'));
        }
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('View Order', $publicBase . '/customer/orders/' . e($order['order_number']));
        
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            If you have any questions about this cancellation or need assistance with a new order, please contact our customer support team.
        </p>';
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 16. Refund Completed Email
    public static function refundCompleted(array $order): string
    {
        $firstName = explode(' ', trim($order['customer_name'] ?? 'Customer'))[0];
        
        $html = self::renderHeader('Your Noir & Co. refund has been processed');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Your refund is on its way</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            Your refund for order ' . e($order['order_number']) . ' has been processed and is being returned to your original payment method.
        </p>';
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        $html .= self::renderInfoCard('Refund Amount', e($order['currency'] ?? '₦') . number_format((float)($order['refund_amount'] ?? 0), 2));
        $html .= self::renderInfoCard('Refund Date', date('F j, Y', strtotime($order['refund_date'] ?? 'now')));
        $html .= self::renderInfoCard('Refund Status', self::renderStatusBadge('Completed', 'success'));
        
        $html .= '<p style="color: #555; line-height: 1.6; margin: 20px 0;">
            Depending on your payment provider, it may take 5-10 business days for the refund to appear in your account.
        </p>';
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('View Order', $publicBase . '/customer/orders/' . e($order['order_number']));
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 17. New Customer Notification (Staff)
    public static function newCustomerNotification(array $customer): string
    {
        $html = self::renderHeader('New customer registered on Noir & Co.');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">New Customer Registration</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            A new customer has registered on Noir & Co.
        </p>';
        
        $html .= self::renderInfoCard('Customer Name', $customer['name']);
        $html .= self::renderInfoCard('Email', $customer['email']);
        $html .= self::renderInfoCard('Registration Date', date('F j, Y', strtotime($customer['created_at'] ?? 'now')));
        
        if (!empty($customer['public_id'])) {
            $html .= self::renderInfoCard('Customer ID', $customer['public_id']);
        }
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('View Customer', $publicBase . '/admin/customers');
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 18. New Order Staff Email
    public static function newOrderStaffNotification(array $order): string
    {
        $html = self::renderHeader('New order received — ' . e($order['order_number']));
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">New Order Received</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            A new order has been placed on Noir & Co.
        </p>';
        
        $html .= self::renderInfoCard('Customer', $order['customer_name'] ?? 'N/A');
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        $html .= self::renderInfoCard('Order Total', e($order['currency'] ?? '₦') . number_format((float)($order['total'] ?? 0), 2));
        $html .= self::renderInfoCard('Payment Status', self::renderStatusBadge($order['payment_status'] ?? 'Pending', 'pending'));
        $html .= self::renderInfoCard('Order Date', date('F j, Y', strtotime($order['order_date'] ?? 'now')));
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('View Order', $publicBase . '/admin/orders');
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 19. Payment Approval Needed Email
    public static function paymentApprovalNeeded(array $order): string
    {
        $html = self::renderHeader('Payment approval required — ' . e($order['order_number']));
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">Payment requires review</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            A payment requires your approval for order ' . e($order['order_number']) . '.
        </p>';
        
        $html .= self::renderInfoCard('Order Number', $order['order_number']);
        $html .= self::renderInfoCard('Customer', $order['customer_name'] ?? 'N/A');
        $html .= self::renderInfoCard('Payment Method', $order['payment_method'] ?? 'Bank Transfer');
        $html .= self::renderInfoCard('Amount', e($order['currency'] ?? '₦') . number_format((float)($order['total'] ?? 0), 2));
        $html .= self::renderInfoCard('Submitted Date', date('F j, Y', strtotime($order['payment_date'] ?? 'now')));
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('Review Payment', $publicBase . '/admin/orders');
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 20. New Staff Application Email
    public static function newStaffApplication(array $application): string
    {
        $html = self::renderHeader('New staff application received');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">New Staff Application</h1>
        <p style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">
            A new staff application has been submitted.
        </p>';
        
        $html .= self::renderInfoCard('Applicant Name', $application['name']);
        $html .= self::renderInfoCard('Email', $application['email']);
        $html .= self::renderInfoCard('Application Date', date('F j, Y', strtotime($application['created_at'] ?? 'now')));
        
        if (!empty($application['position'])) {
            $html .= self::renderInfoCard('Requested Position', $application['position']);
        }
        
        $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $html .= self::renderButton('Review Application', $publicBase . '/admin/staff-applications');
        
        $html .= self::renderFooter();
        
        return $html;
    }

    // 21. Company Announcement Email
    public static function companyAnnouncement(string $title, string $message, string $ctaText = '', string $ctaUrl = '', string $bannerImage = ''): string
    {
        $html = self::renderHeader(e($title));
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">' . e($title) . '</h1>';
        
        if ($bannerImage) {
            $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $html .= '<img src="' . e($publicBase . $bannerImage) . '" alt="Announcement banner" style="max-width: 100%; border-radius: 8px; margin: 20px 0;">';
        }
        
        $html .= '<div style="color: #555; line-height: 1.6; margin: 0 0 20px 0;">' . nl2br(e($message)) . '</div>';
        
        if ($ctaText && $ctaUrl) {
            $html .= self::renderButton($ctaText, $ctaUrl);
        }
        
        $html .= self::renderFooter(true);
        
        return $html;
    }

    // 22. Company Important Update Email
    public static function companyImportantUpdate(string $title, string $message): string
    {
        $html = self::renderHeader('Important update from Noir & Co.');
        $html .= '<h1 style="color: #252820; font-size: 24px; margin: 0 0 16px 0;">An important update from Noir & Co.</h1>';
        $html .= '<h2 style="color: #687158; font-size: 18px; margin: 0 0 16px 0;">' . e($title) . '</h2>';
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 14px; margin: 0;">
            If you have any questions about this update, please contact our customer support team.
        </p>';
        $html .= self::renderFooter();
        return $html;
    }

    // 23. Direct Staff Message Template
    public static function staffDirectMessage(string $name, string $subject, string $bodyText, string $senderName = 'System Administration'): string
    {
        $html = self::renderHeader(e($subject));
        $html .= '<p style="color: #687158; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px 0;">Official Staff Directive</p>';
        $html .= '<h1 style="color: #252820; font-size: 22px; font-weight: 700; margin: 0 0 16px 0;">' . e($subject) . '</h1>';
        $html .= '<p style="color: #444; font-size: 15px; margin: 0 0 16px 0;">Dear ' . e($name) . ',</p>';
        $html .= '<div style="background-color: #f7f7f2; border-left: 3px solid #687158; padding: 18px 20px; border-radius: 4px 8px 8px 4px; color: #252820; font-size: 14px; line-height: 1.65; margin: 20px 0;">' . nl2br(e($bodyText)) . '</div>';
        $html .= '<p style="color: #888; font-size: 12px; margin: 20px 0 0 0;">Sent by: <strong style="color: #252820;">' . e($senderName) . '</strong></p>';
        $html .= self::renderDivider();
        $html .= '<p style="color: #777; font-size: 12px; margin: 0;">This communication was dispatched directly to your staff profile. Please sign in to your staff dashboard to view or respond.</p>';
        $html .= self::renderFooter(true);
        return $html;
    }
}
