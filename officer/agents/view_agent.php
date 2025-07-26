<?php
// view_agent.php - View individual agent details
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'agents';

// Get agent ID from URL parameter
$agent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize message variables
$message = '';
$message_type = '';

// Initialize agent variable
$agent = null;
$agentIssues = [];
$issueStats = [];

// Check if valid agent ID was provided
if ($agent_id <= 0) {
    $message = "Invalid agent ID.";
    $message_type = "error";
} else {
    // Fetch agent details
    try {
        $stmt = $conn->prepare("
            SELECT
                u.*,
                ea.name AS electoral_area_name,
                ea.constituency,
                ea.region,
                COUNT(i.id) AS total_issues,
                SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END) AS pending_issues,
                SUM(CASE WHEN i.status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed_issues,
                SUM(CASE WHEN i.status = 'approved' THEN 1 ELSE 0 END) AS approved_issues,
                SUM(CASE WHEN i.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_issues,
                SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) AS resolved_issues,
                AVG(CASE WHEN i.status = 'resolved' AND i.resolved_at IS NOT NULL 
                    THEN DATEDIFF(i.resolved_at, i.created_at) END) AS avg_resolution_days
            FROM users u
            LEFT JOIN electoral_areas ea ON u.electoral_area = ea.id
            LEFT JOIN issues i ON u.id = i.agent_id
            WHERE u.id = ? AND u.role = 'agent'
            GROUP BY u.id
        ");

        $stmt->execute([$agent_id]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$agent) {
            $message = "Agent not found.";
            $message_type = "error";
        } else {
            // Fetch recent issues submitted by this agent
            $stmt = $conn->prepare("
                SELECT
                    i.*,
                    ic.name AS category_name,
                    c.name AS community_name,
                    s.name AS suburb_name,
                    const.name AS constituent_name
                FROM issues i
                LEFT JOIN issue_categories ic ON i.category_id = ic.id
                LEFT JOIN communities c ON i.community_id = c.id
                LEFT JOIN suburbs s ON i.suburb_id = s.id
                LEFT JOIN constituents const ON i.constituent_id = const.id
                WHERE i.agent_id = ?
                ORDER BY i.created_at DESC
                LIMIT 10
            ");

            $stmt->execute([$agent_id]);
            $agentIssues = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prepare issue statistics
            $issueStats = [
                'total' => intval($agent['total_issues']),
                'pending' => intval($agent['pending_issues']),
                'reviewed' => intval($agent['reviewed_issues']),
                'approved' => intval($agent['approved_issues']),
                'rejected' => intval($agent['rejected_issues']),
                'resolved' => intval($agent['resolved_issues']),
                'avg_resolution_days' => $agent['avg_resolution_days'] ? round($agent['avg_resolution_days'], 1) : 0
            ];
        }
    } catch (Exception $e) {
        $message = "Error loading agent details: " . $e->getMessage();
        $message_type = "error";
    }
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Agents',
        'href' => './',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

if ($agent) {
    $headerActionButtons[] = [
        'icon' => 'fas fa-edit',
        'label' => 'Edit Agent',
        'href' => 'edit_agent.php?id=' . $agent_id,
        'class' => 'bg-indigo-900 text-white hover:bg-indigo-800'
    ];
}

// Function to get status badge class
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'reviewed':
            return 'bg-blue-100 text-blue-800';
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'resolved':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Function to get severity badge class
function getSeverityBadgeClass($severity)
{
    switch ($severity) {
        case 'high':
            return 'bg-red-100 text-red-800';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'low':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

$userName = $_SESSION['user_name'] ?? 'Officer';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Agent - Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

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
    <?php renderOfficerSidebar($current_page, getPendingIssuesCount($conn)); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php
        $pageTitle = $agent ? 'Agent Details: ' . $agent['name'] : 'Agent Details';
        $pageDescription = $agent ? $agent['email'] : 'Agent not found';
        renderOfficerHeader($pageTitle, $pageDescription, $headerActionButtons);
        ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-4 p-3 rounded-xl text-xs <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($agent) : ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Agent Profile Card -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Basic Information -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-center justify-center mb-4">
                                    <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <?php if (!empty($agent['profile_image'])) : ?>
                                            <img src="../../<?php echo htmlspecialchars($agent['profile_image']); ?>" alt="Profile Image" class="w-full h-full rounded-full object-cover">
                                        <?php else : ?>
                                            <i class="fas fa-user text-indigo-700 text-2xl"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="text-center mb-4">
                                    <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($agent['name']); ?></h2>
                                    <p class="text-sm text-gray-500">Field Agent</p>
                                    <div class="mt-2">
                                        <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $agent['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($agent['status']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <h3 class="text-xs font-semibold text-gray-700 mb-1">Email</h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($agent['email']); ?></p>
                                    </div>

                                    <?php if ($agent['phone']) : ?>
                                        <div>
                                            <h3 class="text-xs font-semibold text-gray-700 mb-1">Phone</h3>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($agent['phone']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($agent['department']) : ?>
                                        <div>
                                            <h3 class="text-xs font-semibold text-gray-700 mb-1">Department</h3>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($agent['department']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <h3 class="text-xs font-semibold text-gray-700 mb-1">Electoral Area</h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($agent['electoral_area_name'] ?? 'Not Assigned'); ?></p>
                                        <?php if ($agent['constituency']) : ?>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($agent['constituency'] . ', ' . $agent['region']); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <h3 class="text-xs font-semibold text-gray-700 mb-1">Joined</h3>
                                        <p class="text-sm text-gray-600"><?php echo date('M d, Y', strtotime($agent['created_at'])); ?></p>
                                    </div>

                                    <div>
                                        <h3 class="text-xs font-semibold text-gray-700 mb-1">Last Login</h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo $agent['last_login'] ? date('M d, Y H:i', strtotime($agent['last_login'])) : 'Never'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-5">
                                <h3 class="text-base font-semibold text-gray-800 mb-4">Quick Actions</h3>
                                <div class="space-y-3">
                                    <a href="edit_agent.php?id=<?php echo $agent_id; ?>" class="flex items-center w-full px-4 py-2 text-sm bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                                        <i class="fas fa-edit text-gray-500 mr-3"></i>
                                        Edit Agent Details
                                    </a>

                                    <?php if ($agent['status'] === 'active') : ?>
                                        <button onclick="toggleAgentStatus(<?php echo $agent_id; ?>, 'inactive')" class="flex items-center w-full px-4 py-2 text-sm bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors">
                                            <i class="fas fa-user-times mr-3"></i>
                                            Deactivate Agent
                                        </button>
                                    <?php else : ?>
                                        <button onclick="toggleAgentStatus(<?php echo $agent_id; ?>, 'active')" class="flex items-center w-full px-4 py-2 text-sm bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition-colors">
                                            <i class="fas fa-user-check mr-3"></i>
                                            Activate Agent
                                        </button>
                                    <?php endif; ?>

                                    <a href="../issues/?agent_id=<?php echo $agent_id; ?>" class="flex items-center w-full px-4 py-2 text-sm bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors">
                                        <i class="fas fa-list mr-3"></i>
                                        View All Issues
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics and Issues -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-800"><?php echo $issueStats['total']; ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Total Issues</div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-yellow-600"><?php echo $issueStats['pending']; ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Pending</div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600"><?php echo $issueStats['resolved']; ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Resolved</div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600"><?php echo $issueStats['avg_resolution_days']; ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Avg Days</div>
                                </div>
                            </div>
                        </div>

                        <!-- Issue Status Chart -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="text-base font-semibold text-gray-800">Issue Status Distribution</h3>
                            </div>
                            <div class="p-5">
                                <div class="h-64 flex items-center justify-center">
                                    <canvas id="issueStatusChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Issues -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="text-base font-semibold text-gray-800">Recent Issues</h3>
                            </div>

                            <?php if (empty($agentIssues)) : ?>
                                <div class="p-5 text-center text-sm text-gray-500">
                                    No issues submitted by this agent yet.
                                </div>
                            <?php else : ?>
                                <div class="divide-y divide-gray-100">
                                    <?php foreach ($agentIssues as $issue) : ?>
                                        <div class="p-5 hover:bg-gray-50 transition-colors">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h4 class="text-sm font-medium text-gray-800 mb-1">
                                                        <a href="../issues/view_issue.php?id=<?php echo $issue['id']; ?>" class="hover:text-primary">
                                                            <?php echo htmlspecialchars($issue['title']); ?>
                                                        </a>
                                                    </h4>

                                                    <div class="flex flex-wrap gap-2 mb-2">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getStatusBadgeClass($issue['status']); ?>">
                                                            <?php echo ucfirst($issue['status']); ?>
                                                        </span>

                                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getSeverityBadgeClass($issue['severity']); ?>">
                                                            <?php echo ucfirst($issue['severity']); ?>
                                                        </span>

                                                        <?php if ($issue['category_name']) : ?>
                                                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">
                                                                <?php echo htmlspecialchars($issue['category_name']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <p class="text-xs text-gray-500 mb-2 line-clamp-2">
                                                        <?php echo htmlspecialchars(substr($issue['description'], 0, 120)) . (strlen($issue['description']) > 120 ? '...' : ''); ?>
                                                    </p>

                                                    <div class="flex items-center text-xs text-gray-500 space-x-4">
                                                        <?php if ($issue['community_name']) : ?>
                                                            <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($issue['community_name']); ?></span>
                                                        <?php endif; ?>

                                                        <?php if ($issue['constituent_name']) : ?>
                                                            <span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($issue['constituent_name']); ?></span>
                                                        <?php endif; ?>

                                                        <span><i class="fas fa-clock mr-1"></i><?php echo date('M d, Y', strtotime($issue['created_at'])); ?></span>
                                                    </div>
                                                </div>

                                                <a href="../issues/view_issue.php?id=<?php echo $issue['id']; ?>" class="ml-4 text-primary hover:text-primary-dark">
                                                    <i class="fas fa-arrow-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="p-5 border-t border-gray-100 text-center">
                                    <a href="../issues/?agent_id=<?php echo $agent_id; ?>" class="text-sm text-primary hover:text-primary-dark font-medium">
                                        View All Issues by This Agent <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 text-center">
                    <div class="text-red-500 mb-3"><i class="fas fa-exclamation-triangle fa-3x"></i></div>
                    <h2 class="text-xl font-medium text-gray-800 mb-2">Agent Not Found</h2>
                    <p class="text-gray-600 mb-4">The agent you are looking for does not exist or you don't have permission to access it.</p>
                    <a href="./" class="px-4 py-2 bg-indigo-900 text-white rounded-xl inline-block hover:bg-indigo-800 transition-colors text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Agents
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        <?php if ($agent && $issueStats['total'] > 0) : ?>
            // Create issue status chart
            const ctx = document.getElementById('issueStatusChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Reviewed', 'Approved', 'Rejected', 'Resolved'],
                    datasets: [{
                        data: [
                            <?php echo $issueStats['pending']; ?>,
                            <?php echo $issueStats['reviewed']; ?>,
                            <?php echo $issueStats['approved']; ?>,
                            <?php echo $issueStats['rejected']; ?>,
                            <?php echo $issueStats['resolved']; ?>
                        ],
                        backgroundColor: [
                            '#fbbf24', // yellow for pending
                            '#3b82f6', // blue for reviewed
                            '#10b981', // green for approved
                            '#ef4444', // red for rejected
                            '#6b7280' // gray for resolved
                        ],
                        borderWidth: 0
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
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>

        // Function to toggle agent status
        function toggleAgentStatus(agentId, newStatus) {
            if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this agent?`)) {
                const formData = new FormData();
                formData.append('agent_id', agentId);
                formData.append('status', newStatus);
                formData.append('action', 'toggle_status');

                fetch('toggle_agent_status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to update agent status'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the agent status');
                    });
            }
        }
    </script>
</body>

</html>