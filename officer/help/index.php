<?php
// help/index.php - Officer Help & Support Center
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'help';

// Process support ticket submission if form is submitted
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'medium');
    $officer_id = $_SESSION['user_id'];

    // Basic validation
    if (empty($subject) || empty($description) || empty($category)) {
        $message = "Please fill in all required fields.";
        $message_type = 'error';
    } else {
        // Generate ticket ID and simulate success
        $ticket_id = "T" . sprintf("%06d", rand(1, 999999));
        $message = "Support ticket created successfully! Your ticket ID is: <strong>$ticket_id</strong>. We'll respond to your inquiry as soon as possible.";
        $message_type = 'success';

        // In a real implementation, you'd save this to a support_tickets table
        try {
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, action, details, created_at)
                VALUES (?, 'support_ticket_created', ?, NOW())
            ");
            $stmt->execute([$officer_id, "Support ticket created: $subject (ID: $ticket_id)"]);
        } catch (Exception $e) {
            error_log("Failed to log support ticket: " . $e->getMessage());
        }
    }
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Dashboard',
        'href' => '../dashboard/',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

$userName = $_SESSION['user_name'] ?? 'Officer';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Help & Support - Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        success: '#10b981',
                        warning: '#f59e0b',
                        error: '#ef4444',
                        info: '#3b82f6',
                        slate: {
                            50: '#f8fafc',
                            900: '#0f172a',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <style>
        .accordion-item {
            transition: all 0.3s ease;
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .accordion-content.active {
            max-height: 500px;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen font-sans">
    <?php renderOfficerSidebar($current_page, getPendingIssuesCount($conn)); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderOfficerHeader('Help & Support Center', 'Get assistance and learn how to use the system effectively', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Quick Access Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-book text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">User Guide</h3>
                            <p class="text-xs text-gray-500">Complete documentation</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-question-circle text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">FAQs</h3>
                            <p class="text-xs text-gray-500">Common questions</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-headset text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Contact Support</h3>
                            <p class="text-xs text-gray-500">Get direct help</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-tools text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Troubleshooting</h3>
                            <p class="text-xs text-gray-500">Solve common issues</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Getting Started Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-play-circle text-indigo-900 mr-3"></i>
                                Getting Started
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="prose max-w-none text-gray-700">
                                <p class="mb-4">Welcome to the SWMA Officer Dashboard! This system helps you efficiently manage issues, oversee agents, and generate comprehensive reports.</p>

                                <h3 class="text-lg font-medium text-gray-900 mb-3">System Overview</h3>
                                <p class="mb-4">As an officer, you can:</p>
                                <ul class="list-disc ml-5 mb-4 space-y-1">
                                    <li>Review and approve issues submitted by field agents</li>
                                    <li>Manage agent accounts and monitor their performance</li>
                                    <li>Generate detailed reports and analytics</li>
                                    <li>Track issue resolution progress and trends</li>
                                    <li>Communicate with agents and provide feedback</li>
                                </ul>

                                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                                    <div class="flex">
                                        <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
                                        <div>
                                            <p class="text-sm text-blue-800">
                                                Your dashboard provides real-time insights into system performance. Check it regularly for updates and pending tasks.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Guide Sections -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-book text-indigo-900 mr-3"></i>
                                Officer Guide
                            </h2>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <!-- Dashboard Section -->
                            <div class="accordion-item">
                                <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-tachometer-alt text-gray-500 mr-3"></i>
                                        <div>
                                            <h3 class="font-medium text-gray-900">Using the Dashboard</h3>
                                            <p class="text-sm text-gray-500">Overview of key metrics and quick actions</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform"></i>
                                </button>
                                <div class="accordion-content px-6 pb-6">
                                    <div class="text-sm text-gray-700 space-y-3">
                                        <p>The dashboard provides a comprehensive overview of your system activities:</p>
                                        <ul class="list-disc ml-5 space-y-1">
                                            <li><strong>Summary Cards</strong>: View total issues, pending reviews, and agent statistics</li>
                                            <li><strong>Recent Issues</strong>: See the latest issues submitted by agents</li>
                                            <li><strong>Performance Charts</strong>: Monitor trends and resolution rates</li>
                                            <li><strong>Quick Actions</strong>: Access frequently used features</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Issues Management -->
                            <div class="accordion-item">
                                <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-clipboard-list text-gray-500 mr-3"></i>
                                        <div>
                                            <h3 class="font-medium text-gray-900">Managing Issues</h3>
                                            <p class="text-sm text-gray-500">Review, approve, and track issue resolution</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform"></i>
                                </button>
                                <div class="accordion-content px-6 pb-6">
                                    <div class="text-sm text-gray-700 space-y-3">
                                        <p>Issue management involves several key steps:</p>
                                        <ol class="list-decimal ml-5 space-y-1">
                                            <li><strong>Review Submissions</strong>: Examine issues submitted by agents</li>
                                            <li><strong>Verify Information</strong>: Check location, severity, and details</li>
                                            <li><strong>Update Status</strong>: Approve, reject, or request more information</li>
                                            <li><strong>Track Progress</strong>: Monitor resolution through to completion</li>
                                        </ol>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-xs text-yellow-800">
                                                <strong>Tip:</strong> Use filters to quickly find specific issues by status, severity, or agent.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Agent Management -->
                            <div class="accordion-item">
                                <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-users text-gray-500 mr-3"></i>
                                        <div>
                                            <h3 class="font-medium text-gray-900">Agent Management</h3>
                                            <p class="text-sm text-gray-500">Oversee agent accounts and performance</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform"></i>
                                </button>
                                <div class="accordion-content px-6 pb-6">
                                    <div class="text-sm text-gray-700 space-y-3">
                                        <p>Effective agent management includes:</p>
                                        <ul class="list-disc ml-5 space-y-1">
                                            <li><strong>Account Creation</strong>: Add new field agents to the system</li>
                                            <li><strong>Profile Management</strong>: Update agent information and assignments</li>
                                            <li><strong>Performance Monitoring</strong>: Track issue submission rates and quality</li>
                                            <li><strong>Status Control</strong>: Activate or deactivate agent accounts</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Reports & Analytics -->
                            <div class="accordion-item">
                                <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-chart-bar text-gray-500 mr-3"></i>
                                        <div>
                                            <h3 class="font-medium text-gray-900">Reports & Analytics</h3>
                                            <p class="text-sm text-gray-500">Generate insights and performance reports</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform"></i>
                                </button>
                                <div class="accordion-content px-6 pb-6">
                                    <div class="text-sm text-gray-700 space-y-3">
                                        <p>The reporting system provides comprehensive analytics:</p>
                                        <ul class="list-disc ml-5 space-y-1">
                                            <li><strong>Performance Metrics</strong>: Track resolution rates and response times</li>
                                            <li><strong>Agent Analytics</strong>: Monitor individual agent performance</li>
                                            <li><strong>Trend Analysis</strong>: Identify patterns in issue reporting</li>
                                            <li><strong>Custom Reports</strong>: Generate reports for specific time periods</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Frequently Asked Questions -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-question-circle text-indigo-900 mr-3"></i>
                                Frequently Asked Questions
                            </h2>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div class="accordion-item">
                                <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                                    <span class="font-medium text-gray-900">How do I approve or reject an issue?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform"></i>
                                </button>
                                <div class="accordion-content px-6 pb-6">
                                    <p class="text-sm text-gray-700">Navigate to the Issues section, click on the issue you want to review, and use the status update options to approve or reject with comments explaining your decision.</p>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                                    <span class="font-medium text-gray-900">Can I modify agent assignments?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform"></i>
                                </button>
                                <div class="accordion-content px-6 pb-6">
                                    <p class="text-sm text-gray-700">Yes, you can edit agent profiles to change their electoral area assignments, contact information, and account status through the Agent Management section.</p>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                                    <span class="font-medium text-gray-900">How do I generate monthly reports?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform"></i>
                                </button>
                                <div class="accordion-content px-6 pb-6">
                                    <p class="text-sm text-gray-700">Go to the Reports section, select your desired date range and filters, then use the export options to generate reports in PDF or Excel format.</p>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <button onclick="toggleAccordion(this)" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                                    <span class="font-medium text-gray-900">What should I do if an agent reports duplicate issues?</span>
                                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform"></i>
                                </button>
                                <div class="accordion-content px-6 pb-6">
                                    <p class="text-sm text-gray-700">Review both issues carefully, merge information if necessary, reject the duplicate with an explanation, and provide feedback to the agent to prevent future duplicates.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Contact Support -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-headset text-indigo-900 mr-2"></i>
                                Contact Support
                            </h3>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="" class="space-y-4">
                                <div>
                                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">
                                        Subject <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="subject" name="subject" required
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900"
                                        placeholder="Brief description of your issue">
                                </div>

                                <div>
                                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select id="category" name="category" required
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900">
                                        <option value="">Select category</option>
                                        <option value="account">Account Access</option>
                                        <option value="issues">Issue Management</option>
                                        <option value="agents">Agent Management</option>
                                        <option value="reports">Reports & Analytics</option>
                                        <option value="technical">Technical Problem</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                    <select id="priority" name="priority"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900">
                                        <option value="low">Low - Minor issue</option>
                                        <option value="medium" selected>Medium - Standard issue</option>
                                        <option value="high">High - Urgent issue</option>
                                        <option value="critical">Critical - System down</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                        Description <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="description" name="description" rows="4" required
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900"
                                        placeholder="Please describe your issue in detail..."></textarea>
                                </div>

                                <button type="submit" name="submit_ticket"
                                    class="w-full bg-indigo-900 hover:bg-indigo-800 text-white py-2.5 px-4 rounded-lg font-medium transition-colors text-sm">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Ticket
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Support Information -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-800">Support Information</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                    <i class="fas fa-phone text-blue-600 text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Phone Support</h4>
                                    <p class="text-sm text-gray-600">+233 30 212 3456</p>
                                    <p class="text-xs text-gray-500">Mon-Fri, 8am-5pm</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                    <i class="fas fa-envelope text-green-600 text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Email Support</h4>
                                    <p class="text-sm text-gray-600">support@swma.gov.gh</p>
                                    <p class="text-xs text-gray-500">24-48 hour response</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Emergency</h4>
                                    <p class="text-sm text-gray-600">+233 30 212 3457</p>
                                    <p class="text-xs text-gray-500">24/7 for critical issues</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-800">Quick Tips</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3 text-sm">
                                <div class="flex items-start">
                                    <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                                    <p class="text-gray-700">Review issues promptly to maintain system efficiency</p>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                                    <p class="text-gray-700">Use filters to quickly find specific issues or agents</p>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                                    <p class="text-gray-700">Generate regular reports to track performance trends</p>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                                    <p class="text-gray-700">Provide clear feedback when rejecting issues</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleAccordion(button) {
            const content = button.nextElementSibling;
            const icon = button.querySelector('.fa-chevron-down');
            const isActive = content.classList.contains('active');

            // Close all other accordions
            document.querySelectorAll('.accordion-content').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelectorAll('.fa-chevron-down').forEach(item => {
                item.classList.remove('rotate-180');
            });

            // Toggle current accordion
            if (!isActive) {
                content.classList.add('active');
                icon.classList.add('rotate-180');
            }
        }

        // Smooth scroll for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId !== '#') {
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            const headerOffset = 80;
                            const elementPosition = targetElement.getBoundingClientRect().top;
                            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                            window.scrollTo({
                                top: offsetPosition,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>