<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if(!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Set active page for sidebar
$active_page = 'dashboard';
$pageTitle = 'Dashboard';
$basePath = '../';

// Initialize sidebar state
$sidebarOpen = false;

// Fetch officer information
$officer_id = $_SESSION['officer_id'];
$query = "SELECT * FROM field_officers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$result = $stmt->get_result();
$officer = $result->fetch_assoc();

// Fetch dashboard statistics
$stats_query = "SELECT 
                COUNT(*) as total_issues,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_issues,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_issues
                FROM issues WHERE officer_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $officer_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Ensure we have stats even if the query fails
if (!$stats) {
    $stats = [
        'total_issues' => 0,
        'pending_issues' => 0,
        'in_progress_issues' => 0,
        'resolved_issues' => 0,
        'critical_issues' => 0
    ];
}

// Fetch recent issues
$recent_query = "SELECT i.*, ea.name as electoral_area FROM issues i 
                LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
                WHERE i.officer_id = ? ORDER BY i.created_at DESC LIMIT 5";
$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->bind_param("i", $officer_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_issues = [];
while($issue = $recent_result->fetch_assoc()) {
    $recent_issues[] = $issue;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <style>
    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    .animate-pulse-slow {
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes slideInFromBottom {
        0% {
            transform: translateY(20px);
            opacity: 0;
        }

        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .animate-slide-in {
        animation: slideInFromBottom 0.5s ease-out forwards;
    }

    .staggered-item {
        opacity: 0;
        transform: translateY(20px);
    }

    /* Add glass morphism effect */
    .glassmorphism {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
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

            <!-- Dashboard Content -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6" id="dashboard-content">
                <div class="max-w-7xl mx-auto">
                    <!-- Welcome Message -->
                    <div
                        class="bg-gradient-to-r from-amber-600 to-amber-800 rounded-xl shadow-lg mb-8 p-6 text-white staggered-item">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <h1 class="text-2xl font-bold">Welcome back, <?= htmlspecialchars($officer['name']) ?>!
                                </h1>
                                <p class="mt-1 opacity-90">Here's an overview of your constituency issues</p>
                            </div>
                            <a href="../create-issue/"
                                class="inline-flex items-center px-4 py-2 bg-white text-amber-800 rounded-lg font-medium shadow hover:bg-amber-50 transition-colors duration-300">
                                <i class="fas fa-plus mr-2"></i> Report New Issue
                            </a>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div
                            class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-amber-500 staggered-item hover:shadow-md transition-shadow duration-300">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                                    <i class="fas fa-clipboard-list text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Issues</p>
                                    <p class="text-2xl font-semibold text-gray-900 count-up"
                                        data-target="<?= $stats['total_issues'] ?>">0</p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500 staggered-item hover:shadow-md transition-shadow duration-300">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Pending</p>
                                    <p class="text-2xl font-semibold text-gray-900 count-up"
                                        data-target="<?= $stats['pending_issues'] ?>">0</p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500 staggered-item hover:shadow-md transition-shadow duration-300">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Resolved</p>
                                    <p class="text-2xl font-semibold text-gray-900 count-up"
                                        data-target="<?= $stats['resolved_issues'] ?>">0</p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500 staggered-item hover:shadow-md transition-shadow duration-300">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Critical Issues</p>
                                    <p class="text-2xl font-semibold text-gray-900 count-up"
                                        data-target="<?= $stats['critical_issues'] ?>">0</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Chart -->
                        <div
                            class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300">
                            <h2 class="text-lg font-semibold mb-4 text-gray-800">Issues by Status</h2>
                            <div class="h-64">
                                <canvas id="issuesChart"></canvas>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div
                            class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Recent Issues</h2>
                                <a href="../issues/"
                                    class="text-amber-600 text-sm hover:underline transition-colors duration-300">View
                                    All</a>
                            </div>
                            <div class="space-y-4">
                                <?php if (empty($recent_issues)): ?>
                                <div class="border-b pb-3 text-center py-8">
                                    <div
                                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 text-amber-600 mb-3">
                                        <i class="fas fa-clipboard"></i>
                                    </div>
                                    <p class="text-gray-500">No issues reported yet</p>
                                    <a href="../create-issue/"
                                        class="mt-3 inline-block text-amber-600 hover:underline">Report your first
                                        issue</a>
                                </div>
                                <?php else: ?>
                                <?php foreach ($recent_issues as $index => $issue): ?>
                                <div class="border-b pb-3 hover:bg-amber-50 p-2 rounded-lg transition-colors duration-300"
                                    style="animation-delay: <?= ($index * 0.1) ?>s;">
                                    <div class="flex justify-between">
                                        <a href="../issue-detail/?id=<?= $issue['id'] ?>"
                                            class="font-medium text-amber-700 hover:text-amber-900 hover:underline transition-colors duration-300">
                                            <?= htmlspecialchars($issue['title']) ?>
                                        </a>
                                        <span class="text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($issue['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center">
                                        <?php 
                                            $status_color = 'gray';
                                            $status_bg = 'bg-gray-100';
                                            $status_text = 'text-gray-800';
                                            
                                            if ($issue['status'] == 'pending') {
                                                $status_color = 'yellow';
                                                $status_bg = 'bg-yellow-100';
                                                $status_text = 'text-yellow-800';
                                            } else if ($issue['status'] == 'in_progress') {
                                                $status_color = 'blue';
                                                $status_bg = 'bg-blue-100';
                                                $status_text = 'text-blue-800';
                                            } else if ($issue['status'] == 'resolved') {
                                                $status_color = 'green';
                                                $status_bg = 'bg-green-100';
                                                $status_text = 'text-green-800';
                                            } else if ($issue['status'] == 'rejected') {
                                                $status_color = 'red';
                                                $status_bg = 'bg-red-100';
                                                $status_text = 'text-red-800';
                                            }
                                            ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_bg ?> <?= $status_text ?>">
                                            <?= ucfirst(str_replace('_', ' ', $issue['status'])) ?>
                                        </span>
                                        <span class="ml-2 text-sm text-gray-500">
                                            <?= htmlspecialchars($issue['electoral_area'] ?: 'Unassigned Area') ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div
                        class="bg-white rounded-lg shadow-sm p-6 mt-8 staggered-item hover:shadow-md transition-shadow duration-300">
                        <h2 class="text-lg font-semibold mb-4 text-gray-800">Quick Actions</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a href="../create-issue/"
                                class="flex items-center p-4 border rounded-lg hover:bg-amber-50 transition-colors duration-300 group">
                                <div
                                    class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4 group-hover:bg-amber-200 transition-colors duration-300">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Report New Issue</p>
                                    <p class="text-sm text-gray-600">Create a new constituency issue</p>
                                </div>
                            </a>
                            <a href="../issues/?status=pending"
                                class="flex items-center p-4 border rounded-lg hover:bg-amber-50 transition-colors duration-300 group">
                                <div
                                    class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4 group-hover:bg-yellow-200 transition-colors duration-300">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">View Pending Issues</p>
                                    <p class="text-sm text-gray-600">Check on pending issues</p>
                                </div>
                            </a>
                            <a href="../reports/"
                                class="flex items-center p-4 border rounded-lg hover:bg-amber-50 transition-colors duration-300 group">
                                <div
                                    class="p-3 rounded-full bg-green-100 text-green-600 mr-4 group-hover:bg-green-200 transition-colors duration-300">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Generate Reports</p>
                                    <p class="text-sm text-gray-600">View statistics and analytics</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Count-up animation for statistics
    document.addEventListener('DOMContentLoaded', () => {
        const countElements = document.querySelectorAll('.count-up');

        countElements.forEach(element => {
            const target = parseInt(element.getAttribute('data-target'), 10);
            const duration = 1500; // Animation duration in milliseconds
            const frameDuration = 1000 / 60; // 60fps
            const totalFrames = Math.round(duration / frameDuration);
            let frame = 0;

            const counter = setInterval(() => {
                frame++;
                const progress = frame / totalFrames;
                const currentCount = Math.round(progress * target);

                if (frame === totalFrames) {
                    element.textContent = target;
                    clearInterval(counter);
                } else {
                    element.textContent = currentCount;
                }
            }, frameDuration);
        });

        // Staggered animation for dashboard elements
        setTimeout(() => {
            document.querySelectorAll('.staggered-item').forEach((item, index) => {
                setTimeout(() => {
                    item.style.animation = 'slideInFromBottom 0.5s ease-out forwards';
                }, 100 * index);
            });
        }, 300);
    });

    // Set up chart data
    const ctx = document.getElementById('issuesChart').getContext('2d');
    const issuesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Resolved'],
            datasets: [{
                data: [
                    <?= $stats['pending_issues']; ?>,
                    <?= $stats['in_progress_issues']; ?>,
                    <?= $stats['resolved_issues']; ?>
                ],
                backgroundColor: [
                    '#FCD34D', // Amber-300
                    '#60A5FA', // Blue-400
                    '#10B981', // Green-500
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            family: 'system-ui'
                        },
                        padding: 20
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    boxWidth: 10,
                    usePointStyle: true
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            cutout: '65%',
            radius: '90%'
        }
    });
    </script>
</body>

</html>