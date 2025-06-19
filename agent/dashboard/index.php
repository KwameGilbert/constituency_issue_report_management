<?php
// dashboard.php - Agent Dashboard Page

// Include the sidebar function definition
include __DIR__ . '/../components/sidebar.php';

// Determine the current page for sidebar highlighting
$current_page = 'dashboard';

// --- Dashboard Stats Data ---
$totalIssues = 45;
$pendingReview = 12;
$approved = 18;
$rejected = 5;
$resolved = 22;

// Data for 'Issues by Status' Chart
$issuesByStatusData = [
    'labels' => ['Pending', 'Approved', 'Rejected', 'Resolved'],
    'data' => [$pendingReview, $approved, $rejected, $resolved],
    'backgroundColor' => [
        '#f59e0b', // warning (yellow)
        '#6366f1', // primary (indigo)
        '#ef4444', // red
        '#10b981', // success (green)
    ]
];

// Data for 'Issues by Category' Chart
$issuesByCategoryData = [
    'labels' => ['Infrastructure', 'Education', 'Health', 'Security', 'Water'],
    'data' => [15, 12, 8, 6, 4],
    'backgroundColor' => [
        '#6366f1', // primary 
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#14b8a6', // teal
        '#f59e0b', // yellow
    ]
];

// Data for 'Issues by Severity' Chart
$issuesBySeverityData = [
    'labels' => ['High', 'Medium', 'Low'],
    'data' => [10, 23, 12],
    'backgroundColor' => [
        '#ef4444', // red
        '#f59e0b', // yellow
        '#10b981', // green
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - Constituency System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                        dark: '#1f2937',
                        light: '#f8fafc'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen font-sans bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

    <?php renderAgentSidebar($current_page); ?>

    <!-- Main Content -->
    <main class="lg:ml-60 px-3 pb-3 transition-all duration-300">
        <div class="p-3 flex justify-between items-center border-b border-gray-100">
            <div>
                <h3 class="text-gray-800 font-medium text-sm">Agent Dashboard</h3>
                <p class="text-gray-600 text-xs">Welcome back, Agent</p>
            </div>
            <a href="../issue/add_issue.php" class="px-4 py-1.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Add New Issue
            </a>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6 pt-3">
            <!-- Total Issues Card -->
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Total Issues</p>
                        <h3 class="text-xl font-bold text-gray-800 mt-1"><?php echo $totalIssues; ?></h3>
                    </div>
                    <div class="w-9 h-9 bg-primary/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-alt text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Review Card -->
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Pending Review</p>
                        <h3 class="text-xl font-bold text-gray-800 mt-1"><?php echo $pendingReview; ?></h3>
                    </div>
                    <div class="w-9 h-9 bg-warning/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-hourglass-half text-warning"></i>
                    </div>
                </div>
            </div>

            <!-- Approved Issues Card -->
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Approved</p>
                        <h3 class="text-xl font-bold text-gray-800 mt-1"><?php echo $approved; ?></h3>
                    </div>
                    <div class="w-9 h-9 bg-primary/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-thumbs-up text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Rejected Issues Card -->
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Rejected</p>
                        <h3 class="text-xl font-bold text-gray-800 mt-1"><?php echo $rejected; ?></h3>
                    </div>
                    <div class="w-9 h-9 bg-error/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-thumbs-down text-error"></i>
                    </div>
                </div>
            </div>

            <!-- Resolved Issues Card -->
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Resolved</p>
                        <h3 class="text-xl font-bold text-gray-800 mt-1"><?php echo $resolved; ?></h3>
                    </div>
                    <div class="w-9 h-9 bg-success/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Issues by Status Chart -->
            <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">Issues by Status</h2>
                </div>
                <div class="h-64">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Issues by Category/Severity Chart -->
            <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">Issues by Category</h2>
                    <div class="flex space-x-2">
                        <button id="categoryBtn" class="px-3 py-1 text-xs font-medium rounded bg-primary text-white">Category</button>
                        <button id="severityBtn" class="px-3 py-1 text-xs font-medium rounded bg-gray-200 text-gray-700">Severity</button>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="#" class="flex flex-col items-center p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-plus-circle text-primary text-lg mb-2"></i>
                    <span class="text-xs text-gray-700">New Issue</span>
                </a>
                <a href="#" class="flex flex-col items-center p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-search text-primary text-lg mb-2"></i>
                    <span class="text-xs text-gray-700">Search Issues</span>
                </a>
                <a href="#" class="flex flex-col items-center p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-list-check text-primary text-lg mb-2"></i>
                    <span class="text-xs text-gray-700">Pending Tasks</span>
                </a>
                <a href="#" class="flex flex-col items-center p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-file-export text-primary text-lg mb-2"></i>
                    <span class="text-xs text-gray-700">Export Report</span>
                </a>
            </div>
        </div>
    </main>

    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {

            // Charts initialization
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');

            // Issues by Status Chart
            const statusData = <?php echo json_encode($issuesByStatusData); ?>;
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusData.labels,
                    datasets: [{
                        data: statusData.data,
                        backgroundColor: statusData.backgroundColor,
                        borderWidth: 1,
                        borderColor: '#fff',
                        hoverOffset: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                padding: 15,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Category/Severity Chart
            const categoryData = <?php echo json_encode($issuesByCategoryData); ?>;
            const severityData = <?php echo json_encode($issuesBySeverityData); ?>;

            const categoryChart = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: categoryData.labels,
                    datasets: [{
                        label: 'Issues',
                        data: categoryData.data,
                        backgroundColor: categoryData.backgroundColor,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Toggle between Category and Severity charts
            const categoryBtn = document.getElementById('categoryBtn');
            const severityBtn = document.getElementById('severityBtn');

            categoryBtn.addEventListener('click', function() {
                categoryChart.data.labels = categoryData.labels;
                categoryChart.data.datasets[0].data = categoryData.data;
                categoryChart.data.datasets[0].backgroundColor = categoryData.backgroundColor;
                categoryChart.update();

                // Update buttons states
                categoryBtn.classList.remove('bg-gray-200', 'text-gray-700');
                categoryBtn.classList.add('bg-primary', 'text-white');
                severityBtn.classList.remove('bg-primary', 'text-white');
                severityBtn.classList.add('bg-gray-200', 'text-gray-700');
            });

            severityBtn.addEventListener('click', function() {
                categoryChart.data.labels = severityData.labels;
                categoryChart.data.datasets[0].data = severityData.data;
                categoryChart.data.datasets[0].backgroundColor = severityData.backgroundColor;
                categoryChart.update();

                // Update buttons states
                severityBtn.classList.remove('bg-gray-200', 'text-gray-700');
                severityBtn.classList.add('bg-primary', 'text-white');
                categoryBtn.classList.remove('bg-primary', 'text-white');
                categoryBtn.classList.add('bg-gray-200', 'text-gray-700');
            });
        });
    </script>

</body>

</html>