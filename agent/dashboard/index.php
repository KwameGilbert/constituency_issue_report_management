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

$agentId = $_SESSION['user_id'] ?? null;
if (!$agentId) {
    die("Unauthorized");
}

// Colors (random shades for each)
function randomColor($base)
{
    $shades = [
        'slate' => ['#64748b', '#475569', '#334155', '#1e293b'],
        'indigo' => ['#6366f1', '#4f46e5', '#4338ca'],
        'green' => ['#10b981', '#059669', '#047857'],
        'yellow' => ['#f59e0b', '#d97706', '#b45309'],
        'red' => ['#ef4444', '#dc2626', '#b91c1c'],
        'pink' => ['#ec4899', '#db2777', '#be185d'],
        'violet' => ['#8b5cf6', '#7c3aed', '#6d28d9'],
        'teal' => ['#14b8a6', '#0d9488', '#0f766e'],
    ];
    $pool = $shades[$base] ?? $shades['slate'];
    return $pool[array_rand($pool)];
}

// --- Issues by Status ---
$statusStmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM issues WHERE agent_id = ? GROUP BY status");
$statusStmt->execute([$agentId]);
$statusLabels = [];
$statusData = [];
$statusColors = [];
while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
    $statusLabels[] = ucwords(str_replace('_', ' ', $row['status']));
    $statusData[] = (int)$row['count'];
    $statusColors[] = randomColor('slate');
}
$issuesByStatusData = [
    'labels' => $statusLabels,
    'data' => $statusData,
    'backgroundColor' => $statusColors,
];

// --- Issues by Category ---
$categoryStmt = $conn->prepare("SELECT ic.name AS category, COUNT(*) AS count FROM issues i JOIN issue_categories ic ON i.category_id = ic.id WHERE i.agent_id = ? GROUP BY ic.name");
$categoryStmt->execute([$agentId]);
$categoryLabels = [];
$categoryData = [];
$categoryColors = [];
while ($row = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
    $categoryLabels[] = $row['category'];
    $categoryData[] = (int)$row['count'];
    $categoryColors[] = randomColor('teal');
}
$issuesByCategoryData = [
    'labels' => $categoryLabels,
    'data' => $categoryData,
    'backgroundColor' => $categoryColors,
];

// --- Issues by Severity ---
$severityStmt = $conn->prepare("SELECT severity, COUNT(*) AS count FROM issues WHERE agent_id = ? GROUP BY severity");
$severityStmt->execute([$agentId]);
$severityLabels = [];
$severityData = [];
$severityColors = [];
while ($row = $severityStmt->fetch(PDO::FETCH_ASSOC)) {
    $severityLabels[] = ucfirst($row['severity']);
    $severityData[] = (int)$row['count'];
    $severityColors[] = randomColor('red');
}
$issuesBySeverityData = [
    'labels' => $severityLabels,
    'data' => $severityData,
    'backgroundColor' => $severityColors,
];

// --- Dashboard Totals ---
$countStmt = $conn->prepare("SELECT 
    COUNT(*) AS total, 
    SUM(status = 'pending') AS pending,
    SUM(status = 'approved') AS approved, 
    SUM(status = 'in_progress') AS in_progress, 
    SUM(status = 'resolved') AS resolved, 
    SUM(status = 'rejected') AS rejected 
    FROM issues WHERE agent_id = ?");
$countStmt->execute([$agentId]);
$countRow = $countStmt->fetch(PDO::FETCH_ASSOC);

$totalIssues = (int)$countRow['total'];
$pendingReview = (int)$countRow['pending'];
$approved = (int)$countRow['approved'];
$inProgress = (int) $countRow['in_progress'];
$rejected = (int)$countRow['rejected'];
$resolved = (int)$countRow['resolved'];

/**
 * Fetches the most recent issues for the dashboard table.
 *
 * @param PDO $conn The PDO database connection object.
 * @param int $limit The maximum number of issues to fetch.
 * @return array An array of associative arrays, each representing an issue.
 */
function getRecentIssues(PDO $conn, int $limit = 5): array
{
    $stmt = $conn->prepare("
        SELECT
            i.id,
            i.title,
            i.description,
            i.location,
            mc.name AS main_community_name,
            sc.name AS smaller_community_name,
            cot.name AS cottage_name,
            com.name AS community_name,
            sub.name AS suburb_name,
            i.status,
            ic.name AS category_name,
            i.created_at
        FROM
            issues i
        LEFT JOIN
            electoral_areas ea ON i.electoral_area_id = ea.id
        LEFT JOIN
            communities com ON i.community_id = com.id
        LEFT JOIN
            suburbs sub ON i.suburb_id = sub.id
        LEFT JOIN
            issue_categories ic ON i.category_id = ic.id
        ORDER BY
            i.created_at DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Returns Tailwind CSS classes for an issue status badge.
 *
 * @param string $status The issue status (e.g., 'pending', 'approved', 'rejected', 'resolved').
 * @return string The CSS classes for the badge.
 */
function getStatusBadgeClasses(string $status): string
{
    return match (strtolower($status)) {
        'pending'     => 'bg-yellow-100 text-yellow-800', // warning
        'approved'    => 'bg-indigo-100 text-indigo-800', // primary
        'rejected'    => 'bg-red-100 text-red-800',       // red
        'resolved'    => 'bg-green-100 text-green-800',   // success
        'in_progress' => 'bg-blue-100 text-blue-800',     // blue
        default       => 'bg-gray-100 text-gray-800',
    };
}

// Fetch recent issues
$recentIssues = getRecentIssues($conn, 5);

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

                <!-- In Progress Issues Card -->
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-secondary/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-spinner text-secondary"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">In Progress</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $inProgress; ?></h3>
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

            <!-- Recent Issues Section -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">Recent Activity</h2>
                    <a href="../issues/" class="text-sm text-primary hover:text-primary/80 transition-colors">
                        View all <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Issue
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Category
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">View</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($recentIssues)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No recent issues found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentIssues as $issue): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($issue['title']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?php
                                                // Construct location string
                                                $locationParts = [];
                                                if (!empty($issue['location'])) $locationParts[] = htmlspecialchars($issue['location']);
                                                if (!empty($issue['suburb_name'])) $locationParts[] = htmlspecialchars($issue['suburb_name']);
                                                if (!empty($issue['community_name'])) $locationParts[] = htmlspecialchars($issue['community_name']);
                                                if (!empty($issue['electoral_area_name'])) $locationParts[] = htmlspecialchars($issue['electoral_area_name']);
                                                echo implode(', ', $locationParts);
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClasses($issue['status']); ?>">
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $issue['status']))); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($issue['category_name'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('Y-m-d', strtotime($issue['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="../issues/view_issue.php?id=<?php echo $issue['id'] ?? ''; ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mt-6">
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