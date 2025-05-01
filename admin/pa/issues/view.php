<?php
session_start();

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Check if issue ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to issues list if no ID provided
    header("Location: ./");
    exit();
}

$issue_id = (int)$_GET['id'];

// Get issue details
$query = "SELECT 
            i.*, 
            ea.name as electoral_area_name,
            fo.name as officer_name,
            fo.email as officer_email,
            fo.phone as officer_phone,
            s.name as supervisor_name
          FROM issues i
          LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
          LEFT JOIN field_officers fo ON i.officer_id = fo.id
          LEFT JOIN field_officers s ON i.supervisor_id = s.id
          WHERE i.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $issue_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Issue not found, redirect to issues list
    header("Location: ./");
    exit();
}

$issue = $result->fetch_assoc();

// Get issue photos
$photos_query = "SELECT id, photo_url, caption, uploaded_at FROM issue_photos WHERE issue_id = ? ORDER BY uploaded_at";
$photos_stmt = $conn->prepare($photos_query);
$photos_stmt->bind_param("i", $issue_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$photos = [];
while ($photo = $photos_result->fetch_assoc()) {
    $photos[] = $photo;
}

// Get issue comments
$comments_query = "SELECT 
                    ic.*, 
                    fo.name as officer_name,
                    fo.profile_pic as officer_pic
                  FROM issue_comments ic
                  LEFT JOIN field_officers fo ON ic.officer_id = fo.id
                  WHERE ic.issue_id = ? 
                  ORDER BY ic.created_at DESC";
$comments_stmt = $conn->prepare($comments_query);
$comments_stmt->bind_param("i", $issue_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
$comments = [];
while ($comment = $comments_result->fetch_assoc()) {
    $comments[] = $comment;
}

// Get issue updates
$updates_query = "SELECT 
                    iu.*, 
                    CASE
                        WHEN iu.officer_id = ? THEN 'pa'
                        ELSE 'officer'
                    END as user_role,
                    CASE
                        WHEN iu.officer_id = ? THEN pa.name
                        ELSE fo.name
                    END as officer_name,
                    CASE
                        WHEN iu.officer_id = ? THEN pa.profile_pic
                        ELSE fo.profile_pic
                    END as officer_pic
                  FROM issue_updates iu
                  LEFT JOIN personal_assistants pa ON pa.id = ?
                  LEFT JOIN field_officers fo ON fo.id = iu.officer_id AND iu.officer_id != ?
                  WHERE iu.issue_id = ? 
                  ORDER BY iu.created_at DESC";
$updates_stmt = $conn->prepare($updates_query);
$pa_id = $_SESSION['pa_id'];
$updates_stmt->bind_param("iiiiii", $pa_id, $pa_id, $pa_id, $pa_id, $pa_id, $issue_id);
$updates_stmt->execute();
$updates_result = $updates_stmt->get_result();
$updates = [];
while ($update = $updates_result->fetch_assoc()) {
    $updates[] = $update;
}

$page_title = "Issue #" . $issue_id . " - " . $issue['title'];
include_once '../includes/header.php';

// Helper function to format status for display
function formatStatus($status) {
    $text = str_replace('_', ' ', $status);
    return ucwords($text);
}

// Generate appropriate status badge classes
$status_class = match($issue['status']) {
    'pending' => 'bg-yellow-100 text-yellow-800',
    'under_review' => 'bg-blue-100 text-blue-800',
    'in_progress' => 'bg-purple-100 text-purple-800',
    'resolved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    default => 'bg-gray-100 text-gray-800'
};

// Generate appropriate severity badge classes
$severity_class = match($issue['severity']) {
    'critical' => 'bg-red-100 text-red-800',
    'high' => 'bg-orange-100 text-orange-800',
    'medium' => 'bg-yellow-100 text-yellow-800',
    'low' => 'bg-green-100 text-green-800',
    default => 'bg-gray-100 text-gray-800'
};

// Success and error message handling
$message = '';
$message_type = '';

if (isset($_GET['update_added']) && $_GET['update_added'] == 1) {
    $message = 'Status update has been successfully added.';
    $message_type = 'success';
} elseif (isset($_GET['error'])) {
    $error_type = $_GET['error'];
    $error_messages = [
        'update_failed' => 'Failed to add status update. Please try again.',
        'rejected_issue' => 'Cannot update a rejected issue.',
        'missing_fields' => 'Please fill in all required fields.',
        'invalid_status' => 'Invalid status value.',
        'invalid_transition' => 'Invalid status transition.',
        'closed_issue' => 'Cannot update a closed issue.'
    ];
    $message = $error_messages[$error_type] ?? 'An error occurred. Please try again.';
    $message_type = 'error';
} elseif (isset($_GET['status_updated']) && $_GET['status_updated'] == 1) {
    $message = 'Issue status has been successfully updated.';
    $message_type = 'success';
}
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <!-- Status Messages -->
        <?php if (!empty($message)): ?>
        <div
            class="mb-6 p-4 rounded-md <?= $message_type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
            <div class="flex">
                <div class="flex-shrink-0">
                    <?php if ($message_type === 'success'): ?>
                    <i class="fas fa-check-circle text-green-600"></i>
                    <?php else: ?>
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?= $message ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Back Button and Title -->
        <div class="flex flex-wrap justify-between items-center mb-6">
            <div class="w-full lg:w-auto mb-4 lg:mb-0">
                <a href="./" class="inline-flex items-center text-sm text-gray-700 hover:text-green-600">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Issues
                </a>
                <h1 class="text-2xl font-semibold text-gray-800 mt-2"><?= htmlspecialchars($issue['title']) ?></h1>
                <div class="flex flex-wrap items-center mt-2 text-sm text-gray-600">
                    <span class="mr-4">
                        <i class="fas fa-calendar-alt mr-1"></i> Reported:
                        <?= date('M d, Y, h:i A', strtotime($issue['created_at'])) ?>
                    </span>
                    <span>
                        <i class="fas fa-tag mr-1"></i> ID: <?= $issue_id ?>
                    </span>
                </div>
            </div>
            <div class="w-full lg:w-auto flex flex-wrap gap-2 mt-4 lg:mt-0">
                <?php if ($issue['status'] === 'pending'): ?>
                <a href="update-status.php?id=<?= $issue_id ?>&status=under_review"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-clipboard-check mr-2"></i> Mark as Under Review
                </a>
                <?php elseif ($issue['status'] === 'under_review'): ?>
                <a href="update-status.php?id=<?= $issue_id ?>&status=in_progress"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                    <i class="fas fa-tasks mr-2"></i> Mark as In Progress
                </a>
                <?php elseif ($issue['status'] === 'in_progress'): ?>
                <a href="update-status.php?id=<?= $issue_id ?>&status=resolved"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <i class="fas fa-check-circle mr-2"></i> Mark as Resolved
                </a>
                <?php endif; ?>
                <button type="button" id="printButton"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <i class="fas fa-print mr-2"></i> Print Details
                </button>
            </div>
        </div>

        <!-- Issue Status and Details -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Left Column - Status information -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Issue Status</h3>
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <span class="block text-sm font-medium text-gray-700 mb-1">Current Status</span>
                            <span
                                class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?= $status_class ?>">
                                <?= formatStatus($issue['status']) ?>
                            </span>
                        </div>
                        <div class="mb-4">
                            <span class="block text-sm font-medium text-gray-700 mb-1">Severity</span>
                            <span
                                class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?= $severity_class ?>">
                                <?= ucfirst($issue['severity']) ?>
                            </span>
                        </div>
                        <div class="mb-4">
                            <span class="block text-sm font-medium text-gray-700 mb-1">People Affected</span>
                            <span class="text-gray-900"><?= number_format($issue['people_affected'] ?? 0) ?></span>
                        </div>
                        <?php if ($issue['budget_estimate']): ?>
                        <div class="mb-4">
                            <span class="block text-sm font-medium text-gray-700 mb-1">Budget Estimate</span>
                            <span class="text-gray-900">GH₵ <?= number_format($issue['budget_estimate'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($issue['resolved_at']): ?>
                        <div class="mb-4">
                            <span class="block text-sm font-medium text-gray-700 mb-1">Resolved On</span>
                            <span class="text-gray-900"><?= date('M d, Y', strtotime($issue['resolved_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 pt-4 mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Field Officer Information</h4>
                            <p class="text-gray-900 font-medium">
                                <?= htmlspecialchars($issue['officer_name'] ?? 'Not assigned') ?></p>
                            <?php if ($issue['officer_email']): ?>
                            <p class="text-gray-600 text-sm mt-1">
                                <i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($issue['officer_email']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($issue['officer_phone']): ?>
                            <p class="text-gray-600 text-sm mt-1">
                                <i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($issue['officer_phone']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($issue['supervisor_name']): ?>
                        <div class="border-t border-gray-200 pt-4 mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Supervisor</h4>
                            <p class="text-gray-900"><?= htmlspecialchars($issue['supervisor_name']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Issue details -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Issue Details</h3>
                    </div>
                    <div class="p-4">
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Location</h4>
                            <div class="flex flex-wrap items-center">
                                <span class="text-gray-900 mr-3"><?= htmlspecialchars($issue['location']) ?></span>
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-md">
                                    <?= htmlspecialchars($issue['electoral_area_name'] ?? 'Unknown Area') ?>
                                </span>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Description</h4>
                            <div class="text-gray-700 prose max-w-none">
                                <?= nl2br(htmlspecialchars($issue['description'])) ?>
                            </div>
                        </div>

                        <?php if ($issue['resolution_notes'] && $issue['status'] === 'resolved'): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                            <h4 class="text-sm font-medium text-green-700 mb-2">Resolution Details</h4>
                            <div class="text-green-700">
                                <?= nl2br(htmlspecialchars($issue['resolution_notes'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($issue['additional_notes']): ?>
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Additional Notes</h4>
                            <div class="text-gray-700">
                                <?= nl2br(htmlspecialchars($issue['additional_notes'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Images Section -->
                        <?php if (count($photos) > 0): ?>
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Images</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-2">
                                <?php foreach ($photos as $photo): ?>
                                <div class="relative group">
                                    <img src="<?= htmlspecialchars($photo['photo_url']) ?>" alt="Issue Photo"
                                        class="h-40 w-full object-cover rounded-md cursor-pointer hover:opacity-90 transition-opacity"
                                        onclick="openImageModal('<?= htmlspecialchars($photo['photo_url']) ?>', '<?= htmlspecialchars($photo['caption'] ?? '') ?>')">
                                    <?php if ($photo['caption']): ?>
                                    <div
                                        class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-2 text-xs rounded-b-md">
                                        <?= htmlspecialchars($photo['caption']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Status Update Form (at the top of timeline) -->
        <?php if ($issue['status'] !== 'resolved' && $issue['status'] !== 'rejected'): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Add Status Update</h3>
            </div>
            <div class="p-4">
                <form action="../issue-detail/add-status-update.php" method="POST" class="space-y-4">
                    <input type="hidden" name="issue_id" value="<?= $issue_id ?>">
                    <div>
                        <label for="update_text" class="block text-sm font-medium text-gray-700 mb-1">Update
                            Details</label>
                        <textarea name="update_text" id="update_text" rows="3" required
                            class="shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-2 border-gray-300 rounded-md p-2"
                            placeholder="Provide an update on the current status, progress, or other important information about this issue..."></textarea>
                    </div>
                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-plus-circle mr-2"></i> Add Status Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Combined Updates and Comments Timeline -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Activity Timeline</h3>
            </div>
            <div class="p-4">
                <?php 
            // Merge updates and comments into a single array
            $timeline = [];
            
            foreach ($updates as $update) {
                $timeline[] = [
                'type' => 'update',
                'data' => $update,
                'date' => $update['created_at']
                ];
            }
            
            foreach ($comments as $comment) {
                $timeline[] = [
                'type' => 'comment',
                'data' => $comment,
                'date' => $comment['created_at']
                ];
            }

            // Sort timeline by date (newest first)
            usort($timeline, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            ?>

                <?php if (!empty($timeline)): ?>
                <div class="flow-root">
                    <ul class="-mb-8">
                        <?php foreach ($timeline as $index => $item): ?>
                        <li>
                            <div class="relative pb-8">
                                <?php if ($index !== count($timeline) - 1): ?>
                                <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200"
                                    aria-hidden="true"></span>
                                <?php endif; ?>

                                <?php if ($item['type'] === 'update'): ?>
                                <!-- Status Update -->
                                <div class="relative flex items-start space-x-3">
                                    <div class="relative">
                                        <?php if ($item['data']['officer_pic'] && file_exists("../../../" . $item['data']['officer_pic'])): ?>
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-200 overflow-hidden ring-8 ring-white">
                                            <img src="/<?= $item['data']['officer_pic'] ?>" alt="Profile picture"
                                                class="h-full w-full object-cover">
                                        </div>
                                        <?php else: ?>
                                        <div
                                            class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center ring-8 ring-white">
                                            <span class="text-white font-medium">
                                                <?= strtoupper(substr($item['data']['officer_name'] ?? 'U', 0, 1)) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div>
                                            <div class="text-sm">
                                                <span class="font-medium text-gray-900">
                                                    <?= htmlspecialchars($item['data']['officer_name'] ?? 'System') ?>
                                                    <span class="ml-1 text-xs font-normal text-gray-500">
                                                        (<?= $item['data']['user_role'] === 'pa' ? 'Personal Assistant' : 'Field Officer' ?>)
                                                    </span>
                                                </span>
                                            </div>
                                            <p class="mt-0.5 text-sm text-gray-500">
                                                <?= date('M d, Y h:i A', strtotime($item['data']['created_at'])) ?>
                                            </p>
                                        </div>
                                        <div class="mt-2 text-sm text-gray-700">
                                            <p><?= nl2br(htmlspecialchars($item['data']['update_text'])) ?></p>
                                        </div>
                                        <?php if ($item['data']['status_change']): ?>
                                        <div class="mt-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            <?= match($item['data']['status_change']) {
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'under_review' => 'bg-blue-100 text-blue-800',
                                'in_progress' => 'bg-purple-100 text-purple-800',
                                'resolved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            } ?>">
                                                Status changed to: <?= formatStatus($item['data']['status_change']) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- Comment -->
                                <div class="relative flex items-start space-x-3">
                                    <div class="relative">
                                        <?php if ($item['data']['officer_pic'] && file_exists("../../../" . $item['data']['officer_pic'])): ?>
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-200 overflow-hidden ring-8 ring-white">
                                            <img src="/<?= $item['data']['officer_pic'] ?>" alt="Profile picture"
                                                class="h-full w-full object-cover">
                                        </div>
                                        <?php else: ?>
                                        <div
                                            class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center ring-8 ring-white">
                                            <span class="text-white font-medium">
                                                <?= strtoupper(substr($item['data']['officer_name'] ?? 'U', 0, 1)) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0 flex-1 bg-gray-50 p-3 rounded-lg">
                                        <div>
                                            <div class="text-sm">
                                                <span class="font-medium text-gray-900">
                                                    <?= htmlspecialchars($item['data']['officer_name'] ?? 'Unknown User') ?>
                                                    <span class="ml-1 text-xs font-normal text-gray-500">(Field
                                                        Officer)</span>
                                                </span>
                                            </div>
                                            <p class="mt-0.5 text-sm text-gray-500">
                                                <?= date('M d, Y h:i A', strtotime($item['data']['created_at'])) ?>
                                            </p>
                                        </div>
                                        <div class="mt-2 text-sm text-gray-700">
                                            <p class="font-medium text-xs text-gray-500 mb-1">Comment:</p>
                                            <p><?= nl2br(htmlspecialchars($item['data']['comment'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php else: ?>
                <div class="text-center py-6">
                    <div class="text-gray-400 mb-2">
                        <i class="fas fa-history text-3xl"></i>
                    </div>
                    <p class="text-gray-500">No activity recorded yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="max-w-4xl w-full mx-4">
        <div class="relative">
            <button id="closeModal"
                class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-70 transition-colors">
                <i class="fas fa-times"></i>
            </button>
            <img id="modalImage" src="" alt="Enlarged issue photo" class="max-h-screen object-contain rounded-lg">
            <div id="modalCaption" class="text-white bg-black bg-opacity-50 p-4 rounded-b-lg"></div>
        </div>
    </div>
</div>

<script>
// Image modal functions
function openImageModal(src, caption) {
    document.getElementById('modalImage').src = src;
    document.getElementById('modalCaption').textContent = caption;
    document.getElementById('imageModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

document.getElementById('closeModal').addEventListener('click', function() {
    document.getElementById('imageModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
});

document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        document.getElementById('imageModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
});

// Print functionality
document.getElementById('printButton').addEventListener('click', function() {
    window.print();
});
</script>

<!-- Print Styles -->
<style media="print">
nav,
aside,
form,
button,
#printButton {
    display: none !important;
}

body {
    background-color: white !important;
}

.p-4.sm\:ml-64 {
    margin-left: 0 !important;
    padding: 0 !important;
}

.shadow-md {
    box-shadow: none !important;
}

.rounded-lg {
    border-radius: 0 !important;
}

.bg-white,
.bg-gray-50 {
    background-color: white !important;
}
</style>

<?php include_once '../includes/footer.php'; ?>