<?php
// filepath: c:\xampp\htdocs\swma\admin\pa\reports\index.php
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

// Get date filters if provided
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Prepare date condition for SQL queries
$date_condition = '';
if ($date_range === 'custom' && !empty($start_date) && !empty($end_date)) {
    $date_condition = "AND issues.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
} elseif ($date_range === 'month') {
    $date_condition = "AND issues.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($date_range === 'quarter') {
    $date_condition = "AND issues.created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
} elseif ($date_range === 'year') {
    $date_condition = "AND issues.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
}

// Get overall issue statistics
$overall_query = "SELECT 
    COUNT(*) as total_issues,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review_issues,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_issues,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_issues,
    SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_severity,
    SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium_severity,
    SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_severity,
    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_severity,
    SUM(people_affected) as total_people_affected
FROM issues
WHERE 1=1 $date_condition";

$overall_result = $conn->query($overall_query);
$overall_stats = $overall_result->fetch_assoc();

// Calculate resolution rate
$resolution_rate = 0;
if ($overall_stats['total_issues'] > 0) {
    $resolution_rate = round(($overall_stats['resolved_issues'] / $overall_stats['total_issues']) * 100);
}

// Calculate average resolution time (in days)
$avg_resolution_query = "SELECT 
    AVG(DATEDIFF(updated_at, created_at)) as avg_resolution_days
FROM issues
WHERE status = 'resolved' $date_condition";
$avg_resolution_result = $conn->query($avg_resolution_query);
$avg_resolution_row = $avg_resolution_result->fetch_assoc();
$avg_resolution_days = round($avg_resolution_row['avg_resolution_days'] ?? 0, 1);

