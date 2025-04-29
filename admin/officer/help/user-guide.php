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
$pageTitle = 'User Guide';
$basePath = '../';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Guide | Field Officer Dashboard</title>
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
                                <h1 class="text-2xl font-bold">User Guide</h1>
                                <p class="mt-1 opacity-90">Learn how to use the system effectively</p>
                            </div>
                            <div class="mt-4 md:mt-0">
                                <a href="contact-support.php"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-amber-800 bg-white hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white transition-colors duration-300">
                                    <i class="fas fa-life-ring mr-2"></i>
                                    Contact Support
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Table of Contents -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item" style="animation-delay: 0.1s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Table of Contents</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <ul class="space-y-2 text-amber-700">
                                    <li>
                                        <a href="#getting-started" class="hover:underline flex items-center">
                                            <i class="fas fa-play-circle mr-2"></i> Getting Started
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#dashboard" class="hover:underline flex items-center">
                                            <i class="fas fa-tachometer-alt mr-2"></i> Using the Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#reporting-issues" class="hover:underline flex items-center">
                                            <i class="fas fa-plus-circle mr-2"></i> Reporting Issues
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#managing-issues" class="hover:underline flex items-center">
                                            <i class="fas fa-clipboard-list mr-2"></i> Managing Issues
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <ul class="space-y-2 text-amber-700">
                                    <li>
                                        <a href="#reports-analytics" class="hover:underline flex items-center">
                                            <i class="fas fa-chart-bar mr-2"></i> Reports & Analytics
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#profile" class="hover:underline flex items-center">
                                            <i class="fas fa-user-circle mr-2"></i> Profile Management
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#faqs" class="hover:underline flex items-center">
                                            <i class="fas fa-question-circle mr-2"></i> FAQs
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#troubleshooting" class="hover:underline flex items-center">
                                            <i class="fas fa-tools mr-2"></i> Troubleshooting
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Getting Started Section -->
                    <div id="getting-started" class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item"
                        style="animation-delay: 0.2s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Getting Started</h2>
                        <div class="prose max-w-none text-gray-700">
                            <p>Welcome to the Constituency Issue Management System! This guide will help you understand
                                how to effectively use the system to report and track issues in your community.</p>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">System Overview</h3>
                            <p>The Constituency Issue Management System is designed to:
                            </p>
                            <ul class="list-disc ml-5 mb-4 space-y-1">
                                <li>Streamline the process of reporting community issues</li>
                                <li>Track the progress of reported issues</li>
                                <li>Provide analytics and reports on issue resolution</li>
                                <li>Facilitate communication between field officers and supervisors</li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Logging In</h3>
                            <p>To access the system:</p>
                            <ol class="list-decimal ml-5 space-y-1 mb-4">
                                <li>Navigate to the login page</li>
                                <li>Enter your provided email and password</li>
                                <li>Click "Login" to access your dashboard</li>
                            </ol>

                            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-lightbulb text-amber-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-amber-800">
                                            <strong>Tip:</strong> If you've forgotten your password, click on the
                                            "Forgot Password" link and follow the instructions.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Navigating the Interface</h3>
                            <p>Once logged in, you'll see the main dashboard with:</p>
                            <ul class="list-disc ml-5 space-y-1">
                                <li>A sidebar for navigating to different sections</li>
                                <li>A header showing your current page and user information</li>
                                <li>The main content area displaying relevant information</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Dashboard Section -->
                    <div id="dashboard" class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item"
                        style="animation-delay: 0.3s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Using the Dashboard</h2>
                        <div class="prose max-w-none text-gray-700">
                            <p>The dashboard provides a quick overview of your activities and important information.</p>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Key Dashboard Elements</h3>
                            <ul class="list-disc ml-5 space-y-1 mb-4">
                                <li><strong>Summary Cards</strong>: Shows the count of total, pending, in-progress, and
                                    resolved issues</li>
                                <li><strong>Recent Issues</strong>: Displays your most recently reported issues</li>
                                <li><strong>Status Updates</strong>: Shows recent status changes to your reported issues
                                </li>
                                <li><strong>Quick Actions</strong>: Provides shortcuts to common tasks</li>
                            </ul>

                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-800">
                                            The dashboard is refreshed each time you log in or navigate to it. Make sure
                                            to check it regularly for updates.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Dashboard Charts</h3>
                            <p>The dashboard contains visual charts that help you quickly understand your issue data:
                            </p>
                            <ul class="list-disc ml-5 space-y-1">
                                <li><strong>Issue Status Chart</strong>: Breakdown of issues by their current status
                                </li>
                                <li><strong>Monthly Trend</strong>: Shows how many issues you've reported over time</li>
                                <li><strong>Severity Distribution</strong>: Visual representation of issues by severity
                                    level</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Reporting Issues Section -->
                    <div id="reporting-issues" class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item"
                        style="animation-delay: 0.4s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Reporting Issues</h2>
                        <div class="prose max-w-none text-gray-700">
                            <p>One of your primary responsibilities is to report community issues accurately.</p>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Creating a New Issue Report</h3>
                            <ol class="list-decimal ml-5 space-y-1 mb-4">
                                <li>Navigate to "Report New Issue" from the sidebar</li>
                                <li>Fill in all required fields in the form:
                                    <ul class="list-disc ml-5 mt-2 mb-2">
                                        <li><strong>Title</strong>: A concise description of the issue</li>
                                        <li><strong>Description</strong>: Detailed explanation of the problem</li>
                                        <li><strong>Location</strong>: Specific address or coordinates</li>
                                        <li><strong>Electoral Area</strong>: Select from the dropdown</li>
                                        <li><strong>Severity</strong>: Rate from Low to Critical</li>
                                        <li><strong>People Affected</strong>: Estimated number of citizens impacted</li>
                                    </ul>
                                </li>
                                <li>Add any relevant additional notes</li>
                                <li>Upload photos if available (optional but recommended)</li>
                                <li>Submit the report</li>
                            </ol>

                            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-lightbulb text-amber-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-amber-800">
                                            <strong>Tip:</strong> Be as specific as possible when describing issues.
                                            Clear, detailed reports are more likely to be addressed quickly.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Severity Levels Explained</h3>
                            <ul class="mb-4">
                                <li class="flex items-center mb-2">
                                    <span
                                        class="inline-block w-20 bg-red-100 text-red-800 text-xs font-medium py-1 px-2 rounded mr-2">Critical</span>
                                    <span>Immediate attention required; affects many people or poses safety risks</span>
                                </li>
                                <li class="flex items-center mb-2">
                                    <span
                                        class="inline-block w-20 bg-orange-100 text-orange-800 text-xs font-medium py-1 px-2 rounded mr-2">High</span>
                                    <span>Urgent issue that significantly impacts daily life or infrastructure</span>
                                </li>
                                <li class="flex items-center mb-2">
                                    <span
                                        class="inline-block w-20 bg-amber-100 text-amber-800 text-xs font-medium py-1 px-2 rounded mr-2">Medium</span>
                                    <span>Important but not urgent; should be addressed soon</span>
                                </li>
                                <li class="flex items-center">
                                    <span
                                        class="inline-block w-20 bg-green-100 text-green-800 text-xs font-medium py-1 px-2 rounded mr-2">Low</span>
                                    <span>Minor issue that causes inconvenience but not significant disruption</span>
                                </li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Adding Photos</h3>
                            <p>Photos provide valuable context for issues. To add photos:</p>
                            <ol class="list-decimal ml-5 space-y-1">
                                <li>Click "Choose Files" in the photo upload section</li>
                                <li>Select one or more photos from your device (max 5 photos)</li>
                                <li>Photos will be displayed as thumbnails before submission</li>
                                <li>You can remove photos before submitting if needed</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Managing Issues Section -->
                    <div id="managing-issues" class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item"
                        style="animation-delay: 0.5s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Managing Issues</h2>
                        <div class="prose max-w-none text-gray-700">
                            <p>After reporting issues, you'll need to monitor and manage them throughout their
                                lifecycle.</p>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Viewing Your Issues</h3>
                            <p>To see all the issues you've reported:</p>
                            <ol class="list-decimal ml-5 space-y-1 mb-4">
                                <li>Click on "My Issues" in the sidebar</li>
                                <li>Use filters to narrow down issues by status, severity, or date</li>
                                <li>Click on any issue title to view its details</li>
                            </ol>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Issue Status Lifecycle</h3>
                            <p>Issues go through different statuses as they're processed:</p>
                            <ul class="mb-4">
                                <li class="flex items-center mb-2">
                                    <span
                                        class="inline-block w-28 bg-yellow-100 text-yellow-800 text-xs font-medium py-1 px-2 rounded mr-2">Pending</span>
                                    <span>Newly reported; awaiting initial review</span>
                                </li>
                                <li class="flex items-center mb-2">
                                    <span
                                        class="inline-block w-28 bg-purple-100 text-purple-800 text-xs font-medium py-1 px-2 rounded mr-2">Under
                                        Review</span>
                                    <span>Being assessed by supervisors</span>
                                </li>
                                <li class="flex items-center mb-2">
                                    <span
                                        class="inline-block w-28 bg-blue-100 text-blue-800 text-xs font-medium py-1 px-2 rounded mr-2">In
                                        Progress</span>
                                    <span>Work is underway to resolve the issue</span>
                                </li>
                                <li class="flex items-center mb-2">
                                    <span
                                        class="inline-block w-28 bg-green-100 text-green-800 text-xs font-medium py-1 px-2 rounded mr-2">Resolved</span>
                                    <span>Issue has been successfully addressed</span>
                                </li>
                                <li class="flex items-center">
                                    <span
                                        class="inline-block w-28 bg-red-100 text-red-800 text-xs font-medium py-1 px-2 rounded mr-2">Rejected</span>
                                    <span>Issue was declined (reasons will be provided)</span>
                                </li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Editing Issues</h3>
                            <p>You can edit an issue as long as it's still in "Pending" status:</p>
                            <ol class="list-decimal ml-5 space-y-1 mb-4">
                                <li>Navigate to the issue detail page</li>
                                <li>Click the "Edit" button</li>
                                <li>Make necessary changes</li>
                                <li>Click "Save Changes"</li>
                            </ol>

                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-800">
                                            Once an issue moves beyond "Pending" status, you cannot edit it anymore. You
                                            can still add comments for clarification.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Adding Comments</h3>
                            <p>You can add comments to issues at any point to provide updates or additional information:
                            </p>
                            <ol class="list-decimal ml-5 space-y-1">
                                <li>Go to the issue detail page</li>
                                <li>Scroll to the comment section</li>
                                <li>Type your comment</li>
                                <li>Click "Submit Comment"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Reports & Analytics Section -->
                    <div id="reports-analytics" class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item"
                        style="animation-delay: 0.6s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Reports & Analytics</h2>
                        <div class="prose max-w-none text-gray-700">
                            <p>The reporting section helps you analyze your issue data and generate formal reports.</p>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Using the Reports Dashboard</h3>
                            <p>The reports dashboard provides various visualizations and metrics:</p>
                            <ul class="list-disc ml-5 space-y-1 mb-4">
                                <li><strong>Key Metrics Cards</strong>: Shows important statistics like total issues and
                                    resolution rate</li>
                                <li><strong>Status Breakdown</strong>: Pie chart showing the distribution of issue
                                    statuses</li>
                                <li><strong>Monthly Trend</strong>: Line chart tracking issues over time</li>
                                <li><strong>Electoral Area Breakdown</strong>: Shows which areas have the most reported
                                    issues</li>
                                <li><strong>Severity Distribution</strong>: Bar chart of issues by severity level</li>
                            </ul>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Filtering Report Data</h3>
                            <p>You can filter the data displayed in reports:</p>
                            <ol class="list-decimal ml-5 space-y-1 mb-4">
                                <li>Use the Time Period dropdown to select a date range (week, month, quarter, year, or
                                    all time)</li>
                                <li>All charts and metrics will update automatically to reflect the selected time period
                                </li>
                            </ol>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Generating Downloadable Reports</h3>
                            <p>To create a formal report for download or printing:</p>
                            <ol class="list-decimal ml-5 space-y-1">
                                <li>Navigate to the "Generate Report" section</li>
                                <li>Select a time period</li>
                                <li>Choose a report format (PDF, Excel, or printable HTML)</li>
                                <li>Click "Generate Report"</li>
                                <li>The report will be created and downloaded/displayed according to your selected
                                    format</li>
                            </ol>

                            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-lightbulb text-amber-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-amber-800">
                                            <strong>Tip:</strong> Generate monthly reports for your supervisor to
                                            showcase your progress and the impact of your work.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Management Section -->
                    <div id="profile" class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item"
                        style="animation-delay: 0.7s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Profile Management</h2>
                        <div class="prose max-w-none text-gray-700">
                            <p>Keep your profile information updated to ensure proper communication.</p>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Viewing Your Profile</h3>
                            <p>To access your profile:</p>
                            <ol class="list-decimal ml-5 space-y-1 mb-4">
                                <li>Click on "My Profile" in the sidebar</li>
                                <li>View your personal information and account statistics</li>
                            </ol>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Updating Profile Information</h3>
                            <p>To edit your profile:</p>
                            <ol class="list-decimal ml-5 space-y-1 mb-4">
                                <li>Go to the "Edit Profile" section</li>
                                <li>Update your name, email, or phone number</li>
                                <li>Click "Save Changes"</li>
                            </ol>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Changing Your Password</h3>
                            <p>For security, you should change your password periodically:</p>
                            <ol class="list-decimal ml-5 space-y-1">
                                <li>Go to the "Change Password" section on your profile page</li>
                                <li>Enter your current password</li>
                                <li>Enter and confirm your new password</li>
                                <li>Click "Save Changes"</li>
                            </ol>

                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mt-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-800">
                                            <strong>Security Note:</strong> Never share your password with anyone,
                                            including other officers or supervisors. Choose a strong password that
                                            includes a mix of letters, numbers, and special characters.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQs Section -->
                    <div id="faqs" class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item"
                        style="animation-delay: 0.8s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Frequently Asked Questions</h2>
                        <div class="prose max-w-none text-gray-700">
                            <div x-data="{ open: false }" class="mb-3">
                                <button @click="open = !open"
                                    class="flex justify-between items-center w-full text-left font-medium text-gray-900 py-2 px-4 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-150">
                                    <span>What happens after I report an issue?</span>
                                    <i class="fas fa-chevron-down text-amber-500 transition-transform duration-200"
                                        :class="{'transform rotate-180': open}"></i>
                                </button>
                                <div x-show="open" class="mt-2 px-4" style="display: none;">
                                    <p>After you report an issue, it enters the system with a "Pending" status. A
                                        supervisor will review the issue, assess its validity and priority, and either
                                        assign it for resolution (changing status to "In Progress") or request more
                                        information. You'll receive notifications when the status of your reported issue
                                        changes.</p>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="mb-3">
                                <button @click="open = !open"
                                    class="flex justify-between items-center w-full text-left font-medium text-gray-900 py-2 px-4 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-150">
                                    <span>Can I delete an issue I've reported?</span>
                                    <i class="fas fa-chevron-down text-amber-500 transition-transform duration-200"
                                        :class="{'transform rotate-180': open}"></i>
                                </button>
                                <div x-show="open" class="mt-2 px-4" style="display: none;">
                                    <p>You can only delete issues that are still in "Pending" status. Once an issue has
                                        been reviewed or assigned, it cannot be deleted from the system. If an issue was
                                        reported in error, you should add a comment explaining the situation.</p>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="mb-3">
                                <button @click="open = !open"
                                    class="flex justify-between items-center w-full text-left font-medium text-gray-900 py-2 px-4 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-150">
                                    <span>What file types are supported for photo uploads?</span>
                                    <i class="fas fa-chevron-down text-amber-500 transition-transform duration-200"
                                        :class="{'transform rotate-180': open}"></i>
                                </button>
                                <div x-show="open" class="mt-2 px-4" style="display: none;">
                                    <p>The system accepts JPEG, PNG, and GIF image formats. Each file must be under 5MB
                                        in size. You can upload up to 5 photos per issue. For best results, use clear,
                                        well-lit photos that clearly show the problem being reported.</p>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="mb-3">
                                <button @click="open = !open"
                                    class="flex justify-between items-center w-full text-left font-medium text-gray-900 py-2 px-4 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-150">
                                    <span>How can I check if someone else has already reported an issue?</span>
                                    <i class="fas fa-chevron-down text-amber-500 transition-transform duration-200"
                                        :class="{'transform rotate-180': open}"></i>
                                </button>
                                <div x-show="open" class="mt-2 px-4" style="display: none;">
                                    <p>You can search for existing issues from the "My Issues" page using the search
                                        bar. Enter keywords related to the issue or the specific location. If you find a
                                        similar issue that has already been reported, add a comment to the existing
                                        issue rather than creating a duplicate report.</p>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="mb-3">
                                <button @click="open = !open"
                                    class="flex justify-between items-center w-full text-left font-medium text-gray-900 py-2 px-4 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-150">
                                    <span>Why was my issue rejected?</span>
                                    <i class="fas fa-chevron-down text-amber-500 transition-transform duration-200"
                                        :class="{'transform rotate-180': open}"></i>
                                </button>
                                <div x-show="open" class="mt-2 px-4" style="display: none;">
                                    <p>Issues may be rejected for several reasons:
                                    <ul class="list-disc ml-5 mt-2">
                                        <li>The issue is outside the jurisdiction of the constituency</li>
                                        <li>Insufficient information provided</li>
                                        <li>Duplicate of an existing issue</li>
                                        <li>The reported problem doesn't exist or couldn't be verified</li>
                                        <li>The issue falls under a different department's responsibility</li>
                                    </ul>
                                    When an issue is rejected, supervisors typically provide an explanation in the
                                    comments section.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Troubleshooting Section -->
                    <div id="troubleshooting" class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item"
                        style="animation-delay: 0.9s;">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Troubleshooting</h2>
                        <div class="prose max-w-none text-gray-700">
                            <p>If you encounter problems with the system, try these solutions:</p>

                            <h3 class="text-lg font-medium text-gray-900 mt-4 mb-2">Common Issues & Solutions</h3>

                            <div class="space-y-4">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-900">Can't Log In</h4>
                                    <ul class="list-disc ml-5 mt-2">
                                        <li>Verify you're using the correct email address and password</li>
                                        <li>Check if Caps Lock is turned on</li>
                                        <li>Clear your browser cache and cookies</li>
                                        <li>Use the "Forgot Password" link to reset your password</li>
                                        <li>Contact support if problems persist</li>
                                    </ul>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-900">Photos Won't Upload</h4>
                                    <ul class="list-disc ml-5 mt-2">
                                        <li>Ensure each photo is less than 5MB in size</li>
                                        <li>Verify you're using a supported file type (JPEG, PNG, GIF)</li>
                                        <li>Try a different browser</li>
                                        <li>Check your internet connection</li>
                                        <li>Resize large photos before uploading</li>
                                    </ul>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-900">Dashboard Not Showing Recent Data</h4>
                                    <ul class="list-disc ml-5 mt-2">
                                        <li>Refresh the page</li>
                                        <li>Clear your browser cache</li>
                                        <li>Log out and log back in</li>
                                        <li>Check that your device's date and time are correct</li>
                                    </ul>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-900">Can't Generate Reports</h4>
                                    <ul class="list-disc ml-5 mt-2">
                                        <li>Try a different report format (PDF, Excel, or HTML)</li>
                                        <li>Verify you have data for the selected time period</li>
                                        <li>Ensure you have a stable internet connection</li>
                                        <li>Try using a different browser</li>
                                    </ul>
                                </div>
                            </div>

                            <h3 class="text-lg font-medium text-gray-900 mt-6 mb-2">System Requirements</h3>
                            <p>For optimal performance, use:</p>
                            <ul class="list-disc ml-5 mt-2">
                                <li>Chrome, Firefox, Safari, or Edge (latest versions)</li>
                                <li>JavaScript enabled</li>
                                <li>Cookies enabled</li>
                                <li>Stable internet connection</li>
                            </ul>

                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-800">
                                            If you've tried these solutions and still experience issues, please contact
                                            support using the <a href="contact-support.php"
                                                class="text-blue-600 hover:underline">Contact Support</a> page.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Back to Top Button -->
                    <div class="flex justify-center my-8">
                        <a href="#"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-300 flex items-center">
                            <i class="fas fa-arrow-up mr-2"></i> Back to Top
                        </a>
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

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                if (targetId === '#') {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                    return;
                }

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const headerOffset = 80; // Adjust based on your header height
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
    </script>
</body>

</html>