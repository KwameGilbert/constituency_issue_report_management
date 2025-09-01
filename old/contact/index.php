<?php
require_once '../config/db.php';

// Process form submission
$success_message = '';
$error_message = '';

// Define form variables with default empty values
$name = $email = $phone = $subject = $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    // If subject is empty, provide a default one
    if (empty($subject)) {
        $subject = "General Inquiry from Website";
    }
    
    // Simple validation
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Store in database
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
        
        // Check if database insertion was successful        composer require phpmailer/phpmailer
        $db_success = $stmt->execute();
        
        // Get the ID of the inserted message for reference
        $message_id = $conn->insert_id;
        
        // Prepare email notification
        $admin_email = 'kwamegilbert1114@gmail.com'; // Change to your admin email
        // <!-- // $cc_email = 'issues@swma.gov.gh'; // Additional recipient if needed -->
        
        // Email subject with reference number
        $email_subject = "New Contact Form Message #" . $message_id . ": " . $subject;
        
        // Email headers
        $headers = "From: Website Contact Form <noreply@swma.rf.gd>\r\n";
        $headers .= "Reply-To: $name <$email>\r\n";
        if (!empty($cc_email)) {
            $headers .= "Cc: $cc_email\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Email body in HTML format
        $email_body = "
        <html>
        <head>
            <title>New Contact Form Submission</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
                h2 { color: #c53030; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                td { padding: 8px; border-bottom: 1px solid #eee; }
                .label { font-weight: bold; width: 120px; }
                .message { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #c53030; }
                .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>New Contact Form Submission</h2>
                <p>A new message has been submitted through the website contact form.</p>
                
                <table>
                    <tr>
                        <td class='label'>Reference:</td>
                        <td>#$message_id</td>
                    </tr>
                    <tr>
                        <td class='label'>Name:</td>
                        <td>" . htmlspecialchars($name) . "</td>
                    </tr>
                    <tr>
                        <td class='label'>Email:</td>
                        <td><a href='mailto:$email'>" . htmlspecialchars($email) . "</a></td>
                    </tr>";
        
        if (!empty($phone)) {
            $email_body .= "
                    <tr>
                        <td class='label'>Phone:</td>
                        <td>" . htmlspecialchars($phone) . "</td>
                    </tr>";
        }
        
        $email_body .= "
                    <tr>
                        <td class='label'>Subject:</td>
                        <td>" . htmlspecialchars($subject) . "</td>
                    </tr>
                    <tr>
                        <td class='label'>Date:</td>
                        <td>" . date('F j, Y, g:i a') . "</td>
                    </tr>
                </table>
                
                <h3>Message:</h3>
                <div class='message'>" . nl2br(htmlspecialchars($message)) . "</div>
                
                <div class='footer'>
                    <p>This is an automated message from the Sefwi Wiawso Constituency website.</p>
                    <p>To reply to this message, simply reply to this email or contact the sender directly.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Attempt to send email
        $mail_success = false;
        
        // Use PHPMailer if it's available (for better reliability), otherwise use mail()
        if (file_exists('../vendor/autoload.php')) {
            require '../vendor/autoload.php';
            
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer();
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'kwamegilbert1114@gmail.com'; // Your Gmail address
                $mail->Password = 'vowl uaqn dovs dtid'; // Your Gmail app-specific password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('noreply@swma.rf.gd', 'Website Contact Form');
                $mail->addAddress($admin_email);
                if (!empty($cc_email)) {
                    $mail->addCC($cc_email);
                }
                $mail->addReplyTo($email, $name);
                
                $mail->isHTML(true);
                $mail->Subject = $email_subject;
                $mail->Body = $email_body;
                
                $mail_success = $mail->send();
            } catch (Exception $e) {
                // Log the error but don't show to user
                error_log('Email sending failed: ' . $e->getMessage());
            }
        } else {
            // Fallback to PHP's mail() function
            $mail_success = mail($admin_email, $email_subject, $email_body, $headers);
        }
        
        // Auto-responder to the sender
        if ($mail_success || $db_success) {
            // Prepare auto-response email
            $auto_subject = "Thank you for contacting Sefwi Wiawso Constituency Office";
            
            $auto_headers = "From: Sefwi Wiawso Constituency Office <info@swma.rf.gd>\r\n";
            $auto_headers .= "MIME-Version: 1.0\r\n";
            $auto_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $auto_body = "
            <html>
            <head>
                <title>Thank You for Your Message</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
                    h2 { color: #c53030; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                    .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Thank You for Contacting Us</h2>
                    <p>Dear " . htmlspecialchars($name) . ",</p>
                    <p>Thank you for contacting the Office of the Member of Parliament for Sefwi Wiawso Constituency. This is an automatic confirmation that we have received your message.</p>
                    <p>A member of our team will review your message and get back to you as soon as possible.</p>
                    <p>For your reference, here's a summary of your message:</p>
                    <ul>
                        <li><strong>Subject:</strong> " . htmlspecialchars($subject) . "</li>
                        <li><strong>Date Submitted:</strong> " . date('F j, Y, g:i a') . "</li>
                        <li><strong>Reference Number:</strong> #$message_id</li>
                    </ul>
                    <p>If your matter is urgent, please call our office directly at (+233) 242 560 140.</p>
                    <p>Thank you for your patience.</p>
                    <p>Best regards,<br>
                    Constituency Office<br>
                    Sefwi Wiawso</p>
                    
                    <div class='footer'>
                        <p>This is an automated response. Please do not reply to this email.</p>
                        <p>© 2025 Office of the Member of Parliament, Sefwi Wiawso Constituency</p>
                    </div>
                </div>
            </body>
            </html>";
            
            // Try to send auto-response (don't worry if this fails)
            if (isset($mail) && $mail instanceof PHPMailer\PHPMailer\PHPMailer) {
                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer();
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'kwamegilbert1114@gmail.com'; // Your Gmail address
                    $mail->Password = 'vowl uaqn dovs dtid'; // Your Gmail app-specific password
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                        
                    $mail->setFrom('info@swma.rf.gd', 'Sefwi Wiawso Constituency Office');
                    $mail->addAddress($email, $name);
                    
                    $mail->isHTML(true);
                    $mail->Subject = $auto_subject;
                    $mail->Body = $auto_body;
                    
                    $mail->send();
                } catch (Exception $e) {
                    // Just log, don't show error to user
                    error_log('Auto-response email failed: ' . $e->getMessage());
                }
            } else {
                // Fallback to mail()
                mail($email, $auto_subject, $auto_body, $auto_headers);
            }
        }
        
        // Set success message
        if ($db_success) {
            $success_message = "Thank you for your message. We'll get back to you soon!";
            
            // Clear form data after successful submission
            $name = $email = $phone = $subject = $message = '';
        } else {
            $error_message = "Sorry, there was an error saving your message. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Sefwi Wiawso Constituency</title>
    <meta name="description"
        content="Get in touch with the Office of the Member of Parliament for Sefwi Wiawso Constituency. Report issues, suggest projects, or request information.">
     <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Header/Navbar -->
    <?php include_once '../includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="bg-amber-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col items-center text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Contact Us</h1>
                <p class="text-lg max-w-2xl">We're here to listen to your concerns, questions, and suggestions. Reach
                    out to us using any of the methods below.</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contact Information -->
            <div class="lg:col-span-1 bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6 bg-red-700 text-white">
                    <h2 class="text-2xl font-semibold mb-2">Contact Information</h2>
                    <p class="text-sm">Reach out to us directly through these channels</p>
                </div>

                <div class="p-6 space-y-6">
                    <div class="flex items-start space-x-4">
                        <div class="text-red-600 text-xl mt-1">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Office Address</h3>
                            <address class="not-italic text-gray-600 text-sm mt-1 space-y-1">
                                <div>MP's Office, Sefwi Wiawso Municipal Assembly</div>
                                <div>P.O Box 25, Sefwi Wiawso</div>
                                <div>Western North Region, Ghana</div>
                                <div>Ghana Post GPS: WG-0002-7111</div>
                            </address>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="text-red-600 text-xl mt-1">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Phone Numbers</h3>
                            <div class="text-gray-600 text-sm mt-1 space-y-1">
                                <div>Constituency Office: <a href="tel:+233242560140" class="hover:text-red-600">(+233)
                                        242 560 140</a></div>
                                <div>Constituency Secretary: <a href="tel:+233548531963"
                                        class="hover:text-red-600">(+233) 548 531 963</a></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="text-red-600 text-xl mt-1">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Email Addresses</h3>
                            <div class="text-gray-600 text-sm mt-1 space-y-1">
                                <div>General Inquiries: <a href="mailto:info@swma.gov.gh"
                                        class="hover:text-red-600">info@swma.gov.gh</a></div>
                                <div>Constituency Issues: <a href="mailto:issues@swma.gov.gh"
                                        class="hover:text-red-600">issues@swma.gov.gh</a></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4">
                        <div class="text-red-600 text-xl mt-1">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Office Hours</h3>
                            <div class="text-gray-600 text-sm mt-1">
                                <div>Monday – Friday: 8:00 AM – 5:00 PM</div>
                                <div>Weekends: Closed (Emergencies only)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="pt-4 border-t border-gray-200">
                        <h3 class="font-medium text-gray-900 mb-3">Follow Us</h3>
                        <div class="flex space-x-4">
                            <a href="#"
                                class="w-9 h-9 rounded-full bg-red-600 text-white flex items-center justify-center hover:bg-red-700">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#"
                                class="w-9 h-9 rounded-full bg-red-600 text-white flex items-center justify-center hover:bg-red-700">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#"
                                class="w-9 h-9 rounded-full bg-red-600 text-white flex items-center justify-center hover:bg-red-700">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#"
                                class="w-9 h-9 rounded-full bg-red-600 text-white flex items-center justify-center hover:bg-red-700">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="lg:col-span-2 bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6 bg-red-700 text-white">
                    <h2 class="text-2xl font-semibold mb-2">Send Us a Message</h2>
                    <p class="text-sm">Fill out the form below and we'll get back to you as soon as possible</p>
                </div>

                <div class="p-6">
                    <?php if (!empty($success_message)): ?>
                    <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-md">
                        <i class="fas fa-check-circle mr-2"></i> <?= $success_message ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-md">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?= $error_message ?>
                    </div>
                    <?php endif; ?>

                    <form action="" method="post" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span
                                        class="text-red-600">*</span></label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address
                                    <span class="text-red-600">*</span></label>
                                <input type="email" id="email" name="email"
                                    value="<?= htmlspecialchars($email ?? '') ?>" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone
                                    Number</label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>

                            <!-- Subject -->
                            <div>
                                <label for="subject"
                                    class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <input type="text" id="subject" name="subject"
                                    value="<?= htmlspecialchars($subject ?? '') ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>
                        </div>

                        <!-- Message -->
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message <span
                                    class="text-red-600">*</span></label>
                            <textarea id="message" name="message" rows="6" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500"><?= htmlspecialchars($message ?? '') ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit"
                                class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-paper-plane mr-2"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="mt-12 bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-6 bg-amber-700 text-white">
                <h2 class="text-2xl font-semibold mb-2">Find Us</h2>
                <p class="text-sm">Visit our constituency office in Sefwi Wiawso</p>
            </div>
            <div class="aspect-w-16 aspect-h-9 h-96">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15882.88282507253!2d-2.4950197304687507!3d6.209724399999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xfddc71006b5af4b%3A0x7c3e42e24e01f96!2sSefwi%20Wiawso%20Municipal%20Assembly!5e0!3m2!1sen!2sgh!4v1682434523540!5m2!1sen!2sgh"
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include_once '../includes/footer.php'; ?>

    <script>
    // Form validation enhancement
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, textarea');

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && this.value.trim() === '') {
                    this.classList.add('border-red-500', 'bg-red-50');

                    // Check if error message already exists
                    let nextElement = this.nextElementSibling;
                    if (!nextElement || !nextElement.classList.contains('text-red-500')) {
                        const errorMessage = document.createElement('p');
                        errorMessage.textContent = 'This field is required';
                        errorMessage.classList.add('text-red-500', 'text-xs', 'mt-1');
                        this.insertAdjacentElement('afterend', errorMessage);
                    }
                } else if (this.type === 'email' && this.value.trim() !== '' && !validateEmail(
                        this.value)) {
                    this.classList.add('border-red-500', 'bg-red-50');

                    let nextElement = this.nextElementSibling;
                    if (!nextElement || !nextElement.classList.contains('text-red-500')) {
                        const errorMessage = document.createElement('p');
                        errorMessage.textContent = 'Please enter a valid email address';
                        errorMessage.classList.add('text-red-500', 'text-xs', 'mt-1');
                        this.insertAdjacentElement('afterend', errorMessage);
                    }
                } else {
                    this.classList.remove('border-red-500', 'bg-red-50');

                    // Remove error message if it exists
                    let nextElement = this.nextElementSibling;
                    if (nextElement && nextElement.classList.contains('text-red-500')) {
                        nextElement.remove();
                    }
                }
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('border-red-500')) {
                    this.classList.remove('border-red-500', 'bg-red-50');

                    // Remove error message
                    let nextElement = this.nextElementSibling;
                    if (nextElement && nextElement.classList.contains('text-red-500')) {
                        nextElement.remove();
                    }
                }
            });
        });

        function validateEmail(email) {
            const re =
                /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
    });
    </script>
</body>

</html>