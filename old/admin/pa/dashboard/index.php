<?php
session_start();

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Fetch PA information
$pa_id = $_SESSION['pa_id'];
$pa_query = "SELECT name, office_location, department FROM personal_assistants WHERE id = ?";
$pa_stmt = $conn->prepare($pa_query);
$pa_stmt->bind_param("i", $pa_id);
$pa_stmt->execute();
$pa_result = $pa_stmt->get_result();
$pa_info = $pa_result->fetch_assoc();
$pa_stmt->close();

// Get issues statistics
$issues_stats_query = "SELECT 
    COUNT(*) as total_issues,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review_issues,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_issues,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_issues,
    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_issues,
    SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_severity_issues,
    SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium_severity_issues,
    SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_severity_issues,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as issues_last_30_days,
    SUM(CASE WHEN (created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) THEN 1 ELSE 0 END) as issues_last_7_days
FROM issues";
$issues_stats_result = $conn->query($issues_stats_query);
$issues_stats = $issues_stats_result->fetch_assoc();

// Get electoral area statistics
$electoral_areas_query = "SELECT ea.name, COUNT(i.id) as issue_count
FROM electoral_areas ea
LEFT JOIN issues i ON ea.id = i.electoral_area_id
GROUP BY ea.id
ORDER BY issue_count DESC
LIMIT 5";
$electoral_areas_result = $conn->query($electoral_areas_query);
$electoral_areas_stats = [];
while ($row = $electoral_areas_result->fetch_assoc()) {
    $electoral_areas_stats[] = $row;
}

// Get recent activity
$recent_activity_query = "SELECT 
    i.id, i.title, i.status, i.severity, i.created_at, 
    ea.name as electoral_area,
    fo.name as officer_name
FROM issues i
LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
LEFT JOIN field_officers fo ON i.officer_id = fo.id
ORDER BY i.created_at DESC
LIMIT 10";
$recent_activity_result = $conn->query($recent_activity_query);

// Get monthly issue statistics for chart
$monthly_stats_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as issue_count,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
FROM issues
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month ASC";
$monthly_stats_result = $conn->query($monthly_stats_query);
$monthly_stats = [];
while ($row = $monthly_stats_result->fetch_assoc()) {
    $monthly_stats[] = $row;
}

// Convert monthly stats to JSON for charts
$monthly_labels = [];
$monthly_issues = [];
$monthly_resolved = [];
foreach ($monthly_stats as $stat) {
    $date = date_create_from_format('Y-m', $stat['month']);
    $formatted_date = date_format($date, 'M Y');
    $monthly_labels[] = $formatted_date;
    $monthly_issues[] = $stat['issue_count'];
    $monthly_resolved[] = $stat['resolved_count'];
}

// Calculate resolution rate
$resolution_rate = ($issues_stats['total_issues'] > 0) 
    ? round(($issues_stats['resolved_issues'] / $issues_stats['total_issues']) * 100, 1) 
    : 0;

