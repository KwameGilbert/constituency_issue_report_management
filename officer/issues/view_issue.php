<?php
// Include necessary files for session management, database connection, and UI components.
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';
// Instantiate the Database class to get a connection.
$database = new Database();
$conn = $database->getConnection();

// Include sidebar and header components.
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

// Define the current page for sidebar highlighting.
$current_page = 'issues';
// Get the officer ID from the session (assuming it's set during login).
$officerId = $_SESSION['user_id'];
// Get the issue ID from the URL, sanitizing it as an integer.
$issue_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables for messages and issue data.
$message = '';
$message_type = '';
$issue = null;
$history = [];
$updates = []; // Array to store recent updates with attachments

// Check if a valid issue ID is provided.
if ($issue_id > 0) {
    try {
        // Query to fetch comprehensive issue details along with related data from joined tables.
        $stmt = $conn->prepare("
            SELECT
                i.*,
                ic.name AS category_name,
                isec.name AS sector_name,
                issub.name AS subsector_name,
                ea.name AS electoral_area_name,
                ea.constituency AS electoral_area_constituency,
                ea.region AS electoral_area_region,
                c.name AS community_name,
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
            LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
            LEFT JOIN communities c ON i.community_id = c.id
            LEFT JOIN suburbs s ON i.suburb_id = s.id
            LEFT JOIN constituents const ON i.constituent_id = const.id
            LEFT JOIN users agent_user ON i.agent_id = agent_user.id
            LEFT JOIN users officer_user ON i.officer_id = officer_user.id
            WHERE i.id = ?
        ");
        $stmt->execute([$issue_id]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the issue was found.
        if (!$issue) {
            $message = "Issue not found.";
            $message_type = "error";
        } else {
            // Fetch all history entries for the full history timeline.
            $stmt = $conn->prepare("
                SELECT h.*, u.name AS user_name
                FROM issue_history_logs h
                LEFT JOIN users u ON h.user_id = u.id
                WHERE h.issue_id = ?
                ORDER BY h.created_at DESC
            ");
            $stmt->execute([$issue_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch the 5 most recent updates for the "Recent Updates" section, including user name.
            $stmt = $conn->prepare("
                SELECT h.*, u.name AS user_name
                FROM issue_history_logs h
                LEFT JOIN users u ON h.user_id = u.id
                WHERE h.issue_id = ?
                ORDER BY h.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$issue_id]);
            $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // For each update, fetch associated attachments and set display fields.
            foreach ($updates as &$update) { // Use reference to modify array elements directly
                $stmt = $conn->prepare("
                    SELECT id, file_name, file_path, file_type
                    FROM issue_attachments
                    WHERE log_id = ?
                ");
                $stmt->execute([$update['id']]);
                $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $update['attachments'] = $attachments;
                // Assign 'action' to 'title' and 'comment' to 'message' for consistent display.
                $update['title'] = htmlspecialchars($update['action']);
                $update['message'] = htmlspecialchars($update['comment']);

                // Check if the update action indicates a status change.
                if (strpos(strtolower($update['action']), 'status updated to') === 0) {
                    preg_match('/status updated to: (\w+)/i', $update['action'], $matches);
                    if (isset($matches[1])) {
                        $update['status_change'] = strtolower($matches[1]);
                    }
                }
            }
            unset($update); // Break the reference to avoid unintended side effects
        }
    } catch (Exception $e) {
        // Catch any exceptions during database operations and set an error message.
        $message = "Error loading issue details: " . $e->getMessage();
        $message_type = "error";
    }
} else {
    // If no valid issue ID is provided, set an error message.
    $message = "Invalid issue ID.";
    $message_type = "error";
}

// Prepare header action buttons.
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Issues',
        'href' => './',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

// Add 'Edit Issue' button only if the issue exists and its status is 'pending'.
if ($issue && $issue['status'] === 'pending') {
    $headerActionButtons[] = [
        'icon' => 'fas fa-edit',
        'label' => 'Edit Issue',
        'href' => 'edit_issue.php?id=' . $issue['id'],
        'class' => 'bg-indigo-700 text-white hover:bg-indigo-600'
    ];
}

// Determine the Tailwind CSS class for the issue status badge.
$statusClass = match ($issue['status'] ?? '') {
    'pending' => 'bg-yellow-100 text-yellow-800',
    'reviewed' => 'bg-purple-100 text-purple-800', // Styling for 'reviewed' status
    'approved' => 'bg-blue-100 text-blue-800',
    'rejected' => 'bg-red-100 text-red-800',
    'resolved' => 'bg-green-100 text-green-800',
    default => 'bg-gray-100 text-gray-800', // Default styling for unknown statuses
};

// Get the current user's name for display, defaulting to 'Officer'.
$userName = $_SESSION['user_name'] ?? 'Officer';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>
        <?php if ($issue): ?>
            View Issue #<?php echo $issue['id']; ?> - Officer Dashboard
        <?php else: ?>
            Issue Not Found - Officer Dashboard
        <?php endif; ?>
    </title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Inter for a clean sans-serif font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        // Tailwind CSS configuration to extend default theme colors and fonts.
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
                        },
                        purple: { // Custom color for 'reviewed' status
                            100: '#ede9fe',
                            800: '#6d28d9',
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
    <?php renderOfficerSidebar($current_page); // Render the officer sidebar 
    ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php
        // Render the header with issue ID/title and action buttons.
        if ($issue) {
            renderOfficerHeader('Issue #' . $issue['id'], $issue['title'], $headerActionButtons);
        } else {
            renderOfficerHeader('Issue Not Found', '', $headerActionButtons);
        }
        ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : // Display any system messages 
            ?>
                <div class="mb-4 p-3 rounded-xl text-xs <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($issue): // Only display issue details if the issue was found 
            ?>
                <!-- Issue Status Bar and Actions -->
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div class="flex items-center space-x-3 mb-2">
                                <!-- Status badge -->
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($issue['status']); ?>
                                </span>
                                <!-- Reported date -->
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-calendar-alt mr-1"></i> Reported on <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                                </span>
                            </div>
                            <?php if (!empty($issue['status_description'])) : // Display status description if available 
                            ?>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['status_description']); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Issue Actions - Procedural Status Update Buttons -->
                        <div class="flex flex-wrap gap-3 mt-4 sm:mt-0">
                            <?php if ($issue['status'] === 'pending'): ?>
                                <form action="process_issue_update.php" method="POST" class="inline-block">
                                    <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                                    <input type="hidden" name="new_status" value="reviewed">
                                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-xl hover:bg-purple-700 transition-colors flex items-center">
                                        <i class="fas fa-eye mr-2"></i> Mark as Reviewed
                                    </button>
                                </form>
                                <form action="process_issue_update.php" method="POST" class="inline-block">
                                    <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                                    <input type="hidden" name="new_status" value="rejected">
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 transition-colors flex items-center">
                                        <i class="fas fa-times-circle mr-2"></i> Reject Issue
                                    </button>
                                </form>
                            <?php elseif ($issue['status'] === 'reviewed'): ?>
                                <form action="process_issue_update.php" method="POST" class="inline-block">
                                    <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                                    <input type="hidden" name="new_status" value="approved">
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition-colors flex items-center">
                                        <i class="fas fa-check-circle mr-2"></i> Approve Issue
                                    </button>
                                </form>
                                <form action="process_issue_update.php" method="POST" class="inline-block">
                                    <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                                    <input type="hidden" name="new_status" value="rejected">
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 transition-colors flex items-center">
                                        <i class="fas fa-times-circle mr-2"></i> Reject Issue
                                    </button>
                                </form>
                            <?php elseif ($issue['status'] === 'approved'): ?>
                                <form action="process_issue_update.php" method="POST" class="inline-block">
                                    <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                                    <input type="hidden" name="new_status" value="resolved">
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-xl hover:bg-green-700 transition-colors flex items-center">
                                        <i class="fas fa-clipboard-check mr-2"></i> Mark as Resolved
                                    </button>
                                </form>
                            <?php elseif ($issue['status'] === 'rejected' || $issue['status'] === 'resolved'): ?>
                                <p class="text-sm text-gray-500">This issue is <strong><?php echo htmlspecialchars(ucfirst($issue['status'])); ?></strong> and cannot be updated further via status changes.</p>
                                <a href="./" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-300 transition-colors flex items-center">
                                    <i class="fas fa-list-alt mr-2"></i> View All Issues
                                </a>
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

                        <!-- Updates Section - Displays updates with attachments -->
                        <div class="mt-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="p-5 border-b border-gray-100">
                                    <h2 class="text-base font-semibold text-gray-800">Recent Updates</h2>
                                    <p class="text-xs text-gray-500 mt-1">Showing the 5 most recent updates for this issue.</p>
                                </div>

                                <div class="divide-y divide-gray-100">
                                    <?php
                                    if (empty($updates)) :
                                    ?>
                                        <div class="p-5 text-center text-sm text-gray-500">
                                            No updates have been added yet.
                                        </div>
                                    <?php else : ?>
                                        <?php foreach ($updates as $update) : ?>
                                            <div class="p-5">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h3 class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($update['title']); ?></h3>
                                                    <span class="text-xs text-gray-500"><?php echo date('M d, Y H:i', strtotime($update['created_at'])); ?></span>
                                                </div>

                                                <?php if (!empty($update['status_change'])) : ?>
                                                    <div class="mb-2 flex items-center gap-2">
                                                        <span class="text-xs font-medium text-gray-700">Status changed to:</span>
                                                        <!-- Dynamic styling for status change badge -->
                                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold 
                                                        <?php echo match ($update['status_change']) {
                                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                                            'reviewed' => 'bg-purple-100 text-purple-800',
                                                            'approved' => 'bg-blue-100 text-blue-800',
                                                            'rejected' => 'bg-red-100 text-red-800',
                                                            'resolved' => 'bg-green-100 text-green-800',
                                                            default => 'bg-gray-100 text-gray-800',
                                                        }; ?>">
                                                            <?php echo ucfirst($update['status_change']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <p class="text-sm text-gray-600 mb-3"><?php echo nl2br(htmlspecialchars($update['message'])); ?></p>

                                                <?php if (!empty($update['attachments'])) : ?>
                                                    <div class="mt-2">
                                                        <div class="text-xs font-medium text-gray-700 mb-1">Attachments:</div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <?php foreach ($update['attachments'] as $attachment) : ?>
                                                                <?php
                                                                // Determine if the attachment is an image for specific rendering.
                                                                $file_ext = pathinfo($attachment['file_name'], PATHINFO_EXTENSION);
                                                                $is_image = in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif']);
                                                                ?>
                                                                <?php if ($is_image) : ?>
                                                                    <!-- Image preview with hover effect for zoom -->
                                                                    <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="block w-16 h-16 rounded-lg overflow-hidden bg-gray-100 border border-gray-200 group relative">
                                                                        <img src="<?php echo htmlspecialchars($attachment['file_path']); ?>" alt="Attachment" class="w-full h-full object-cover group-hover:opacity-75 transition-opacity">
                                                                        <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                            <i class="fas fa-magnifying-glass text-white text-lg"></i>
                                                                        </div>
                                                                    </a>
                                                                <?php else : ?>
                                                                    <!-- Document icon and link -->
                                                                    <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200 transition-colors">
                                                                        <i class="fas fa-file-alt mr-1 text-slate-500"></i> <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                                    </a>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mt-2 text-xs text-gray-500">
                                                    By <?php echo htmlspecialchars($update['user_name']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
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
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['location'] ?? '-'); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Electoral Area</h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['electoral_area_name'] ?? '-'); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Community</h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['community_name'] ?? '-'); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Suburb</h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($issue['suburb_name'] ?? '-'); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Created</h3>
                                    <p class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($issue['created_at'])); ?></p>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-700 mb-1">Last Updated</h3>
                                    <p class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($issue['updated_at'])); ?></p>
                                </div>
                                <?php if (!empty($issue['resolved_at'])): ?>
                                    <div>
                                        <h3 class="text-xs font-semibold text-gray-700 mb-1">Resolved At</h3>
                                        <p class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($issue['resolved_at'])); ?></p>
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

                <!-- Issue Update Section - For General Comments and Attachments -->
                <div class="mt-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-5 border-b border-gray-100">
                            <h2 class="text-base font-semibold text-gray-800">Add New Update</h2>
                            <p class="text-xs text-gray-500 mt-1">Add comments, attachments, or general progress updates to this issue.</p>
                        </div>

                        <div class="p-5">
                            <form action="process_issue_update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                                <!-- This hidden input signals to process_issue_update.php that it's a general update -->
                                <input type="hidden" name="is_general_update" value="1">

                                <!-- Update Title -->
                                <div>
                                    <label for="update_title" class="block text-xs font-medium text-gray-700 mb-1">Update Title <span class="text-red-500">*</span></label>
                                    <input type="text" id="update_title" name="update_title" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900" placeholder="Brief title for this update">
                                </div>

                                <!-- Update Message -->
                                <div>
                                    <label for="update_message" class="block text-xs font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                                    <textarea id="update_message" name="update_message" rows="4" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900" placeholder="Detailed update about this issue..."></textarea>
                                </div>

                                <!-- File Uploads -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Attachments (Optional)</label>
                                    <div class="space-y-3">
                                        <!-- Image Upload Field -->
                                        <div class="border border-dashed border-gray-300 rounded-lg p-4 bg-gray-50">
                                            <div class="flex items-center">
                                                <span class="bg-slate-100 rounded-lg p-2 mr-3">
                                                    <i class="fas fa-image text-slate-500"></i>
                                                </span>
                                                <div class="flex-1">
                                                    <div class="text-xs font-medium text-gray-700">Images</div>
                                                    <div class="text-xs text-gray-500">Upload images related to this issue (JPG, PNG, GIF)</div>
                                                </div>
                                                <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif" multiple class="text-xs border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
                                            </div>
                                        </div>

                                        <!-- Document Upload Field -->
                                        <div class="border border-dashed border-gray-300 rounded-lg p-4 bg-gray-50">
                                            <div class="flex items-center">
                                                <span class="bg-slate-100 rounded-lg p-2 mr-3">
                                                    <i class="fas fa-file-alt text-slate-500"></i>
                                                </span>
                                                <div class="flex-1">
                                                    <div class="text-xs font-medium text-gray-700">Documents</div>
                                                    <div class="text-xs text-gray-500">Upload relevant documents (PDF, DOC, DOCX, XLS, XLSX)</div>
                                                </div>
                                                <input type="file" name="documents[]" accept=".pdf,.doc,.docx,.xls,.xlsx" multiple class="text-xs border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button for General Update -->
                                <div class="pt-4 flex justify-end">
                                    <button type="submit" class="px-5 py-2 bg-slate-900 text-white text-xs font-medium rounded-xl hover:bg-slate-800 transition-colors">
                                        <i class="fas fa-paper-plane mr-1"></i> Submit Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


            <?php else: ?>
                <!-- Fallback message if issue is not found -->
                <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-600">
                    <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                    <p class="text-lg font-semibold">Issue Not Found</p>
                    <p class="text-sm mt-2">The issue you are looking for does not exist or there was an error loading it.</p>
                    <a href="./" class="mt-5 inline-flex items-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-xl hover:bg-slate-800 transition-colors">
                        <i class="fas fa-list-alt mr-2"></i> Back to Issues List
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const updateForm = document.querySelector('.mt-6 form'); // Select the form for updates
    const recentUpdatesContainer = document.querySelector('.divide-y.divide-gray-100'); // Container for recent updates
    const initialNoUpdatesMessage = recentUpdatesContainer.querySelector('.p-5.text-center.text-sm.text-gray-500'); // The "No updates yet" message

    if (updateForm) {
        updateForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Get form data including files

            fetch('process_issue_update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Display success message
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'mb-4 p-3 rounded-xl text-xs bg-green-100 text-green-800';
                    messageDiv.textContent = data.message;
                    updateForm.closest('.mt-6').prepend(messageDiv); // Add message above the form

                    // Remove message after a few seconds
                    setTimeout(() => {
                        messageDiv.remove();
                    }, 5000);

                    // Add new update to the Recent Updates section
                    if (data.new_update_html) {
                        if (initialNoUpdatesMessage) {
                            initialNoUpdatesMessage.remove(); // Remove "No updates yet" if present
                        }
                        const newUpdateElement = document.createElement('div');
                        newUpdateElement.innerHTML = data.new_update_html;
                        recentUpdatesContainer.prepend(newUpdateElement.firstElementChild); // Add new update at the top
                    }

                    // Clear the form fields
                    updateForm.reset();
                    // Optionally, clear file inputs if needed (depends on browser behavior after reset)
                    const fileInputs = updateForm.querySelectorAll('input[type="file"]');
                    fileInputs.forEach(input => {
                        input.value = ''; // Clear file selection
                    });

                } else {
                    // Display error message
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'mb-4 p-3 rounded-xl text-xs bg-red-100 text-red-800';
                    messageDiv.textContent = data.message;
                    updateForm.closest('.mt-6').prepend(messageDiv);

                    setTimeout(() => {
                        messageDiv.remove();
                    }, 7000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const messageDiv = document.createElement('div');
                messageDiv.className = 'mb-4 p-3 rounded-xl text-xs bg-red-100 text-red-800';
                messageDiv.textContent = 'An unexpected error occurred. Please try again.';
                updateForm.closest('.mt-6').prepend(messageDiv);

                setTimeout(() => {
                    messageDiv.remove();
                }, 7000);
            });
        });
    }
});
</script>

</body>

</html>