// Get electoral area breakdown
$area_query = "SELECT 
    ea.name as area_name,
    COUNT(*) as issue_count,
    SUM(CASE WHEN issues.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
FROM issues
LEFT JOIN electoral_areas ea ON issues.electoral_area_id = ea.id
WHERE 1=1 $date_condition
GROUP BY issues.electoral_area_id
ORDER BY issue_count DESC
LIMIT 10";
$area_result = $conn->query($area_query);
$area_data = [];
$area_labels = [];
$area_counts = [];
$area_resolved = [];
while ($area = $area_result->fetch_assoc()) {
    $area_name = $area['area_name'] ? $area['area_name'] : 'Unassigned';
    $area_labels[] = $area_name;
    $area_counts[] = $area['issue_count'];
    $area_resolved[] = $area['resolved_count'];
    $area_data[] = [
        'name' => $area_name,
        'count' => $area['issue_count'],
        'resolved' => $area['resolved_count']
    ];
}

// Get monthly trends data
$monthly_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as issue_count,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
FROM issues
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month ASC";
$monthly_result = $conn->query($monthly_query);
$monthly_labels = [];
$monthly_issues = [];
$monthly_resolved = [];
while ($month = $monthly_result->fetch_assoc()) {
    $date = date_create_from_format('Y-m', $month['month']);
    $formatted_date = date_format($date, 'M Y');
    $monthly_labels[] = $formatted_date;
    $monthly_issues[] = $month['issue_count'];
    $monthly_resolved[] = $month['resolved_count'];
}

// Get officer performance
$officer_query = "SELECT 
    fo.name as officer_name,
    COUNT(*) as assigned_issues,
    SUM(CASE WHEN issues.status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
    ROUND(AVG(DATEDIFF(issues.updated_at, issues.created_at)), 1) as avg_resolution_days
FROM issues
JOIN field_officers fo ON issues.officer_id = fo.id
WHERE issues.officer_id IS NOT NULL $date_condition
GROUP BY issues.officer_id
ORDER BY resolved_issues DESC
LIMIT 10";
$officer_result = $conn->query($officer_query);
$officer_data = [];
while ($officer = $officer_result->fetch_assoc()) {
    $officer_data[] = $officer;
}

// Get severity breakdown
$severity_data = [
    'low' => $overall_stats['low_severity'],
    'medium' => $overall_stats['medium_severity'],
    'high' => $overall_stats['high_severity'],
    'critical' => $overall_stats['critical_severity']
];

// Get status breakdown
$status_data = [
    'pending' => $overall_stats['pending_issues'],
    'under_review' => $overall_stats['under_review_issues'],
    'in_progress' => $overall_stats['in_progress_issues'],
    'resolved' => $overall_stats['resolved_issues'],
    'rejected' => $overall_stats['rejected_issues']
];

// Get top issues by people affected
$top_issues_query = "SELECT 
    issues.id,
    issues.title,
    issues.severity,
    issues.status,
    issues.people_affected,
    ea.name as area_name
FROM issues
LEFT JOIN electoral_areas ea ON issues.electoral_area_id = ea.id
WHERE issues.people_affected > 0 $date_condition
ORDER BY issues.people_affected DESC
LIMIT 5";
$top_issues_result = $conn->query($top_issues_query);
$top_issues = [];
while ($issue = $top_issues_result->fetch_assoc()) {
    $top_issues[] = $issue;
}

// Set the page title and current page identifier for the header
$page_title = "Reports & Analytics - PA Portal";
$current_page = 'reports';

// Include the header (which will handle the overall layout and sidebar)
include_once '../includes/header.php';
?>

<!-- Load Chart.js library before initializing charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <!-- Date range filter -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div>
                    <label for="date_range" class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <select id="date_range" name="date_range" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" onchange="toggleCustomDateFields(this.value)">
                        <option value="all" <?= $date_range === 'all' ? 'selected' : '' ?>>All Time</option>
                        <option value="month" <?= $date_range === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="quarter" <?= $date_range === 'quarter' ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="year" <?= $date_range === 'year' ? 'selected' : '' ?>>Last Year</option>
                        <option value="custom" <?= $date_range === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <div id="custom_date_fields" class="flex gap-4" style="<?= $date_range === 'custom' ? '' : 'display: none;' ?>">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                    </div>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-filter mr-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-sm font-medium text-gray-500">Total Issues</h2>
                        <p class="text-3xl font-semibold text-gray-800 mt-1"><?= number_format($overall_stats['total_issues']) ?></p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
                <div class="flex items-center mt-4 text-sm">
                    <span class="text-gray-500">From <?= $date_range === 'custom' ? $start_date . ' to ' . $end_date : ($date_range === 'all' ? 'all time' : 'the past ' . ($date_range === 'month' ? '30 days' : ($date_range === 'quarter' ? '90 days' : 'year'))) ?></span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-sm font-medium text-gray-500">Resolution Rate</h2>
                        <p class="text-3xl font-semibold text-gray-800 mt-1"><?= $resolution_rate ?>%</p>
                    </div>
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="flex items-center mt-4 text-sm">
                    <span class="text-green-500"><?= number_format($overall_stats['resolved_issues']) ?></span>
                    <span class="text-gray-500 mx-1">of</span>
                    <span class="text-blue-500"><?= number_format($overall_stats['total_issues']) ?></span>
                    <span class="text-gray-500 ml-1">issues resolved</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-sm font-medium text-gray-500">Avg. Resolution Time</h2>
                        <p class="text-3xl font-semibold text-gray-800 mt-1"><?= $avg_resolution_days ?> days</p>
                    </div>
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="flex items-center mt-4 text-sm">
                    <span class="text-gray-500">For resolved issues</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-sm font-medium text-gray-500">People Affected</h2>
                        <p class="text-3xl font-semibold text-gray-800 mt-1"><?= number_format($overall_stats['total_people_affected'] ?? 0) ?></p>
                    </div>
                    <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="flex items-center mt-4 text-sm">
                    <span class="text-gray-500">Across all reported issues</span>
                </div>
            </div>
        </div>

        <!-- Charts Section (Row 1) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Monthly Trends Chart -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Monthly Issue Trends</h2>
                <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                    <canvas id="monthlyTrendsChart"></canvas>
                </div>
            </div>

            <!-- Electoral Area Breakdown -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Top Electoral Areas by Issues</h2>
                <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                    <canvas id="electoralAreasChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Section (Row 2) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Status Distribution -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Issue Status Distribution</h2>
                <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                    <canvas id="statusDistributionChart"></canvas>
                </div>
            </div>

            <!-- Severity Distribution -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Issue Severity Distribution</h2>
                <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                    <canvas id="severityDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Officer Performance Table -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Officer Performance</h2>

            <?php if (count($officer_data) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Officer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Issues</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolved Issues</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolution Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Resolution Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($officer_data as $officer): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($officer['officer_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= number_format($officer['assigned_issues']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= number_format($officer['resolved_issues']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= round(($officer['resolved_issues'] / $officer['assigned_issues']) * 100) ?>%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $officer['avg_resolution_days'] ?> days
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No officer performance data available for the selected time period.</p>
            <?php endif; ?>
        </div>

        <!-- Top Issues by People Affected -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Top Issues by People Affected</h2>

            <?php if (count($top_issues) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Electoral Area</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">People Affected</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($top_issues as $issue): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <a href="../issue-detail/?id=<?= $issue['id'] ?>" class="hover:text-blue-600">
                                            <?= htmlspecialchars($issue['title']) ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($issue['area_name'] ?? 'Unassigned') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    switch ($issue['severity']) {
                                        case 'low':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'medium':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'high':
                                            echo 'bg-amber-100 text-amber-800';
                                            break;
                                        case 'critical':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                    }
                                    ?>">
                                            <?= ucfirst($issue['severity']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    switch ($issue['status']) {
                                        case 'pending':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'under_review':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'in_progress':
                                            echo 'bg-purple-100 text-purple-800';
                                            break;
                                        case 'resolved':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'rejected':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                    }
                                    ?>">
                                            <?= ucfirst(str_replace('_', ' ', $issue['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= number_format($issue['people_affected']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No issues with people affected data for the selected time period.</p>
            <?php endif; ?>
        </div>

        <!-- Export Options -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-medium text-gray-800 mb-4">Export Reports</h2>
            <div class="flex flex-wrap gap-4">
                <a href="generate-report.php?format=pdf&date_range=<?= $date_range ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-file-pdf mr-2"></i> Export as PDF
                </a>
                <a href="generate-report.php?format=excel&date_range=<?= $date_range ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-file-excel mr-2"></i> Export as Excel
                </a>
                <a href="generate-report.php?format=print&date_range=<?= $date_range ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-print mr-2"></i> Print Report
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle custom date fields based on selection
    function toggleCustomDateFields(value) {
        const customDateFields = document.getElementById('custom_date_fields');
        if (value === 'custom') {
            customDateFields.style.display = 'flex';
        } else {
            customDateFields.style.display = 'none';
        }
    }

    // Monthly Trends Chart
    const monthlyTrendsChart = new Chart(
        document.getElementById('monthlyTrendsChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($monthly_labels) ?>,
                datasets: [{
                        label: 'Issues Reported',
                        data: <?= json_encode($monthly_issues) ?>,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
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

    // Electoral Areas Chart
    const electoralAreasChart = new Chart(
        document.getElementById('electoralAreasChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($area_labels) ?>,
                datasets: [{
                        label: 'Total Issues',
                        data: <?= json_encode($area_counts) ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Resolved Issues',
                        data: <?= json_encode($area_resolved) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );

    // Status Distribution Chart
    const statusDistributionChart = new Chart(
        document.getElementById('statusDistributionChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Under Review', 'In Progress', 'Resolved', 'Rejected'],
                datasets: [{
                    data: [
                        <?= $status_data['pending'] ?>,
                        <?= $status_data['under_review'] ?>,
                        <?= $status_data['in_progress'] ?>,
                        <?= $status_data['resolved'] ?>,
                        <?= $status_data['rejected'] ?>
                    ],
                    backgroundColor: [
                        'rgb(251, 191, 36)', // amber-400
                        'rgb(59, 130, 246)', // blue-500
                        'rgb(139, 92, 246)', // purple-500
                        'rgb(16, 185, 129)', // green-500
                        'rgb(239, 68, 68)' // red-500
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        }
    );

    // Severity Distribution Chart
    const severityDistributionChart = new Chart(
        document.getElementById('severityDistributionChart'), {
            type: 'pie',
            data: {
                labels: ['Low', 'Medium', 'High', 'Critical'],
                datasets: [{
                    data: [
                        <?= $severity_data['low'] ?>,
                        <?= $severity_data['medium'] ?>,
                        <?= $severity_data['high'] ?>,
                        <?= $severity_data['critical'] ?>
                    ],
                    backgroundColor: [
                        'rgb(34, 197, 94)', // green-500
                        'rgb(59, 130, 246)', // blue-500
                        'rgb(251, 191, 36)', // amber-400
                        'rgb(239, 68, 68)' // red-500
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        }
    );
</script>

<?php include_once '../includes/footer.php'; ?>