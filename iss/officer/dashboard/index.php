<?php
require_once __DIR__ . '/../../config/db_connection.php';
$database = new Database();
$conn = $database->getConnection();
// Include the sidebar and header function definitions
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../login/session_check.php';

$current_page = "dashboard";
$officerId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Officer';

$headerActionButtons = [];
// Colors (random shades for each)
function randomColor($base)
{
    $shades = [
        'slate' => ['#64748b', '#475569', '#334155', '#1e293b'],
        'indigo' => ['#e0e7ff','#c7d2fe','#a5b4fc','#818cf8','#6366f1','#4f46e5','#4338ca','#3730a3','#312e81', '#1e1b4b'],
        'violet' => ['#8b5cf6', '#7c3aed', '#6d28d9'],
        'teal' => ['#14b8a6', '#0d9488', '#0f766e'],
        // 'green' => ['#10b981', '#059669', '#047857'], // Commented out as per original
        // 'yellow' => ['#f59e0b', '#d97706', '#b45309'], // Commented out as per original
        // 'red' => ['#ef4444', '#dc2626', '#b91c1c'],     // Commented out as per original
        // 'pink' => ['#ec4899', '#db2777', '#be185d'],   // Commented out as per original
    ];
    $pool = $shades[$base] ?? $shades['indigo'];
    return $pool[array_rand($pool)];
}

function getStatusBadgeClasses(string $status): string
{
    return match (strtolower($status)) {
        'pending', 'pending_review' => 'bg-yellow-100 text-yellow-800', // warning
        'approved'                  => 'bg-indigo-100 text-indigo-800', // primary
        'rejected'                  => 'bg-red-100 text-red-800',      // red
        'resolved'                  => 'bg-green-100 text-green-800',  // success
        'in_progress'               => 'bg-blue-100 text-blue-800',    // blue
        'reviewed'                  => 'bg-blue-100 text-blue-800',    // Added 'reviewed' as per previous context
        default                     => 'bg-gray-100 text-gray-800',
    };
}
function getStatusBadgeClassesForTable(string $status): string {
    return getStatusBadgeClasses($status); // Reuse the same logic for consistency
}

// --- Dashboard Totals ---
$countStmt = $conn->prepare("SELECT
    COUNT(*) AS total,
    SUM(status = 'pending') AS pending,
    SUM(status = 'approved') AS approved,
    SUM(status = 'in_progress') AS in_progress,
    SUM(status = 'resolved') AS resolved,
    SUM(status = 'rejected') AS rejected
    FROM issues");

$countStmt->execute();
$countRow = $countStmt->fetch(PDO::FETCH_ASSOC);

$totalIssues = (int)$countRow['total'];
$pendingReview = (int)$countRow['pending'];
$approved = (int)$countRow['approved'];
$inProgress = (int) $countRow['in_progress'];
$rejected = (int)$countRow['rejected'];
$resolved = (int)$countRow['resolved'];


// --- Issues by Status ---
$statusStmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM issues GROUP BY status");
$statusStmt->execute();
$statusLabels = [];
$statusData = [];
$statusColors = [];
while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
    $statusLabels[] = ucwords(str_replace('_', ' ', $row['status']));
    $statusData[] = (int)$row['count'];
    $statusColors[] = randomColor('indigo');
}
$issuesByStatusData = [
    'labels' => $statusLabels,
    'data' => $statusData,
    'backgroundColor' => $statusColors,
];


