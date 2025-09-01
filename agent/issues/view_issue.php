<?php
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
$current_page = 'issues';

$issue_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';
$issue = null;
$history = [];

if ($issue_id > 0) {
    try {
        // Query 1: Fetch issue and related data
        $stmt = $conn->prepare("
            SELECT
                i.*,
                ic.name AS category_name,
                isec.name AS sector_name,
                issub.name AS subsector_name,
                mc.name AS main_community_name,
                sc.name AS smaller_community_name,
                cot.name AS cottage_name,
                s.name AS suburb_name,
                const.name AS constituent_name,
                const.phone AS constituent_phone,
                const.location AS constituent_location,
                agent_user.name AS agent_name,
                officer_user.name AS officer_name
            FROM issues i
            LEFT JOIN issue_categories ic ON i.category_id = ic.id
            LEFT JOIN issue_sectors isec ON i.sector_id = isec.id
            LEFT JOIN issue_subsectors issub ON i.subsector_id = issub.id
            LEFT JOIN communities mc ON i.main_community_id = mc.id
            LEFT JOIN smaller_communities sc ON i.smaller_community_id = sc.id
            LEFT JOIN suburbs s ON i.suburb_id = s.id
            LEFT JOIN cottages cot ON i.cottage_id = cot.id
            LEFT JOIN constituents const ON i.constituent_id = const.id
            LEFT JOIN users agent_user ON i.agent_id = agent_user.id
            LEFT JOIN users officer_user ON i.officer_id = officer_user.id
            WHERE i.id = ?
        ");
        $stmt->execute([$issue_id]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$issue) {
            $message = "Issue not found.";
            $message_type = "error";
        } else {
            // Query 2: Fetch history entries manually
            $stmt = $conn->prepare("
                SELECT h.*, u.name AS user_name
                FROM issue_history_logs h
                LEFT JOIN users u ON h.user_id = u.id
                WHERE h.issue_id = ?
                ORDER BY h.created_at DESC
            ");
            $stmt->execute([$issue_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $message = "Error loading issue details: " . $e->getMessage();
        $message_type = "error";
    }
} else {
    $message = "Invalid issue ID.";
    $message_type = "error";
}

// Header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Issues',
        'href' => './',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

if (!empty($issue) && ($issue['status'] === 'pending')) {
    $headerActionButtons[] = [
        'icon' => 'fas fa-edit',
        'label' => 'Edit Issue',
        'href' => 'edit_issue.php?id=' . $issue['id'],
    ];
}

// Status styling
$statusClass = match ($issue['status'] ?? '') {
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-blue-100 text-blue-800',
    'rejected' => 'bg-red-100 text-red-800',
    'resolved' => 'bg-green-100 text-green-800',
    default => 'bg-gray-100 text-gray-800',
};

$userName = $_SESSION['user_name'] ?? 'Agent';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Issue #<?php echo htmlspecialchars($issue['id'] ?? ''); ?> - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAgentHeader('Issue #' . ($issue['id'] ?? ''), $issue['title'] ?? 'Issue details', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-4 p-3 rounded-xl text-xs <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Issue Status Bar -->
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                <?php echo ucfirst($issue['status']); ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-calendar-alt mr-1"></i> Reported on <?php echo !empty($issue['created_at']) ? date('M d, Y', strtotime($issue['created_at'])) : '-'; ?>
                            </span>
                        </div>
                        <?php if (!empty($issue['status_description'])) : ?>
                            <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($issue['status_description']); ?></p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Main Issue Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Issue Details Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-5 border-b border-gray-100">
                            <h2 class="text-base font-semibold text-gray-800">Issue Details</h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-1">Description</h3>
                                <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($issue['description'])); ?></p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Type</h3>
                                    <p class="text-sm text-gray-600"><?php echo ucfirst($issue['type']); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Category</h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['category_name'] ?? '-'); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Severity</h3>
                                    <p class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($issue['severity'] ?? '-'); ?></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Sector</h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['sector_name'] ?? '-'); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Subsector</h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['subsector_name'] ?? '-'); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">People Affected</h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['people_affected'] ?? '-'); ?></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Additional Notes</h3>
                                    <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($issue['additional_notes'] ?? '-')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Issue History Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-5 border-b border-gray-100">
                            <h2 class="text-base font-semibold text-gray-800">History</h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <?php if (!empty($history)) : ?>
                                <ol class="relative border-l border-gray-200">
                                    <?php foreach ($history as $log) : ?>
                                        <li class="mb-6 ml-4">
                                            <div class="absolute w-3 h-3 bg-slate-900 rounded-full mt-1.5 -left-1.5 border border-white"></div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-semibold text-gray-700"><?php echo htmlspecialchars($log['action'] ?? ''); ?></span>
                                                <span class="text-xs text-gray-400"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></span>
                                            </div>
                                            <div class="text-xs text-gray-500 mb-1">By <?php echo htmlspecialchars($log['user_name'] ?? '-'); ?></div>
                                            <div class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($log['comment'] ?? '')); ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php else : ?>
                                <p class="text-sm text-gray-500">No history available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Meta & Constituent Info -->
                <div class="space-y-6">

                    <!-- Location & Meta Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-5 border-b border-gray-100">
                            <h2 class="text-base font-semibold text-gray-800">Meta & Location</h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Location</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['location_description'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Main Community</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['main_community_name'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Smaller Community</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['smaller_community_name'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Suburb</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['suburb_name'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Cottage</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['cottage_name'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Created</h3>
                                <p class="text-sm text-gray-600"><?php echo !empty($issue['created_at']) ? date('M d, Y H:i', strtotime($issue['created_at'])) : '-'; ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Last Updated</h3>
                                <p class="text-sm text-gray-600"><?php echo !empty($issue['updated_at']) ? date('M d, Y H:i', strtotime($issue['updated_at'])) : '-'; ?></p>
                            </div>
                            <?php if (!empty($issue['resolved_at'])): ?>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Resolved At</h3>
                                    <p class="text-sm text-gray-600"><?php echo !empty($issue['resolved_at']) ? date('M d, Y H:i', strtotime($issue['resolved_at'])) : '-'; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Constituent Info Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-5 border-b border-gray-100">
                            <h2 class="text-base font-semibold text-gray-800">Constituent Info</h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Name</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['constituent_name'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Phone</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['constituent_phone'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Location</h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['constituent_location'] ?? '-'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>