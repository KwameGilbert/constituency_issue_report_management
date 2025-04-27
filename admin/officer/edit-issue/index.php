<?php
// edit-issue.php - Form for field officers to edit existing issues
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

// Get electoral areas for dropdown
$areas_query = "SELECT DISTINCT name FROM electoral_areas ORDER BY name";
$areas_result = $conn->query($areas_query);
$electoral_areas = [];
while($area = $areas_result->fetch_assoc()) {
    $electoral_areas[] = $area['name'];
}

// Get existing photos
$photos_query = "SELECT * FROM issue_photos WHERE issue_id = ?";
$photos_stmt = $conn->prepare($photos_query);
$photos_stmt->bind_param("i", $issue_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$photos = [];
while($photo = $photos_result->fetch_assoc()) {
    $photos[] = $photo;
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $electoral_area = trim($_POST['electoral_area']);
    $severity = trim($_POST['severity']);
    $people_affected = (int) $_POST['people_affected'];
    $additional_notes = trim($_POST['additional_notes']);
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($location) || empty($electoral_area) || empty($severity)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Update the issue in the database
        $update_query = "UPDATE issues SET 
                         title = ?, 
                         description = ?, 
                         location = ?, 
                         electoral_area = ?, 
                         severity = ?, 
                         people_affected = ?, 
                         additional_notes = ?,
                         updated_at = NOW()
                         WHERE id = ? AND officer_id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssisii", $title, $description, $location, $electoral_area, $severity, $people_affected, $additional_notes, $issue_id, $officer_id);
        
        if ($stmt->execute()) {
            // Handle photo deletions
            if (isset($_POST['delete_photo']) && is_array($_POST['delete_photo'])) {
                foreach ($_POST['delete_photo'] as $photo_id) {
                    // Get file path first
                    $get_photo = "SELECT file_path FROM issue_photos WHERE id = ? AND issue_id = ?";
                    $photo_stmt = $conn->prepare($get_photo);
                    $photo_stmt->bind_param("ii", $photo_id, $issue_id);
                    $photo_stmt->execute();
                    $photo_result = $photo_stmt->get_result();
                    
                    if ($photo_result->num_rows > 0) {
                        $photo_path = $photo_result->fetch_assoc()['file_path'];
                        
                        // Delete from database
                        $delete_photo = "DELETE FROM issue_photos WHERE id = ? AND issue_id = ?";
                        $delete_stmt = $conn->prepare($delete_photo);
                        $delete_stmt->bind_param("ii", $photo_id, $issue_id);
                        $delete_stmt->execute();
                        
                        // Delete file from server
                        if (file_exists($photo_path)) {
                            unlink($photo_path);
                        }
                    }
                }
            }
            
            // Handle new file uploads
            if (!empty($_FILES['photos']['name'][0])) {
                $upload_dir = 'uploads/issues/' . $issue_id . '/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Upload each file
                $total_files = count($_FILES['photos']['name']);
                for ($i = 0; $i < $total_files; $i++) {
                    if ($_FILES['photos']['error'][$i] === 0) {
                        $tmp_name = $_FILES['photos']['tmp_name'][$i];
                        $name = basename($_FILES['photos']['name'][$i]);
                        $file_path = $upload_dir . $name;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            // Insert file info into database
                            $insert_file = "INSERT INTO issue_photos (issue_id, file_path, uploaded_at) VALUES (?, ?, NOW())";
                            $file_stmt = $conn->prepare($insert_file);
                            $file_stmt->bind_param("is", $issue_id, $file_path);
                            $file_stmt->execute();
                        }
                    }
                }
            }
            
            $success_message = "Issue updated successfully!";
            
            // Refresh issue data
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $issue_id, $officer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $issue = $result->fetch_assoc();
            
            // Refresh photos
            $photos_stmt->execute();
            $photos_result = $photos_stmt->get_result();
            $photos = [];
            while($photo = $photos_result->fetch_assoc()) {
                $photos[] = $photo;
            }
        } else {
            $error_message = "Error updating issue: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Issue - Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <a href="issue-detail.php?id=<?php echo $issue_id; ?>"
                            class="text-blue-600 hover:text-blue-800 mr-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="text-2xl font-semibold text-gray-900">Edit Issue #<?php echo $issue_id; ?></h1>
                    </div>
                </div>
            </header>

            <!-- Form Content -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4">
                <div class="max-w-3xl mx-auto">
                    <?php if (!empty($success_message)): ?>
                    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md"
                        role="alert">
                        <p class="font-bold">Success!</p>
                        <p><?php echo $success_message; ?></p>
                        <div class="mt-3">
                            <a href="issue-detail.php?id=<?php echo $issue_id; ?>"
                                class="text-green-700 font-medium hover:underline">
                                View Issue Details
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                        <p class="font-bold">Error</p>
                        <p><?php echo $error_message; ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-6">
                            <h2 class="text-lg font-medium mb-6">Edit Issue Information</h2>

                            <form action="edit-issue.php?id=<?php echo $issue_id; ?>" method="POST"
                                enctype="multipart/form-data">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Title -->
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title
                                            <span class="text-red-500">*</span></label>
                                        <input type="text" name="title" id="title" required
                                            value="<?php echo htmlspecialchars($issue['title']); ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label for="description"
                                            class="block text-sm font-medium text-gray-700 mb-1">Description <span
                                                class="text-red-500">*</span></label>
                                        <textarea name="description" id="description" rows="4" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo htmlspecialchars($issue['description']); ?></textarea>
                                    </div>

                                    <!-- Location -->
                                    <div>
                                        <label for="location"
                                            class="block text-sm font-medium text-gray-700 mb-1">Location <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="location" id="location" required
                                            value="<?php echo htmlspecialchars($issue['location']); ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>

                                    <!-- Electoral Area -->
                                    <div>
                                        <label for="electoral_area"
                                            class="block text-sm font-medium text-gray-700 mb-1">Electoral Area <span
                                                class="text-red-500">*</span></label>
                                        <select name="electoral_area" id="electoral_area" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <?php foreach ($electoral_areas as $area): ?>
                                            <option value="<?php echo htmlspecialchars($area); ?>"
                                                <?php echo ($issue['electoral_area'] == $area) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($area); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Severity -->
                                    <div>
                                        <label for="severity"
                                            class="block text-sm font-medium text-gray-700 mb-1">Severity <span
                                                class="text-red-500">*</span></label>
                                        <select name="severity" id="severity" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="Critical"
                                                <?php echo ($issue['severity'] == 'Critical') ? 'selected' : ''; ?>>
                                                Critical</option>
                                            <option value="High"
                                                <?php echo ($issue['severity'] == 'High') ? 'selected' : ''; ?>>High
                                            </option>
                                            <option value="Medium"
                                                <?php echo ($issue['severity'] == 'Medium') ? 'selected' : ''; ?>>Medium
                                            </option>
                                            <option value="Low"
                                                <?php echo ($issue['severity'] == 'Low') ? 'selected' : ''; ?>>Low
                                            </option>
                                        </select>
                                    </div>

                                    <!-- People Affected -->
                                    <div>
                                        <label for="people_affected"
                                            class="block text-sm font-medium text-gray-700 mb-1">People Affected
                                            (estimated)</label>
                                        <input type="number" name="people_affected" id="people_affected" min="0"
                                            value="<?php echo (int)$issue['people_affected']; ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>

                                    <!-- Additional Notes -->
                                    <div>
                                        <label for="additional_notes"
                                            class="block text-sm font-medium text-gray-700 mb-1">Additional
                                            Notes</label>
                                        <textarea name="additional_notes" id="additional_notes" rows="3"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo htmlspecialchars($issue['additional_notes']); ?></textarea>
                                    </div>

                                    <!-- Current Photos -->
                                    <?php if(count($photos) > 0): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Current
                                            Photos</label>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                            <?php foreach($photos as $photo): ?>
                                            <div class="relative">
                                                <img src="<?php echo htmlspecialchars($photo['file_path']); ?>"
                                                    alt="Issue Photo" class="h-40 w-full object-cover rounded-lg">
                                                <div class="absolute top-0 right-0 p-2">
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="delete_photo[]"
                                                            value="<?php echo $photo['id']; ?>"
                                                            class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                                        <span
                                                            class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Delete</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-2">Check the box to delete photos.</p>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Upload New Photos -->
                                    <div>
                                        <label for="photos" class="block text-sm font-medium text-gray-700 mb-1">Upload
                                            New Photos</label>
                                        <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                        <p class="text-xs text-gray-500 mt-1">You can upload multiple photos. Accepted
                                            file types: JPG, PNG, GIF.</p>
                                    </div>

                                    <!-- Status Info -->
                                    <div class="bg-gray-50 p-4 rounded-md">
                                        <h3 class="text-sm font-medium text-gray-700 mb-2">Current Status Information
                                        </h3>
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php
                                                switch($issue['status']) {
                                                    case 'Pending':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'In Progress':
                                                        echo 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'Resolved':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'Closed':
                                                        echo 'bg-gray-100 text-gray-800';
                                                        break;
                                                    default:
                                                        echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo htmlspecialchars($issue['status']); ?>
                                            </span>
                                            <span class="ml-3 text-sm text-gray-500">Last updated:
                                                <?php echo date('M j, Y g:i A', strtotime($issue['updated_at'])); ?></span>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-600">
                                            Note: Status updates are managed by the PA or MCE. You'll be notified of any
                                            status changes.
                                        </p>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="mt-4 flex justify-end">
                                        <a href="issue-detail.php?id=<?php echo $issue_id; ?>"
                                            class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Cancel
                                        </a>
                                        <button type="submit"
                                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Update Issue
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Delete Issue Section -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-6">
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-red-600 mb-4">Danger Zone</h2>
                            <p class="text-sm text-gray-600 mb-4">
                                Deleting an issue will permanently remove it from the system. This action cannot be
                                undone.
                            </p>
                            <button type="button" onclick="confirmDelete(<?php echo $issue_id; ?>)"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Delete Issue
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 md:p-8 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this issue? This action cannot be undone and
                all related data will be permanently removed.</p>
            <div class="flex justify-end">
                <button type="button" onclick="closeDeleteModal()"
                    class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <form id="deleteForm" method="POST" action="delete-issue.php">
                    <input type="hidden" id="delete_issue_id" name="issue_id" value="">
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Delete Issue
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Delete confirmation modal functions
    function confirmDelete(issueId) {
        document.getElementById('delete_issue_id').value = issueId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        let modal = document.getElementById('deleteModal');
        if (event.target == modal) {
            closeDeleteModal();
        }
    }

    // Preview image uploads
    const photoInput = document.getElementById('photos');
    photoInput.addEventListener('change', function() {
        // You could add code here to preview images before upload
        // For simplicity, we're just showing how many files were selected
        const fileCount = this.files.length;
        if (fileCount > 0) {
            this.nextElementSibling.textContent =
                `${fileCount} file${fileCount !== 1 ? 's' : ''} selected for upload`;
        } else {
            this.nextElementSibling.textContent =
                'You can upload multiple photos. Accepted file types: JPG, PNG, GIF.';
        }
    });
    </script>
</body>

</html>