<?php
// filepath: c:\xampp\htdocs\swma\email-services\mail.php

// PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Require PHPMailer autoloader if not already included
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    // If you don't have composer, include the files directly
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require_once __DIR__ . '/../libs/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../libs/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/phpmailer/src/SMTP.php';
    }
}

// SMTP Configuration constants
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USERNAME', 'kwamegilbert1114@gmail.com');
define('MAIL_PASSWORD', 'vowl uaqn dovs dtid');
define('MAIL_FROM_NAME', 'Constituency Management System');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
define('MAIL_CHARSET', 'UTF-8');
define('MAIL_ENCODING', 'base64');

/**
 * Creates and configures a PHPMailer instance with Gmail SMTP settings
 *
 * @param bool $exceptions Whether to throw exceptions on errors (default: true)
 * @param bool $debug Whether to enable debug output (default: false)
 * @param int $debugLevel Debug level if debug is enabled (default: 2 - server and client messages)
 * @return PHPMailer Configured PHPMailer instance ready to use
 */
function createMailer($exceptions = true, $debug = false, $debugLevel = 2) {
    // Create a new PHPMailer instance
    $mail = new PHPMailer($exceptions);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;
    
    // Set debug level if needed 
    // (0 = no output, 1 = client messages, 2 = client and server messages, 
    // 3 = verbose debug output, 4 = low-level debug output)
    $mail->SMTPDebug = $debug ? $debugLevel : SMTP::DEBUG_OFF;
    
    // If debugging is enabled, use error_log instead of echo for production environment
    if ($debug) {
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer [$level]: $str");
        };
    }
    
    // Set default sender
    $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
    
    // Character set and encoding
    $mail->CharSet = MAIL_CHARSET;
    $mail->Encoding = MAIL_ENCODING;
    
    // Enable SMTP keep alive to avoid connection issues with multiple emails
    $mail->SMTPKeepAlive = true;
    
    // Set timeout value to avoid long waiting periods if mail server is unavailable
    $mail->Timeout = 10;
    
    return $mail;
}

/**
 * Sends an email using the Gmail SMTP configuration
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $altBody Plain text alternative (optional)
 * @param array $attachments List of file paths to attach (optional)
 * @param array $cc List of CC email addresses (optional)
 * @param array $bcc List of BCC email addresses (optional)
 * @param string $replyTo Reply-to email address (optional)
 * @param string $replyToName Reply-to name (optional)
 * @param bool $debug Whether to enable debug output (default: false)
 * @return array Status array with 'success' (bool) and 'message' (string)
 */
function sendEmail($to, $subject, $body, $altBody = '', $attachments = [], $cc = [], $bcc = [], $replyTo = '', $replyToName = '', $debug = false) {
    try {
        $mail = createMailer(true, $debug);
        
        // Validate recipient email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => "Invalid recipient email address: $to"
            ];
        }
        
        // Recipients
        $mail->addAddress($to);
        
        // Add CC recipients
        foreach ($cc as $ccAddress) {
            if (filter_var($ccAddress, FILTER_VALIDATE_EMAIL)) {
                $mail->addCC($ccAddress);
            }
        }
        
        // Add BCC recipients
        foreach ($bcc as $bccAddress) {
            if (filter_var($bccAddress, FILTER_VALIDATE_EMAIL)) {
                $mail->addBCC($bccAddress);
            }
        }
        
        // Add reply-to if provided
        if (!empty($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo, $replyToName);
        }
        
        // Add attachments if any
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment);
            } else {
                error_log("Mail attachment not found: $attachment");
            }
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        
        // Send the email
        if (!$mail->send()) {
            throw new Exception($mail->ErrorInfo);
        }
        
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Email could not be sent. Error: {$e->getMessage()}"
        ];
    }
}

/**
 * Test the email configuration
 * 
 * @param string $testEmail Email address to send test email to
 * @param bool $debug Whether to enable debug output
 * @return array Status array with 'success' (bool) and 'message' (string)
 */
function testEmailConfiguration($testEmail, $debug = true) {
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => "Invalid email address for testing: $testEmail"
        ];
    }
    
    $subject = 'Email Configuration Test - Constituency Management System';
    
    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <div style="background-color: #006b3f; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Email Configuration Test</h1>
        </div>
        <div style="padding: 20px; border: 1px solid #ddd; border-top: none;">
            <p>Hello,</p>
            
            <p>This is a test email to verify that your email configuration for the <strong>Constituency Management System</strong> is working correctly.</p>
            
            <div style="background-color: #f8f9fa; border-left: 4px solid #006b3f; padding: 15px; margin: 20px 0;">
                <p style="margin: 0;"><strong>Server:</strong> ' . MAIL_HOST . '</p>
                <p style="margin: 5px 0 0 0;"><strong>Sender:</strong> ' . MAIL_USERNAME . '</p>
                <p style="margin: 5px 0 0 0;"><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
            </div>
            
            <p>If you received this email, your email configuration is working correctly.</p>
            
            <p>Regards,<br>
            System Administrator<br>
            Constituency Management System</p>
        </div>
        <div style="background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;">
            <p>Government of Ghana | Ministry of Local Government and Rural Development</p>
            <p>&copy; ' . date('Y') . ' Republic of Ghana. All rights reserved.</p>
        </div>
    </div>';
    
    $textBody = "
