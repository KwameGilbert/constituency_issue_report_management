<?php
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
