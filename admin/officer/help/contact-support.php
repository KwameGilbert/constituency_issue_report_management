<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if (!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Set active page for sidebar
$active_page = 'help';
$pageTitle = 'Contact Support';
$basePath = '../';

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    // Get form data
    $subject = trim($_POST['subject']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    $officer_id = $_SESSION['officer_id'];
    
    // Basic validation
    if (empty($subject) || empty($description) || empty($category)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert support ticket into database
        // Note: You would need to create a support_tickets table in your database
        try {
            // Simulate ticket creation (Replace this with actual database insertion)
            // $insert_query = "INSERT INTO support_tickets (officer_id, subject, category, description, priority, status, created_at) 
            //                 VALUES (?, ?, ?, ?, ?, 'open', NOW())";
            // $insert_stmt = $conn->prepare($insert_query);
            // $insert_stmt->bind_param("issss", $officer_id, $subject, $category, $description, $priority);
            // $insert_stmt->execute();
            
            // For now, just simulate success
            // $ticket_id = $insert_stmt->insert_id;
            $ticket_id = "T" . sprintf("%06d", rand(1, 999999)); // Generate random ticket ID
            
            $success_message = "Support ticket created successfully! Your ticket ID is: <strong>$ticket_id</strong>. We'll respond to your inquiry as soon as possible.";
        } catch (Exception $e) {
            $error_message = "Error creating support ticket: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support | Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in-out forwards;
    }

    .staggered-item {
        opacity: 0;
        animation: fadeIn 0.5s ease-out forwards;
    }

    /* Custom form input styling for better visibility */
    input[type="text"],
    input[type="email"],
    textarea,
    select {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        width: 100%;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    textarea:focus,
    select:focus {
        outline: none;
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    }

    input:hover,
    textarea:hover,
    select:hover {
        border-color: #f59e0b !important;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar component -->
        <?php include_once '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header component -->
            <?php include_once '../includes/header.php'; ?>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Action Bar -->
                    <div
                        class="bg-gradient-to-r from-amber-600 to-amber-800 rounded-xl shadow-lg mb-6 p-6 text-white fade-in">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <h1 class="text-2xl font-bold">Contact Support</h1>
                                <p class="mt-1 opacity-90">Get help with your questions or technical issues</p>
                            </div>
                            <div class="mt-4 md:mt-0">
                                <a href="user-guide.php"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-amber-800 bg-white hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white transition-colors duration-300">
                                    <i class="fas fa-book mr-2"></i>
                                    View User Guide
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 fade-in"
                        role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 fade-in"
                        role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Support Options -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-lg shadow-sm p-6 staggered-item"
                                style="animation-delay: 0.1s;">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Support Options</h2>

                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div
                                            class="flex-shrink-0 h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                                            <i class="fas fa-ticket-alt"></i>
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-sm font-medium text-gray-900">Submit a Ticket</h3>
                                            <p class="text-sm text-gray-600">Create a support ticket for technical
                                                issues or questions.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div
                                            class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                            <i class="fas fa-phone-alt"></i>
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-sm font-medium text-gray-900">Phone Support</h3>
                                            <p class="text-sm text-gray-600">Call us at <a href="tel:+233302123456"
                                                    class="text-blue-600 hover:underline">+233 30 212 3456</a> (Mon-Fri,
                                                8am-5pm)</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div
                                            class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-sm font-medium text-gray-900">Email Support</h3>
                                            <p class="text-sm text-gray-600">Send an email to <a
                                                    href="mailto:support@constituency.gov.gh"
                                                    class="text-blue-600 hover:underline">support@constituency.gov.gh</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 border-t border-gray-200 pt-4">
                                    <h3 class="text-md font-medium text-gray-900 mb-3">Business Hours</h3>
                                    <dl class="space-y-1 text-sm text-gray-600">
                                        <div class="flex justify-between">
                                            <dt>Monday - Friday:</dt>
                                            <dd>8:00 AM - 5:00 PM</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt>Saturday:</dt>
                                            <dd>9:00 AM - 12:00 PM</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt>Sunday:</dt>
                                            <dd>Closed</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="mt-6 border-t border-gray-200 pt-4">
                                    <h3 class="text-md font-medium text-gray-900 mb-3">Emergency Contact</h3>
                                    <p class="text-sm text-gray-600">For urgent matters outside business hours, please
                                        call:</p>
                                    <a href="tel:+233302123457"
                                        class="mt-1 inline-block text-amber-600 font-medium hover:underline">+233 30 212
                                        3457</a>
                                </div>
                            </div>

                            <!-- FAQ section -->
                            <div class="bg-white rounded-lg shadow-sm p-6 mt-6 staggered-item"
                                style="animation-delay: 0.2s;">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Popular FAQs</h2>
                                <ul class="space-y-3">
                                    <li>
                                        <a href="user-guide.php#reporting-issues"
                                            class="text-amber-700 hover:underline flex items-start">
                                            <i class="fas fa-question-circle mt-1 mr-2 text-amber-500"></i>
                                            <span>How do I report a new issue?</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="user-guide.php#managing-issues"
                                            class="text-amber-700 hover:underline flex items-start">
                                            <i class="fas fa-question-circle mt-1 mr-2 text-amber-500"></i>
                                            <span>Why was my issue rejected?</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="user-guide.php#profile"
                                            class="text-amber-700 hover:underline flex items-start">
                                            <i class="fas fa-question-circle mt-1 mr-2 text-amber-500"></i>
                                            <span>How do I change my password?</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="user-guide.php#reports-analytics"
                                            class="text-amber-700 hover:underline flex items-start">
                                            <i class="fas fa-question-circle mt-1 mr-2 text-amber-500"></i>
                                            <span>How can I generate a monthly report?</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="user-guide.php#faqs"
                                            class="text-amber-700 hover:underline flex items-start">
                                            <i class="fas fa-list mt-1 mr-2 text-amber-500"></i>
                                            <span>View all FAQs</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Support Ticket Form -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-lg shadow-sm p-6 staggered-item"
                                style="animation-delay: 0.3s;">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Submit a Support Ticket</h2>
                                <p class="text-gray-600 mb-6">Please provide detailed information about your issue so we
                                    can assist you better.</p>

                                <form action="" method="POST">
                                    <div class="space-y-4">
                                        <div>
                                            <label for="subject"
                                                class="block text-sm font-medium text-gray-700 mb-1">Subject <span
                                                    class="text-red-500">*</span></label>
                                            <input type="text" name="subject" id="subject" required
                                                class="w-full rounded-md border-gray-300"
                                                placeholder="Brief description of your issue">
                                        </div>

                                        <div>
                                            <label for="category"
                                                class="block text-sm font-medium text-gray-700 mb-1">Issue Category
                                                <span class="text-red-500">*</span></label>
                                            <select name="category" id="category" required
                                                class="w-full rounded-md border-gray-300">
                                                <option value="">Select category</option>
                                                <option value="account">Account Access</option>
                                                <option value="reporting">Issue Reporting</option>
                                                <option value="technical">Technical Problem</option>
                                                <option value="feature">Feature Request</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="description"
                                                class="block text-sm font-medium text-gray-700 mb-1">Description <span
                                                    class="text-red-500">*</span></label>
                                            <textarea name="description" id="description" rows="5" required
                                                class="w-full rounded-md border-gray-300"
                                                placeholder="Please describe your issue in detail. Include steps to reproduce, error messages, and what you've already tried."></textarea>
                                        </div>

                                        <div>
                                            <label for="priority"
                                                class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                            <select name="priority" id="priority"
                                                class="w-full rounded-md border-gray-300">
                                                <option value="low">Low - Minor issue, no urgency</option>
                                                <option value="medium" selected>Medium - Standard issue affecting work
                                                </option>
                                                <option value="high">High - Serious issue that blocks work</option>
                                                <option value="critical">Critical - System unavailable or data loss
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="attachment"
                                                class="block text-sm font-medium text-gray-700 mb-1">Screenshot or
                                                Attachment (optional)</label>
                                            <input type="file" name="attachment" id="attachment"
                                                class="w-full rounded-md border-gray-300 text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 mt-1">
                                            <p class="mt-1 text-xs text-gray-500">Acceptable formats: JPG, PNG, PDF.
                                                Maximum size: 5MB</p>
                                        </div>

                                        <div class="mt-6">
                                            <button type="submit" name="submit_ticket"
                                                class="w-full bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-md font-medium transition-colors duration-300 flex items-center justify-center">
                                                <i class="fas fa-paper-plane mr-2"></i> Submit Ticket
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Self-Help Resources -->
                            <div class="bg-white rounded-lg shadow-sm p-6 mt-6 staggered-item"
                                style="animation-delay: 0.4s;">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Self-Help Resources</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <a href="user-guide.php"
                                        class="group block p-4 bg-amber-50 rounded-lg hover:bg-amber-100 transition-colors duration-300">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 rounded-full bg-amber-200 flex items-center justify-center text-amber-700 group-hover:bg-amber-300 transition-colors duration-300">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div class="ml-4">
                                                <h3 class="text-sm font-medium text-gray-900">User Guide</h3>
                                                <p class="text-xs text-gray-600">Comprehensive documentation for all
                                                    features</p>
                                            </div>
                                        </div>
                                    </a>

                                    <a href="#"
                                        class="group block p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors duration-300">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 group-hover:bg-blue-300 transition-colors duration-300">
                                                <i class="fas fa-video"></i>
                                            </div>
                                            <div class="ml-4">
                                                <h3 class="text-sm font-medium text-gray-900">Video Tutorials</h3>
                                                <p class="text-xs text-gray-600">Step-by-step visual guides</p>
                                            </div>
                                        </div>
                                    </a>

                                    <a href="#"
                                        class="group block p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-300">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 rounded-full bg-green-200 flex items-center justify-center text-green-700 group-hover:bg-green-300 transition-colors duration-300">
                                                <i class="fas fa-cogs"></i>
                                            </div>
                                            <div class="ml-4">
                                                <h3 class="text-sm font-medium text-gray-900">Troubleshooting</h3>
                                                <p class="text-xs text-gray-600">Solutions to common problems</p>
                                            </div>
                                        </div>
                                    </a>

                                    <a href="#"
                                        class="group block p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors duration-300">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-200 flex items-center justify-center text-purple-700 group-hover:bg-purple-300 transition-colors duration-300">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                            <div class="ml-4">
                                                <h3 class="text-sm font-medium text-gray-900">Training Resources</h3>
                                                <p class="text-xs text-gray-600">Advanced guides and best practices</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation for staggered items
        document.querySelectorAll('.staggered-item').forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = "1";
            }, 100 * index);
        });
    });
    </script>
</body>

</html>