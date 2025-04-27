<?php
// dashboard.php - Main dashboard page for field officers
session_start();

// Check if user is logged in and is a field officer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ./../login");
    exit();
}

// Fetch officer information
require_once 'includes/db_connect.php';
$officer_id = $_SESSION['user_id'];
$query = "SELECT * FROM officers WHERE id = ?";
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

// Fetch recent issues
$recent_query = "SELECT * FROM issues WHERE officer_id = ? ORDER BY created_at DESC LIMIT 5";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-blue-800 text-white w-64 flex-shrink-0">
            <div class="p-4">
                <h2 class="text-2xl font-bold">Field Officer Portal</h2>
                <p class="text-sm opacity-70">Constituency Issue Management</p>
            </div>
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center py-3 px-4 bg-blue-900">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="issues.php" class="flex items-center py-3 px-4 hover:bg-blue-700">
                    <i class="fas fa-exclamation-circle w-6"></i>
                    <span>Issue Management</span>
                </a>
                <a href="profile.php" class="flex items-center py-3 px-4 hover:bg-blue-700">
                    <i class="fas fa-user w-6"></i>
                    <span>Profile</span>
                </a>
                <a href="reports.php" class="flex items-center py-3 px-4 hover:bg-blue-700">
                    <i class="fas fa-chart-bar w-6"></i>
                    <span>Reports & Analytics</span>
                </a>
                <a href="logout.php" class="flex items-center py-3 px-4 hover:bg-blue-700 mt-12">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
                    <div class="flex items-center">
                        <span class="mr-4"><?php echo htmlspecialchars($officer['name']); ?></span>
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white">
                            <?php echo strtoupper(substr($officer['name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4">
                <div class="max-w-7xl mx-auto">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                                    <i class="fas fa-clipboard-list text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Issues</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?php echo $stats['total_issues']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Pending</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?php echo $stats['pending_issues']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Resolved</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?php echo $stats['resolved_issues']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Critical Issues</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?php echo $stats['critical_issues']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Chart -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Issues by Status</h2>
                            <div class="h-64">
                                <canvas id="issuesChart"></canvas>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-semibold">Recent Issues</h2>
                                <a href="issues.php" class="text-blue-600 text-sm hover:underline">View All</a>
                            </div>
                            <div class="space-y-4">
                                <?php foreach ($recent_issues as $issue): ?>
                                <div class="border-b pb-3">
                                    <div class="flex justify-between">
                                        <a href="issue-detail.php?id=<?php echo $issue['id']; ?>"
                                            class="font-medium text-blue-600 hover:underline">
                                            <?php echo htmlspecialchars($issue['title']); ?>
                                        </a>
                                        <span class="text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center">
                                        <?php 
                                        $status_color = 'gray';
                                        if ($issue['status'] == 'pending') $status_color = 'yellow';
                                        if ($issue['status'] == 'in_progress') $status_color = 'blue';
                                        if ($issue['status'] == 'resolved') $status_color = 'green';
                                        ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                            <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                        </span>
                                        <span class="ml-2 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($issue['electoral_area']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                                <?php if (empty($recent_issues)): ?>
                                <p class="text-gray-500 text-center py-4">No recent issues found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mt-8">
                        <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a href="create-issue.php"
                                class="flex items-center p-4 border rounded-lg hover:bg-blue-50 transition">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Report New Issue</p>
                                    <p class="text-sm text-gray-600">Create a new constituency issue</p>
                                </div>
                            </a>
                            <a href="issues.php?filter=pending"
                                class="flex items-center p-4 border rounded-lg hover:bg-blue-50 transition">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div>
                                    <p class="font-medium">View Pending Issues</p>
                                    <p class="text-sm text-gray-600">Check on pending issues</p>
                                </div>
                            </a>
                            <a href="reports.php"
                                class="flex items-center p-4 border rounded-lg hover:bg-blue-50 transition">
                                <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Generate Reports</p>
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
    // Set up chart data
    const ctx = document.getElementById('issuesChart').getContext('2d');
    const issuesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Resolved'],
            datasets: [{
                data: [
                    <?php echo $stats['pending_issues']; ?>,
                    <?php echo $stats['in_progress_issues']; ?>,
                    <?php echo $stats['resolved_issues']; ?>
                ],
                backgroundColor: [
                    '#fbbf24',
                    '#60a5fa',
                    '#34d399'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Add animation to stats numbers
    const animateValue = (element, start, end, duration) => {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            element.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    };

    document.addEventListener('DOMContentLoaded', () => {
        const statElements = document.querySelectorAll('.text-2xl.font-semibold');
        statElements.forEach(element => {
            const finalValue = parseInt(element.textContent);
            element.textContent = '0';
            animateValue(element, 0, finalValue, 1000);
        });
    });
    </script>
</body>

</html>