// --- Issues by Category ---
// Updated to join with issue_categories to get the category name
$categoryStmt = $conn->prepare("SELECT ic.name AS category, COUNT(*) AS count
                                FROM issues i
                                JOIN issue_categories ic ON i.category_id = ic.id
                                GROUP BY ic.name");
$categoryStmt->execute();
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
$severityStmt = $conn->prepare("SELECT severity, COUNT(*) AS count FROM issues GROUP BY severity");
$severityStmt->execute();
$severityLabels = [];
$severityData = [];
$severityColors = [];
while ($row = $severityStmt->fetch(PDO::FETCH_ASSOC)) {
    $severityLabels[] = ucfirst($row['severity']);
    $severityData[] = (int)$row['count'];
    $severityColors[] = randomColor('violet');
}
$issuesBySeverityData = [
    'labels' => $severityLabels,
    'data' => $severityData,
    'backgroundColor' => $severityColors,
];

// --- Function to fetch recent issues for the dashboard table ---
/**
 * Fetches recent issues specifically for the dashboard table.
 * Includes agent's name and formats the issue ID.
 *
 * @param PDO $conn The PDO database connection object.
 * @param int $limit The maximum number of issues to fetch.
 * @return array An array of associative arrays, each representing an issue.
 */
function getRecentIssuesForDashboardTable(PDO $conn, int $limit = 5): array {
    $stmt = $conn->prepare("
        SELECT
            i.id,
            i.title,
            u.name AS agent_name, -- Agent's name from the users table
            i.status,
            i.created_at AS submitted_at -- Alias created_at to submitted_at
        FROM
            issues i
        LEFT JOIN
            users u ON i.agent_id = u.id -- Join with users table to get agent's name
        ORDER BY
            i.created_at DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getIssuesCountByStatus(PDO $conn, string $status): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM issues WHERE status = :status");
    $stmt->execute([':status' => $status]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Data for Recent Issues Submitted table
$recentIssuesDashboardTable = getRecentIssuesForDashboardTable($conn, 5); // Fetch 4 recent issues
// Assuming 'pending' is the status for pending issues in the table's count message
$totalPendingIssues = getIssuesCountByStatus($conn, 'pending');


// Get current user data for display
$userName = $_SESSION['user_name'] ?? 'Officer';

// Define action buttons for the header
$headerActionButtons = [
    [
        'icon' => 'fas fa-list',
        'label' => 'Issues',
        'href' => './../issues/'
    ],
    [
        'icon' => 'fas fa-plus',
        'label' => 'New Issue',
        'href' => './../issues/add_issue.php'
    ]
];

?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - Constituency System</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
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
        <?php renderOfficerSidebar($current_page); ?>
        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen transition-all duration-300">
            <?php renderOfficerHeader('Dashboard', 'Welcome back, ' . htmlspecialchars($userName), $headerActionButtons); ?>
            <div class="p-4 sm:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">


                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-900/10 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-file-alt text-blue-900"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total Issues</p>
                                <h3 class="text-xl font-bold text-gray-800"><?php echo $totalIssues; ?></h3>
                            </div>
                        </div>
                    </div>

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

                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-error/10 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-times-circle text-error"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Rejected</p>
                                <h3 class="text-xl font-bold text-gray-800"><?php echo $rejected; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-6 min-h-[300px] flex items-center justify-center">
                        <canvas id="issuesByStatusChart"></canvas>
                    </div>
                    <!-- Issues by Category/Severity Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-base font-semibold text-gray-800">Issues Breakdown</h2>
                            <div class="flex space-x-2">
                                <button id="categoryBtn" class="px-3 py-1 text-xs font-medium rounded-lg bg-indigo-900 text-white">Category</button>
                                <button id="severityBtn" class="px-3 py-1 text-xs font-medium rounded-lg bg-gray-200 text-gray-700">Severity</button>
                            </div>
                        </div>
                        <div class="h-72">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

            <!-- Recent Issues Submitted Table -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Recent Issues Submitted</h2>
                            <a href="./../issues/index.php" class="text-sm text-blue-700 hover:text-blue-800 font-medium">View All Issues <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Issue ID
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Title
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Agent
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date Submitted
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($recentIssuesDashboardTable)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                No recent issues found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentIssuesDashboardTable as $issue): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    #ISU<?php echo str_pad($issue['id'], 5, '0', STR_PAD_LEFT); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($issue['title']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($issue['agent_name'] ?? 'N/A'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClassesForTable($issue['status']); ?>">
                                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $issue['status']))); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?php echo date('Y-m-d', strtotime($issue['submitted_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="../issues/view_issue.php?id=<?php echo htmlspecialchars($issue['id']); ?>" class="text-blue-600 hover:text-blue-900">Review</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- <div class="mt-4 text-center text-sm text-gray-500">
                            Showing <?php //echo count($recentIssuesDashboardTable); ?> of <?php // echo $totalPendingIssues; ?> pending issues.
                        </div> -->
                    </div>
        </main>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // Issues by Category Chart
                const issueStatusCtx = document.getElementById('issuesByStatusChart').getContext('2d');
                const issueStatusData = <?php echo json_encode($issuesByStatusData); ?>;

                const statusChart = new Chart(issueStatusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: issueStatusData.labels,
                        datasets: [{
                            data: issueStatusData.data,
                            backgroundColor: issueStatusData.backgroundColor,
                            borderWidth: 1
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
                                },
                                title: {
                                    display: true,
                                    text: 'Issues by Status'
                                }
                            }
                        },
                    }
                });


                // Category/Severity Chart
                // Charts initialization
                const categoryCtx = document.getElementById('categoryChart').getContext('2d');

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
                    categoryBtn.classList.add('bg-indigo-900', 'text-white');
                    severityBtn.classList.remove('bg-indigo-900', 'text-white');
                    severityBtn.classList.add('bg-gray-200', 'text-gray-700');
                });

                severityBtn.addEventListener('click', function() {
                    categoryChart.data.labels = severityData.labels;
                    categoryChart.data.datasets[0].data = severityData.data;
                    categoryChart.data.datasets[0].backgroundColor = severityData.backgroundColor;
                    categoryChart.update();

                    // Update buttons states
                    severityBtn.classList.remove('bg-gray-200', 'text-black-700');
                    severityBtn.classList.add('bg-indigo-900', 'text-white');
                    categoryBtn.classList.remove('bg-indigo-900', 'text-white');
                    categoryBtn.classList.add('bg-gray-200', 'text-black-700');
                });

            });
        </script>

</body>
</html>