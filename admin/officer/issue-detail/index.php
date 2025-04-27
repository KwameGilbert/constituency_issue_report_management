<?php
// issue-detail.php - Detailed view of a specific issue
session_start();

// Check if user is logged in and is a field officer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: login.php");
    exit();
}

require_once 'includes/db_connect.php';

// Check if issue ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: issues.php");
    exit();
}

$issue_id = (int)$_GET['id'];
$officer_id = $_SESSION['user_id'];

// Fetch issue details
$query = "SELECT * FROM issues WHERE id = ? AND officer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $issue_id, $officer_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if issue exists and belongs to this officer
if($result->num_rows === 0) {
    header("Location: issues.php");
    exit();
}

$issue = $result->fetch_assoc();

// Fetch photos for this issue
$photos_query = "SELECT * FROM issue_photos WHERE issue_id = ? ORDER BY uploaded_at";
$photos_stmt = $conn->prepare($photos_query);
$photos_stmt->bind_param("i", $issue_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$photos = [];
while($photo = $photos_result->fetch_assoc()) {
    $photos[] = $photo;
}

// Fetch status history
$history_query = "SELECT * FROM issue_status_history WHERE issue_id = ? ORDER BY updated_at DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $issue_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
$status_history = [];
while($history = $history_result->fetch_assoc()) {
    $status_history[] = $history;
}

// Fetch comments
$comments_query = "SELECT c.*, u.name, u.role FROM issue_comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.issue_id = ? ORDER BY c.created_at";
$comments_stmt = $conn->prepare($comments_query);
$comments_stmt->bind_param("i", $issue_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
$comments = [];
while($comment = $comments_result->fetch_assoc()) {
    $comments[] = $comment;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Details - Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
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
                <a href="dashboard.php" class="flex items-center py-3 px-4 hover:bg-blue-700">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="issues.php" class="flex items-center py-3 px-4 bg-blue-900">
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
                    <div class="flex items-center">
                        <a href="issues.php" class="text-blue-600 hover:text-blue-800 mr-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="text-2xl font-semibold text-gray-900">Issue Details</h1>
                    </div>
                    <div class="flex space-x-2">
                        <a href="edit-issue.php?id=<?php echo $issue_id; ?>"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-edit mr-2"></i> Edit Issue
                        </a>
                        <button onclick="confirmDelete(<?php echo $issue_id; ?>)"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-trash-alt mr-2"></i> Delete
                        </button>
                    </div>
                </div>
            </header>

            <!-- Issue Content -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4">
                <div class="max-w-5xl mx-auto">
                    <!-- Issue Overview -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($issue['title']); ?></h2>
                                    <p class="text-gray-500 mt-1">
                                        Reported on
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($issue['created_at'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        <?php 
                                        $status_color = '';
                                        if ($issue['status'] == 'pending') $status_color = 'bg-yellow-100 text-yellow-800';
                                        if ($issue['status'] == 'in_progress') $status_color = 'bg-blue-100 text-blue-800';
                                        if ($issue['status'] == 'resolved') $status_color = 'bg-green-100 text-green-800';
                                        echo $status_color;
                                        ?>">
                                        <i class="fas fa-circle text-xs mr-1.5"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                                <div class="col-span-2">
                                    <div class="mb-6">
                                        <h3 class="text-sm font-medium text-gray-500 mb-1">Description</h3>
                                        <p class="text-gray-900 whitespace-pre-line">
                                            <?php echo nl2br(htmlspecialchars($issue['description'])); ?></p>
                                    </div>

                                    <?php if (!empty($issue['additional_notes'])): ?>
                                    <div class="mb-6">
                                        <h3 class="text-sm font-medium text-gray-500 mb-1">Additional Notes</h3>
                                        <p class="text-gray-900 whitespace-pre-line">
                                            <?php echo nl2br(htmlspecialchars($issue['additional_notes'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="text-sm font-medium text-gray-500 mb-3">Issue Information</h3>

                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-xs text-gray-500">Issue ID</p>
                                            <p class="text-sm font-medium">#<?php echo $issue['id']; ?></p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-gray-500">Location</p>
                                            <p class="text-sm font-medium">
                                                <?php echo htmlspecialchars($issue['location']); ?></p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-gray-500">Electoral Area</p>
                                            <p class="text-sm font-medium">
                                                <?php echo htmlspecialchars($issue['electoral_area']); ?></p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-gray-500">Severity</p>
                                            <p class="text-sm font-medium">
                                                <?php 
                                                $severity_color = 'text-gray-900';
                                                if ($issue['severity'] == 'critical') $severity_color = 'text-red-600';
                                                if ($issue['severity'] == 'high') $severity_color = 'text-orange-600';
                                                if ($issue['severity'] == 'medium') $severity_color = 'text-yellow-600';
                                                if ($issue['severity'] == 'low') $severity_color = 'text-green-600';
                                                ?>
                                                <span class="<?php echo $severity_color; ?>">
                                                    <?php echo ucfirst($issue['severity']); ?>
                                                </span>
                                            </p>
                                        </div>

                                        <?php if (!empty($issue['people_affected'])): ?>
                                        <div>
                                            <p class="text-xs text-gray-500">People Affected</p>
                                            <p class="text-sm font-medium">
                                                <?php echo number_format($issue['people_affected']); ?></p>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($issue['contact_person'])): ?>
                                        <div>
                                            <p class="text-xs text-gray-500">Contact Person</p>
                                            <p class="text-sm font-medium">
                                                <?php echo htmlspecialchars($issue['contact_person']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Evidence -->
                    <?php if (!empty($photos)): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Photo Evidence</h3>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php foreach ($photos as $photo): ?>
                                <a href="<?php echo htmlspecialchars($photo['file_path']); ?>"
                                    data-lightbox="issue-photos" class="block">
                                    <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Issue Photo"
                                        class="rounded-lg object-cover w-full h-36 shadow-sm hover:opacity-90 transition">
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Status Updates -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Status Updates</h3>

                            <div class="flow-root">
                                <ul class="-mb-8">
                                    <?php if (empty($status_history)): ?>
                                    <li>
                                        <div class="relative pb-8">
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span
                                                        class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-plus text-white text-sm"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">Issue created with status <span
                                                                class="font-medium text-gray-900">Pending</span></p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php else: ?>
                                    <?php foreach ($status_history as $index => $history): ?>
                                    <li>
                                        <div class="relative pb-8">
                                            <?php if ($index !== count($status_history) - 1): ?>
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                                aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <?php 
                                                        $icon_color = 'bg-gray-400';
                                                        $icon = 'fas fa-spinner';
                                                        if ($history['status'] == 'pending') {
                                                            $icon_color = 'bg-yellow-500';
                                                            $icon = 'fas fa-hourglass-half';
                                                        }
                                                        if ($history['status'] == 'in_progress') {
                                                            $icon_color = 'bg-blue-500';
                                                            $icon = 'fas fa-cogs';
                                                        }
                                                        if ($history['status'] == 'resolved') {
                                                            $icon_color = 'bg-green-500';
                                                            $icon = 'fas fa-check';
                                                        }
                                                        ?>
                                                    <span
                                                        class="h-8 w-8 rounded-full <?php echo $icon_color; ?> flex items-center justify-center ring-8 ring-white">
                                                        <i class="<?php echo $icon; ?> text-white text-sm"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">Status changed to <span
                                                                class="font-medium text-gray-900"><?php echo ucfirst(str_replace('_', ' ', $history['status'])); ?></span>
                                                        </p>
                                                        <?php if (!empty($history['notes'])): ?>
                                                        <p class="mt-1 text-sm text-gray-700">
                                                            <?php echo htmlspecialchars($history['notes']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <?php echo date('M d, Y', strtotime($history['updated_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Comments and Updates -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Comments and Updates</h3>

                            <?php if (empty($comments)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-comments text-3xl text-gray-300 mb-2"></i>
                                <p class="text-gray-500">No comments or updates yet.</p>
                                <p class="text-sm text-gray-400">Updates will appear here when the PA or MCE adds
                                    information.</p>
                            </div>
                            <?php else: ?>
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    <?php foreach ($comments as $index => $comment): ?>
                                    <li>
                                        <div class="relative pb-8">
                                            <?php if ($index !== count($comments) - 1): ?>
                                            <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200"
                                                aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <div class="relative flex items-start space-x-3">
                                                <div class="relative">
                                                    <div
                                                        class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium text-sm">
                                                        <?php echo strtoupper(substr($comment['name'], 0, 1)); ?>
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-sm">
                                                        <span
                                                            class="font-medium text-gray-900"><?php echo htmlspecialchars($comment['name']); ?></span>
                                                        <span
                                                            class="text-gray-500 ml-1">(<?php echo ucfirst(str_replace('_', ' ', $comment['role'])); ?>)</span>
                                                    </div>
                                                    <div class="mt-2 text-sm text-gray-700">
                                                        <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                                        </p>
                                                    </div>
                                                    <div class="mt-2 text-xs text-gray-500">
                                                        <?php echo date('M d, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Delete Issue
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete this issue? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="deleteForm" method="POST" action="delete-issue.php">
                        <input type="hidden" id="deleteIssueId" name="issue_id" value="">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete
                        </button>
                    </form>
                    <button type="button" onclick="closeDeleteModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
    function confirmDelete(issueId) {
        document.getElementById('deleteIssueId').value = issueId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Initialize lightbox
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': "Photo %1 of %2"
    });
    </script>
</body>

</html>