<?php
/**
 * Email Configuration Test Script
 * 
 * Use this script to test if your email configuration is working correctly.
 * Run this file directly in the browser or via command line to send a test email.
 */

// Include the mail functions
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/email-templates.php';

// Set this to true to see detailed debug information
$debug = true;

// Add your test email address here
$testEmailAddress = 'kwamegilbert1114@gmail.com'; // Change this to your real email

// Header
echo "==== Constituency Management System - Email Test ====\n\n";

// Test standard email delivery
echo "Testing standard email delivery...\n";
$standardResult = testEmailConfiguration($testEmailAddress, $debug);

if ($standardResult['success']) {
    echo "✓ Standard email test successful! Check your inbox.\n";
} else {
    echo "✗ Standard email test failed: {$standardResult['message']}\n";
    echo "Please check your SMTP settings in mail.php\n";
}

echo "\n";

// Test template system
echo "Testing email template system...\n";

// Security notification template test
$securityTemplateResult = sendSecurityNotificationEmail(
    $testEmailAddress,
    'Test User',
    'success_login'
);

if ($securityTemplateResult['success']) {
    echo "✓ Security notification template test successful! Check your inbox.\n";
} else {
    echo "✗ Security notification template test failed: {$securityTemplateResult['message']}\n";
}

echo "\n";

// Test async email functionality
echo "Testing asynchronous email delivery...\n";
$asyncResult = sendEmailAsync(
    $testEmailAddress,
    'Async Email Test',
    '<h1>Asynchronous Email Test</h1><p>This email was sent in the background.</p>',
    'Asynchronous Email Test - This email was sent in the background.'
);

if ($asyncResult) {
    echo "✓ Asynchronous email dispatched successfully!\n";
    echo "Check your inbox in a few moments.\n";
} else {
    echo "✗ Failed to dispatch asynchronous email.\n";
}

echo "\n";
echo "==== Test Complete ====\n";
echo "If the tests were successful, you should receive 3 emails shortly.\n";
echo "Remember to update the \$testEmailAddress in this script with your actual email.\n";