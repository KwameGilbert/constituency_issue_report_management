<?php
// dashboard.php - Agent Dashboard Page
// Include the sidebar and header function definitions
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../login/session_check.php';

$database = new Database();
$conn = $database->getConnection();
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

// Get current user data for display
$userName = $_SESSION['user_name'] ?? 'Agent';

// Define action buttons for the header
$headerActionButtons = [
    [
        'icon' => 'fas fa-plus',
        'label' => 'New Issue',
        'href' => '../issues/add_issue.php'
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
</head>

<body class="bg-slate-50 min-h-screen font-sans">

    <?php renderAgentSidebar($current_page); ?>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAgentHeader('Dashboard', 'Welcome back, ' . htmlspecialchars($userName), $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <!-- Total Issues Card -->
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-slate-900/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-alt text-slate-900"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Issues</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $totalIssues; ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Pending Review Card -->
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-warning/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-hourglass-half text-warning"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Pending</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $pendingReview; ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Approved Issues Card -->
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-thumbs-up text-primary"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Approved</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $approved; ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Rejected Issues Card -->
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-error/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-thumbs-down text-error"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Rejected</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $rejected; ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Resolved Issues Card -->
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-success/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Resolved</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $resolved; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Issues by Status Chart -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-gray-800">Issues by Status</h2>
                    </div>
                    <div class="h-72">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Issues by Category/Severity Chart -->
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-gray-800">Issues Breakdown</h2>
                        <div class="flex space-x-2">
                            <button id="categoryBtn" class="px-3 py-1 text-xs font-medium rounded-lg bg-slate-900 text-white">Category</button>
                            <button id="severityBtn" class="px-3 py-1 text-xs font-medium rounded-lg bg-gray-200 text-gray-700">Severity</button>
                        </div>
                    </div>
                    <div class="h-72">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="../issues/add_issue.php" class="flex flex-col items-center p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="w-10 h-10 flex items-center justify-center bg-slate-900/10 rounded-lg mb-3">
                            <i class="fas fa-plus-circle text-slate-900"></i>
                        </div>
                        <span class="text-sm text-slate-900 font-medium">New Issue</span>
                    </a>
                    <a href="../issues/" class="flex flex-col items-center p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="w-10 h-10 flex items-center justify-center bg-slate-900/10 rounded-lg mb-3">
                            <i class="fas fa-search text-slate-900"></i>
                        </div>
                        <span class="text-sm text-slate-900 font-medium">Search Issues</span>
                    </a>
                    <a href="../issues/?filter=pending" class="flex flex-col items-center p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="w-10 h-10 flex items-center justify-center bg-slate-900/10 rounded-lg mb-3">
                            <i class="fas fa-tasks text-slate-900"></i>
                        </div>
                        <span class="text-sm text-slate-900 font-medium">Pending Tasks</span>
                    </a>
                    <a href="../export/" class="flex flex-col items-center p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="w-10 h-10 flex items-center justify-center bg-slate-900/10 rounded-lg mb-3">
                            <i class="fas fa-file-export text-slate-900"></i>
                        </div>
                        <span class="text-sm text-slate-900 font-medium">Export Data</span>
                    </a>
                </div>
            </div>

            <!-- Recent Issues Section -->
            <div class="mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">Recent Activity</h2>
                    <a href="../issues/" class="text-sm text-primary hover:text-primary/80 transition-colors">
                        View all <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Sample data, replace with actual data -->
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Broken Street Light</div>
                                    <div class="text-xs text-gray-500">East Street, North Community</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-warning/10 text-warning">Pending</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Infrastructure</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-06-18</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="#" class="text-primary hover:text-primary/80 font-medium">View</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Water Shortage</div>
                                    <div class="text-xs text-gray-500">West Hills, Central Area</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-primary/10 text-primary">Approved</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Water</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-06-17</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="#" class="text-primary hover:text-primary/80 font-medium">View</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">School Renovation</div>
                                    <div class="text-xs text-gray-500">South District, Main Area</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-success/10 text-success">Resolved</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Education</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-06-15</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="#" class="text-primary hover:text-primary/80 font-medium">View</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
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
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                padding: 15,
                                font: {
                                    size: 11,
                                    family: "'Inter', sans-serif"
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
                        borderRadius: 6
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
                                precision: 0,
                                font: {
                                    family: "'Inter', sans-serif"
                                }
                            },
                            grid: {
                                display: true,
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: "'Inter', sans-serif"
                                }
                            },
                            grid: {
                                display: false
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
                categoryBtn.classList.add('bg-slate-900', 'text-white');
                severityBtn.classList.remove('bg-slate-900', 'text-white');
                severityBtn.classList.add('bg-gray-200', 'text-gray-700');
            });

            severityBtn.addEventListener('click', function() {
                categoryChart.data.labels = severityData.labels;
                categoryChart.data.datasets[0].data = severityData.data;
                categoryChart.data.datasets[0].backgroundColor = severityData.backgroundColor;
                categoryChart.update();

                // Update buttons states
                severityBtn.classList.remove('bg-gray-200', 'text-gray-700');
                severityBtn.classList.add('bg-slate-900', 'text-white');
                categoryBtn.classList.remove('bg-slate-900', 'text-white');
                categoryBtn.classList.add('bg-gray-200', 'text-gray-700');
            });
        });
    </script>

</body>

</html>