<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if(!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Set active page for sidebar
$active_page = 'issues';
$pageTitle = 'Issue Details';
$basePath = '../';

// Check if issue ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../issues/");
    exit();
}

$issue_id = (int)$_GET['id'];
$officer_id = $_SESSION['officer_id'];

// Fetch issue details
$query = "SELECT i.*, ea.name as electoral_area_name 
          FROM issues i 
          LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id 
          WHERE i.id = ? AND i.officer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $issue_id, $officer_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if issue exists and belongs to this officer
if($result->num_rows === 0) {
    header("Location: ../issues/");
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
$history_query = "SELECT * FROM issue_updates WHERE issue_id = ? ORDER BY created_at DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $issue_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
$status_history = [];
while($history = $history_result->fetch_assoc()) {
    $status_history[] = $history;
}

// // Fetch comments
// $comments_query = "SELECT c.*, fo.name as officer_name FROM issue_comments c 
//                   LEFT JOIN field_officers fo ON c.officer_id = fo.id 
//                   WHERE c.issue_id = ? ORDER BY c.created_at";
// $comments_stmt = $conn->prepare($comments_query);
// $comments_stmt->bind_param("i", $issue_id);
// $comments_stmt->execute();
// $comments_result = $comments_stmt->get_result();
// $comments = [];
// while($comment = $comments_result->fetch_assoc()) {
//     $comments[] = $comment;
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Details - Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <style>
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in-out forwards;
    }

    .hover-scale {
        transition: transform 0.2s ease-in-out;
    }

    .hover-scale:hover {
        transform: scale(1.01);
    }

    @keyframes slideInFromRight {
        from {
            transform: translateX(20px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .slide-in-right {
        animation: slideInFromRight 0.3s ease-out forwards;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar component -->
        <?php include_once '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header component -->
            <?php include_once '../includes/header.php'; ?>

            <!-- Overlay for mobile sidebar -->
            <div x-show="sidebarOpen" class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
                @click="sidebarOpen = false" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"></div>

            <!-- Issue Content -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6">
                <div class="max-w-5xl mx-auto">
                    <div class="flex items-center mb-6">
                        <a href="../issues/" class="text-amber-600 hover:text-amber-800 mr-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div class="flex space-x-2 ml-auto">
                            <a href="../edit-issue/?id=<?php echo $issue_id; ?>"
                                class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center transition-colors duration-300">
                                <i class="fas fa-edit mr-2"></i> Edit Issue
                            </a>
                            <button
                                onclick="confirmDelete(<?php echo $issue_id; ?>, '<?php echo addslashes($issue['title']); ?>')"
                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center transition-colors duration-300">
                                <i class="fas fa-trash-alt mr-2"></i> Delete
                            </button>
                        </div>
                    </div>

                    <!-- Issue Overview -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6 fade-in">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row justify-between items-start mb-4">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($issue['title']); ?></h2>
                                    <p class="text-gray-500 mt-1">
                                        Reported on
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($issue['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="mt-2 md:mt-0">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        <?php 
                                        $status_color = 'bg-gray-100 text-gray-800';
                                        if ($issue['status'] == 'pending') $status_color = 'bg-yellow-100 text-yellow-800';
                                        if ($issue['status'] == 'under_review') $status_color = 'bg-purple-100 text-purple-800';
                                        if ($issue['status'] == 'in_progress') $status_color = 'bg-blue-100 text-blue-800';
                                        if ($issue['status'] == 'resolved') $status_color = 'bg-green-100 text-green-800';
                                        if ($issue['status'] == 'rejected') $status_color = 'bg-red-100 text-red-800';
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

                                    <?php if (!empty($issue['resolution_notes'])): ?>
                                    <div class="mb-6">
                                        <h3 class="text-sm font-medium text-gray-500 mb-1">Resolution Notes</h3>
                                        <p class="text-gray-900 whitespace-pre-line">
                                            <?php echo nl2br(htmlspecialchars($issue['resolution_notes'])); ?></p>
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
                                                <?php echo htmlspecialchars($issue['electoral_area_name'] ?? 'Not assigned'); ?>
                                            </p>
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

                                        <?php if (!empty($issue['budget_estimate'])): ?>
                                        <div>
                                            <p class="text-xs text-gray-500">Budget Estimate</p>
                                            <p class="text-sm font-medium">GH₵
                                                <?php echo number_format($issue['budget_estimate'], 2); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Evidence -->
                    <?php if (!empty($photos)): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6 fade-in"
                        style="animation-delay: 0.1s;">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Photo Evidence</h3>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php foreach ($photos as $photo): ?>
                                <a href="<?php echo htmlspecialchars($photo['photo_url']); ?>"
                                    data-lightbox="issue-photos" class="block">
                                    <img src="<?php echo htmlspecialchars($photo['photo_url']); ?>" alt="Issue Photo"
                                        class="rounded-lg object-cover w-full h-36 shadow-sm hover:opacity-90 transition">
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Status Updates -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6 fade-in"
                        style="animation-delay: 0.2s;">
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
                                                        class="h-8 w-8 rounded-full bg-amber-500 flex items-center justify-center ring-8 ring-white">
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
                                                        if (strpos($history['status_change'], 'pending') !== false) {
                                                            $icon_color = 'bg-yellow-500';
                                                            $icon = 'fas fa-hourglass-half';
                                                        }
                                                        if (strpos($history['status_change'], 'under_review') !== false) {
                                                            $icon_color = 'bg-purple-500';
                                                            $icon = 'fas fa-search';
                                                        }
                                                        if (strpos($history['status_change'], 'in_progress') !== false) {
                                                            $icon_color = 'bg-blue-500';
                                                            $icon = 'fas fa-cogs';
                                                        }
                                                        if (strpos($history['status_change'], 'resolved') !== false) {
                                                            $icon_color = 'bg-green-500';
                                                            $icon = 'fas fa-check';
                                                        }
                                                        if (strpos($history['status_change'], 'rejected') !== false) {
                                                            $icon_color = 'bg-red-500';
                                                            $icon = 'fas fa-times';
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
                                                                class="font-medium text-gray-900"><?php echo ucfirst(str_replace('_', ' ', $history['status_change'])); ?></span>
                                                        </p>
                                                        <?php if (!empty($history['update_text'])): ?>
                                                        <p class="mt-1 text-sm text-gray-700">
                                                            <?php echo htmlspecialchars($history['update_text']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <?php echo date('M d, Y', strtotime($history['created_at'])); ?>
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
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6 fade-in"
                        style="animation-delay: 0.3s;">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Comments and Updates</h3>

                            <?php if (empty($comments)): ?>
                            <div class="text-center py-8">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600 mb-4">
                                    <i class="fas fa-comments text-3xl"></i>
                                </div>
                                <p class="text-gray-500">No comments or updates yet.</p>
                                <p class="text-sm text-gray-400">Updates will appear here when the PA or supervisor adds
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
                                                        class="h-10 w-10 rounded-full bg-amber-600 flex items-center justify-center text-white font-medium text-sm">
                                                        <?php echo strtoupper(substr($comment['officer_name'] ?? 'User', 0, 1)); ?>
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-sm">
                                                        <span
                                                            class="font-medium text-gray-900"><?php echo htmlspecialchars($comment['officer_name'] ?? 'System'); ?></span>
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

                            <!-- Add Comment Form -->
                            <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Add a Comment</h4>
                                <form action="add-comment.php" method="post">
                                    <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                                    <textarea name="comment" rows="3" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                                        placeholder="Write your comment here..."></textarea>
                                    <div class="mt-3 flex justify-end">
                                        <button type="submit"
                                            class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-300">
                                            <i class="fas fa-paper-plane mr-2"></i> Submit Comment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full slide-in-right">
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
                                <p class="text-sm text-gray-500" id="delete-message">
                                    Are you sure you want to delete this issue? All data related to this issue will be
                                    permanently removed.
                                    This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="deleteForm" method="POST" action="../issues/delete.php">
                        <input type="hidden" id="delete_issue_id" name="issue_id" value="">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-300">
                            Delete
                        </button>
                        <button type="button" onclick="closeDeleteModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-300">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
    // AlpineJS sidebar control
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', {
            open: false,
            toggle() {
                this.open = !this.open;
            }
        });
    });

    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        Alpine.store('sidebar').toggle();
    });

    function confirmDelete(id, title) {
        document.getElementById('delete_issue_id').value = id;
        document.getElementById('delete-message').innerHTML =
            `Are you sure you want to delete the issue "<strong>${title}</strong>"? All data related to this issue will be permanently removed. This action cannot be undone.`;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Initialize lightbox
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': "Photo %1 of %2"
    });
    </script>
</body>

</html>