Email Configuration Test - Constituency Management System

Hello,

This is a test email to verify that your email configuration for the Constituency Management System is working correctly.

Server: " . MAIL_HOST . "
Sender: " . MAIL_USERNAME . "
Time: " . date('Y-m-d H:i:s') . "

If you received this email, your email configuration is working correctly.

Regards,
System Administrator
Constituency Management System

Government of Ghana | Ministry of Local Government and Rural Development
© " . date('Y') . " Republic of Ghana. All rights reserved.";
    
    return sendEmail($testEmail, $subject, $body, $textBody, [], [], [], '', '', $debug);
}

/**
 * Send email asynchronously (in background without waiting for response)
 * Use this when sending emails shouldn't delay the user experience
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $altBody Plain text alternative (optional)
 * @param array $attachments List of file paths to attach (optional)
 * @param array $cc List of CC email addresses (optional)
 * @param array $bcc List of BCC email addresses (optional)
 * @return bool Whether the request was dispatched successfully
 */
function sendEmailAsync($to, $subject, $body, $altBody = '', $attachments = [], $cc = [], $bcc = []) {
    // Get the server's document root (should work in most server configurations)
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 1);
    
    // Create data to be passed to the background script
    $emailData = [
        'to' => $to,
        'subject' => $subject,
        'body' => $body,
        'altBody' => $altBody,
        'attachments' => $attachments,
        'cc' => $cc,
        'bcc' => $bcc,
        'timestamp' => time()
    ];
    
    // Convert to JSON and encode to avoid command injection
    $encodedData = base64_encode(json_encode($emailData));
    
    // Path to the PHP interpreter and the background email script
    $phpPath = PHP_BINARY ?: 'php';
    $scriptPath = __DIR__ . '/send_email_async.php';
    
    if (!file_exists($scriptPath)) {
        // Create the async script if it doesn't exist
        $asyncScript = '<?php
// Background email sending script
// This file is automatically created by mail.php

// Prevent direct access
if (PHP_SAPI !== "cli") {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

// Include mail functions
require_once __DIR__ . "/mail.php";

// Check if argument is provided
if ($argc < 2) {
    error_log("send_email_async.php: No data provided");
    exit(1);
}

// Decode the email data
$data = json_decode(base64_decode($argv[1]), true);
if (!$data) {
    error_log("send_email_async.php: Invalid data format");
    exit(1);
}

// Extract email parameters
$to = $data["to"] ?? "";
$subject = $data["subject"] ?? "";
$body = $data["body"] ?? "";
$altBody = $data["altBody"] ?? "";
$attachments = $data["attachments"] ?? [];
$cc = $data["cc"] ?? [];
$bcc = $data["bcc"] ?? [];

// Send the email
$result = sendEmail($to, $subject, $body, $altBody, $attachments, $cc, $bcc);

// Log the result
if ($result["success"]) {
    error_log("Background email sent successfully to: {$to}");
} else {
    error_log("Background email failed for {$to}: {$result["message"]}");
}
';
        file_put_contents($scriptPath, $asyncScript);
    }
    
    // Determine OS type to use appropriate command
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if ($isWindows) {
        // Windows (no output, run in background)
        $command = "{$phpPath} {$scriptPath} {$encodedData} >NUL 2>NUL";
        pclose(popen("start /B " . $command, "r"));
    } else {
        // Linux/Unix/MacOS (redirect to /dev/null, run in background)
        $command = "{$phpPath} {$scriptPath} '{$encodedData}' > /dev/null 2>&1 &";
        exec($command);
    }
    
    return true;
}

// Example usage:
/*
// Method 1: Using the sendEmail helper function for immediate sending
$result = sendEmail(
    'recipient@example.com',
    'Test Subject',
    '<h1>Hello World!</h1><p>This is a test email.</p>',
    'Hello World! This is a test email.',
    ['/path/to/attachment.pdf'],
    ['cc@example.com'],
    ['bcc@example.com']
);

if ($result['success']) {
    echo $result['message'];
} else {
    echo 'Error: ' . $result['message'];
}

// Method 2: Using the createMailer function for more control
try {
    $mail = createMailer();
    $mail->addAddress('recipient@example.com');
    $mail->Subject = 'Custom Email';
    $mail->Body = '<h1>Custom HTML Content</h1>';
    $mail->send();
    echo 'Email sent successfully';
} catch (Exception $e) {
    echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

// Method 3: Using the asynchronous method (for login notifications, etc.)
sendEmailAsync(
    'user@example.com',
    'Security Alert',
    '<h1>Login Notification</h1><p>A login was detected on your account.</p>'
);
*/