$page_title = "Dashboard - PA Portal";
include_once '../includes/header.php';
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <!-- Welcome Banner -->
        <div class="p-4 mb-6 bg-white rounded-lg shadow-md border-l-4 border-green-600">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Welcome, <?= htmlspecialchars($pa_info['name']) ?>
                    </h2>
                    <p class="text-gray-600 mt-1">
                        <?= htmlspecialchars($pa_info['department']) ?? 'Personal Assistant' ?> |
                        <?= htmlspecialchars($pa_info['office_location']) ?? 'Head Office' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Issues Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-b-4 border-blue-500">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-gray-500 text-sm font-medium">Total Issues</h3>
                        <span class="p-2 bg-blue-100 rounded-full">
                            <i class="fas fa-list-alt text-blue-500"></i>
                        </span>
                    </div>
                    <div class="flex items-baseline mt-2">
                        <p class="text-3xl font-semibold text-gray-800">
                            <?= number_format($issues_stats['total_issues']) ?></p>
                        <p class="text-sm text-gray-500 ml-2">across all areas</p>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-green-500 flex items-center">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            <?= number_format($issues_stats['issues_last_30_days']) ?> in last 30 days
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pending Issues Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-b-4 border-yellow-500">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-gray-500 text-sm font-medium">Pending Issues</h3>
                        <span class="p-2 bg-yellow-100 rounded-full">
                            <i class="fas fa-hourglass-half text-yellow-500"></i>
                        </span>
                    </div>
                    <div class="flex items-baseline mt-2">
                        <p class="text-3xl font-semibold text-gray-800">
                            <?= number_format($issues_stats['pending_issues']) ?></p>
                        <p class="text-sm text-gray-500 ml-2">awaiting review</p>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <a href="../issues/?status=pending" class="text-yellow-500 flex items-center hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Manage pending issues
                        </a>
                    </div>
                </div>
            </div>

            <!-- In Progress Issues Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-b-4 border-purple-500">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-gray-500 text-sm font-medium">In Progress</h3>
                        <span class="p-2 bg-purple-100 rounded-full">
                            <i class="fas fa-tasks text-purple-500"></i>
                        </span>
                    </div>
                    <div class="flex items-baseline mt-2">
                        <p class="text-3xl font-semibold text-gray-800">
                            <?= number_format($issues_stats['in_progress_issues']) ?></p>
                        <p class="text-sm text-gray-500 ml-2">issues being handled</p>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <a href="../issues/?status=in_progress"
                            class="text-purple-500 flex items-center hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> View in-progress issues
                        </a>
                    </div>
                </div>
            </div>

            <!-- Resolution Rate Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-b-4 border-green-500">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-gray-500 text-sm font-medium">Resolution Rate</h3>
                        <span class="p-2 bg-green-100 rounded-full">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </span>
                    </div>
                    <div class="flex items-baseline mt-2">
                        <p class="text-3xl font-semibold text-gray-800"><?= $resolution_rate ?>%</p>
                        <p class="text-sm text-gray-500 ml-2">issues resolved</p>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-green-500 flex items-center">
                            <i class="fas fa-check mr-1"></i> <?= number_format($issues_stats['resolved_issues']) ?>
                            resolved issues
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Activity Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Monthly Trends Chart -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Monthly Issue Trends</h3>
                <div class="h-80">
                    <canvas id="monthlyTrendsChart"></canvas>
                </div>
            </div>

            <!-- Status Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Issues by Status</h3>
                <div class="h-80">
                    <canvas id="statusDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Additional Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Severity Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Issues by Severity</h3>
                <div class="h-64">
                    <canvas id="severityDistributionChart"></canvas>
                </div>
            </div>

            <!-- Electoral Area Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Top Electoral Areas by Issues</h3>
                <div class="h-64">
                    <canvas id="electoralAreasChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Recent Activity</h3>
                <a href="../issues/" class="text-sm text-green-600 hover:underline flex items-center">
                    View All Issues <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Issue
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Electoral Area
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Severity
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reported By
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($recent_activity_result->num_rows > 0): ?>
                        <?php while ($issue = $recent_activity_result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <a href="../issues/view.php?id=<?= $issue['id'] ?>"
                                            class="text-sm font-medium text-gray-900 hover:text-green-600">
                                            <?= htmlspecialchars($issue['title']) ?>
                                        </a>
                                        <div class="text-xs text-gray-500">ID: <?= $issue['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($issue['electoral_area'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                        $status_class = match($issue['status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'under_review' => 'bg-blue-100 text-blue-800',
                                            'in_progress' => 'bg-purple-100 text-purple-800',
                                            'resolved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        $status_text = match($issue['status']) {
                                            'pending' => 'Pending',
                                            'under_review' => 'Under Review',
                                            'in_progress' => 'In Progress',
                                            'resolved' => 'Resolved',
                                            'rejected' => 'Rejected',
                                            default => 'Unknown'
                                        };
                                        ?>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                    <?= $status_text ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                        $severity_class = match($issue['severity']) {
                                            'critical' => 'bg-red-100 text-red-800',
                                            'high' => 'bg-orange-100 text-orange-800',
                                            'medium' => 'bg-yellow-100 text-yellow-800',
                                            'low' => 'bg-green-100 text-green-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        $severity_text = ucfirst($issue['severity']);
                                        ?>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $severity_class ?>">
                                    <?= $severity_text ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($issue['officer_name'] ?? 'Unknown') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($issue['created_at'])) ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No recent activity found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="../issues/?status=pending"
                    class="flex items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200 hover:bg-yellow-100 transition-colors">
                    <span
                        class="inline-flex justify-center items-center w-10 h-10 rounded-full bg-yellow-100 text-yellow-500 mr-3">
                        <i class="fas fa-clipboard-list"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-medium">Pending Issues</span>
                        <span class="block text-xs text-gray-500">Review and assign</span>
                    </span>
                </a>

                <a href="../projects/create.php"
                    class="flex items-center p-3 bg-blue-50 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors">
                    <span
                        class="inline-flex justify-center items-center w-10 h-10 rounded-full bg-blue-100 text-blue-500 mr-3">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-medium">New Project</span>
                        <span class="block text-xs text-gray-500">Start a new initiative</span>
                    </span>
                </a>

                <a href="../reports/"
                    class="flex items-center p-3 bg-green-50 rounded-lg border border-green-200 hover:bg-green-100 transition-colors">
                    <span
                        class="inline-flex justify-center items-center w-10 h-10 rounded-full bg-green-100 text-green-500 mr-3">
                        <i class="fas fa-chart-bar"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-medium">Generate Reports</span>
                        <span class="block text-xs text-gray-500">Create detailed analytics</span>
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Monthly trends chart
const monthlyTrendsChart = new Chart(
    document.getElementById('monthlyTrendsChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($monthly_labels) ?>,
            datasets: [{
                    label: 'Issues Reported',
                    data: <?= json_encode($monthly_issues) ?>,
                    borderColor: 'rgb(79, 70, 229)',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Issues Resolved',
                    data: <?= json_encode($monthly_resolved) ?>,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'transparent',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
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
    }
);

// Status distribution chart
const statusDistributionChart = new Chart(
    document.getElementById('statusDistributionChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Under Review', 'In Progress', 'Resolved', 'Rejected'],
            datasets: [{
                data: [
                    <?= $issues_stats['pending_issues'] ?>,
                    <?= $issues_stats['under_review_issues'] ?>,
                    <?= $issues_stats['in_progress_issues'] ?>,
                    <?= $issues_stats['resolved_issues'] ?>,
                    <?= $issues_stats['rejected_issues'] ?>
                ],
                backgroundColor: [
                    'rgb(251, 191, 36)', // amber-400
                    'rgb(59, 130, 246)', // blue-500
                    'rgb(139, 92, 246)', // purple-500
                    'rgb(16, 185, 129)', // green-500
                    'rgb(239, 68, 68)' // red-500
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            cutout: '60%'
        }
    }
);

// Severity distribution chart
const severityDistributionChart = new Chart(
    document.getElementById('severityDistributionChart'), {
        type: 'pie',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low'],
            datasets: [{
                data: [
                    <?= $issues_stats['critical_issues'] ?>,
                    <?= $issues_stats['high_severity_issues'] ?>,
                    <?= $issues_stats['medium_severity_issues'] ?>,
                    <?= $issues_stats['low_severity_issues'] ?>
                ],
                backgroundColor: [
                    'rgb(239, 68, 68)', // red-500
                    'rgb(249, 115, 22)', // orange-500
                    'rgb(251, 191, 36)', // amber-400
                    'rgb(16, 185, 129)' // green-500
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    }
);

// Electoral areas chart
const electoralAreasChart = new Chart(
    document.getElementById('electoralAreasChart'), {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($electoral_areas_stats as $area): ?> "<?= addslashes($area['name']) ?>",
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Number of Issues',
                data: [
                    <?php foreach ($electoral_areas_stats as $area): ?>
                    <?= $area['issue_count'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(79, 70, 229, 0.6)',
                borderColor: 'rgb(79, 70, 229)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    }
);
</script>

<?php include_once '../includes/footer.php'; ?>