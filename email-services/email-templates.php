<?php
/**
 * Email templates for the constituency management system
 * These templates can be easily modified without changing the core application code
 */

/**
 * Generates a successful login notification email
 * 
 * @param string $name User's name
 * @param string $time Login time (formatted)
 * @param string $ipAddress IP address where login occurred
 * @param string $device Device information
 * @return array Associative array with subject, html_body, and text_body
 */
function getSuccessfulLoginTemplate($name, $time, $ipAddress, $device) {
    $subject = "Security Alert: Successful Login to Constituency Management System";
    
    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #006b3f; color: white; padding: 20px; text-align: center;'>
            <h1 style='margin: 0;'>Security Notification</h1>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd; border-top: none;'>
            <p>Hello {$name},</p>
            
            <p>We detected a successful login to your <strong>Constituency Management System</strong> account.</p>
            
            <div style='background-color: #f8f9fa; border-left: 4px solid #006b3f; padding: 15px; margin: 20px 0;'>
                <p style='margin: 0;'><strong>Time:</strong> {$time}</p>
                <p style='margin: 5px 0 0 0;'><strong>IP Address:</strong> {$ipAddress}</p>
                <p style='margin: 5px 0 0 0;'><strong>Device:</strong> {$device}</p>
            </div>
            
            <p>If this was you, no further action is required.</p>
            
            <p>If you did not initiate this login, please contact our support team immediately at <a href='mailto:support@localgov.gh'>support@localgov.gh</a> or call 030 222 3344.</p>
            
            <p>Thank you for helping us keep your account secure.</p>
            
            <p>Regards,<br>
            Security Team<br>
            Constituency Management System</p>
        </div>
        <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
            <p>Government of Ghana | Ministry of Local Government and Rural Development</p>
            <p>&copy; " . date('Y') . " Republic of Ghana. All rights reserved.</p>
        </div>
    </div>";
    
    $text_body = "
Hello {$name},

We detected a successful login to your Constituency Management System account.

Time: {$time}
IP Address: {$ipAddress}
Device: {$device}

If this was you, no further action is required.

If you did not initiate this login, please contact our support team immediately at support@localgov.gh or call 030 222 3344.

Thank you for helping us keep your account secure.

Regards,
Security Team
Constituency Management System

Government of Ghana | Ministry of Local Government and Rural Development
© " . date('Y') . " Republic of Ghana. All rights reserved.";
    
    return [
        'subject' => $subject,
        'html_body' => $html_body,
        'text_body' => $text_body
    ];
}

/**
 * Generates a failed login attempt notification email
 * 
 * @param string $name User's name
 * @param string $time Attempt time (formatted)
 * @param string $ipAddress IP address where attempt occurred
 * @param string $device Device information
 * @param string $reason Reason for failure (e.g., "incorrect password")
 * @return array Associative array with subject, html_body, and text_body
 */
function getFailedLoginTemplate($name, $time, $ipAddress, $device, $reason) {
    $subject = "Security Alert: Failed Login Attempt Detected";
    
    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #ce1126; color: white; padding: 20px; text-align: center;'>
            <h1 style='margin: 0;'>Security Alert</h1>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd; border-top: none;'>
            <p>Hello {$name},</p>
            
            <p>We detected a <strong>failed login attempt</strong> to your Constituency Management System account.</p>
            
            <div style='background-color: #fff3f3; border-left: 4px solid #ce1126; padding: 15px; margin: 20px 0;'>
                <p style='margin: 0;'><strong>Time:</strong> {$time}</p>
                <p style='margin: 5px 0 0 0;'><strong>IP Address:</strong> {$ipAddress}</p>
                <p style='margin: 5px 0 0 0;'><strong>Device:</strong> {$device}</p>
                <p style='margin: 5px 0 0 0;'><strong>Reason:</strong> {$reason}</p>
            </div>
            
            <p>If this was you, please try again with the correct credentials.</p>
            
            <p>If you did not attempt to log in, please take these steps immediately:</p>
            <ol>
                <li>Change your password immediately by visiting the reset password page</li>
                <li>Contact our support team at <a href='mailto:support@localgov.gh'>support@localgov.gh</a> or call 030 222 3344</li>
                <li>Review your account for any unauthorized activity</li>
            </ol>
            
            <p>Regards,<br>
            Security Team<br>
            Constituency Management System</p>
        </div>
        <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
            <p>Government of Ghana | Ministry of Local Government and Rural Development</p>
            <p>&copy; " . date('Y') . " Republic of Ghana. All rights reserved.</p>
        </div>
    </div>";
    
    $text_body = "
Hello {$name},

We detected a FAILED LOGIN ATTEMPT to your Constituency Management System account.

Time: {$time}
IP Address: {$ipAddress}
Device: {$device}
Reason: {$reason}

If this was you, please try again with the correct credentials.

If you did not attempt to log in, please take these steps immediately:
1. Change your password immediately by visiting the reset password page
2. Contact our support team at support@localgov.gh or call 030 222 3344
3. Review your account for any unauthorized activity

Regards,
Security Team
Constituency Management System

Government of Ghana | Ministry of Local Government and Rural Development
© " . date('Y') . " Republic of Ghana. All rights reserved.";
    
    return [
        'subject' => $subject,
        'html_body' => $html_body,
        'text_body' => $text_body
    ];
}

/**
 * Generates an account status update notification email
 * 
 * @param string $name User's name
 * @param string $status New account status (inactive/suspended/active)
 * @param string $reason Reason for the status change
 * @return array Associative array with subject, html_body, and text_body
 */
function getAccountStatusTemplate($name, $status, $reason) {
    $statusColor = match($status) {
        'active' => '#006b3f',
        'inactive' => '#f59e0b',
        'suspended' => '#ce1126',
        default => '#6b7280'
    };
    
    $statusText = match($status) {
        'active' => 'Activated',
        'inactive' => 'Deactivated',
        'suspended' => 'Suspended',
        default => ucfirst($status)
    };
    
    $subject = "Account Status Update: Your Account Has Been {$statusText}";
    
    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: {$statusColor}; color: white; padding: 20px; text-align: center;'>
            <h1 style='margin: 0;'>Account Status Update</h1>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd; border-top: none;'>
            <p>Hello {$name},</p>
            
            <p>This is to inform you that your Constituency Management System account has been <strong>{$statusText}</strong>.</p>
            
            <div style='background-color: #f8f9fa; border-left: 4px solid {$statusColor}; padding: 15px; margin: 20px 0;'>
                <p style='margin: 0;'><strong>New Status:</strong> {$statusText}</p>
                <p style='margin: 5px 0 0 0;'><strong>Reason:</strong> {$reason}</p>
                <p style='margin: 5px 0 0 0;'><strong>Effective:</strong> Immediately</p>
            </div>";
    
    if ($status == 'inactive' || $status == 'suspended') {
        $html_body .= "
            <p>If you believe this change was made in error or require further clarification, please contact our support team at <a href='mailto:support@localgov.gh'>support@localgov.gh</a> or call 030 222 3344.</p>";
    } else if ($status == 'active') {
        $html_body .= "
            <p>You can now log in to your account using your email and password.</p>";
    }
    
    $html_body .= "
            <p>Regards,<br>
            Administrative Team<br>
            Constituency Management System</p>
        </div>
        <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
            <p>Government of Ghana | Ministry of Local Government and Rural Development</p>
            <p>&copy; " . date('Y') . " Republic of Ghana. All rights reserved.</p>
        </div>
    </div>";
    
    $text_body = "
Hello {$name},

This is to inform you that your Constituency Management System account has been {$statusText}.

New Status: {$statusText}
Reason: {$reason}
Effective: Immediately
";

    if ($status == 'inactive' || $status == 'suspended') {
        $text_body .= "
If you believe this change was made in error or require further clarification, please contact our support team at support@localgov.gh or call 030 222 3344.";
    } else if ($status == 'active') {
        $text_body .= "
You can now log in to your account using your email and password.";
    }

    $text_body .= "

Regards,
Administrative Team
Constituency Management System

Government of Ghana | Ministry of Local Government and Rural Development
© " . date('Y') . " Republic of Ghana. All rights reserved.";
    
    return [
        'subject' => $subject,
        'html_body' => $html_body,
        'text_body' => $text_body
    ];
}

/**
 * Helper function to send a security notification email
 * 
 * @param string $email Recipient email address
 * @param string $name Recipient name
 * @param string $type Type of notification ('success_login', 'failed_login', 'account_status')
 * @param array $data Additional data needed for the template
 * @return array Status array with 'success' (bool) and 'message' (string)
 */
function sendSecurityNotificationEmail($email, $name, $type, $data = []) {
    require_once __DIR__ . '/mail.php';
    
    // Get user's device and IP information
    $device = $data['device'] ?? getUserDeviceInfo();
    $ipAddress = $data['ip'] ?? getUserIP();
    $time = $data['time'] ?? date('F j, Y, g:i a');
    
    // Select template based on notification type
    switch ($type) {
        case 'success_login':
            $template = getSuccessfulLoginTemplate($name, $time, $ipAddress, $device);
            break;
            
        case 'failed_login':
            $reason = $data['reason'] ?? 'Unknown reason';
            $template = getFailedLoginTemplate($name, $time, $ipAddress, $device, $reason);
            break;
            
        case 'account_status':
            $status = $data['status'] ?? 'unknown';
            $reason = $data['reason'] ?? 'Administrative decision';
            $template = getAccountStatusTemplate($name, $status, $reason);
            break;
            
        default:
            return [
                'success' => false,
                'message' => "Invalid notification type: $type"
            ];
    }
    
    // Send the email
    return sendEmail(
        $email,
        $template['subject'],
        $template['html_body'],
        $template['text_body']
    );
}

/**
 * Helper function to get user's IP address
 * 
 * @return string User's IP address
 */
function getUserIP() {
    $ip = '0.0.0.0';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Helper function to get user's device information
 * 
 * @return string User's device and browser information
 */
function getUserDeviceInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Simple browser detection
    $browser = 'Unknown Browser';
    if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
        $browser = 'Internet Explorer';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $browser = 'Mozilla Firefox';
    } elseif (strpos($userAgent, 'Chrome') !== false) {
        $browser = 'Google Chrome';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
        $browser = 'Opera';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        $browser = 'Microsoft Edge';
    }
    
    // Simple OS detection
    $os = 'Unknown OS';
    if (strpos($userAgent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        $os = 'MacOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
        $os = 'iOS';
    }
    
    return "$browser on $os";
}