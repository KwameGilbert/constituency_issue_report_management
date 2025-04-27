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
$pageTitle = 'Edit Issue';
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

// Get electoral areas for dropdown
$areas_query = "SELECT id, name FROM electoral_areas ORDER BY name";
$areas_result = $conn->query($areas_query);
$electoral_areas = [];
while($area = $areas_result->fetch_assoc()) {
    $electoral_areas[] = $area;
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
    $electoral_area_id = (int) $_POST['electoral_area_id'];
    $severity = trim($_POST['severity']);
    $people_affected = (int) $_POST['people_affected'];
    $additional_notes = trim($_POST['additional_notes']);
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($location) || empty($severity)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Update the issue in the database
        $update_query = "UPDATE issues SET 
                         title = ?, 
                         description = ?, 
                         location = ?, 
                         electoral_area_id = ?, 
                         severity = ?, 
                         people_affected = ?, 
                         additional_notes = ?,
                         updated_at = NOW()
                         WHERE id = ? AND officer_id = ?";
        
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            $error_message = "Prepare statement failed: " . $conn->error;
        } else {
            // Debug information
            echo "<!-- Debug: Binding parameters with title=$title, description=..., location=$location, electoral_area_id=$electoral_area_id, severity=$severity, people_affected=$people_affected, additional_notes=..., issue_id=$issue_id, officer_id=$officer_id -->";
            
            if (!$stmt->bind_param("sssissiis", $title, $description, $location, $electoral_area_id, $severity, $people_affected, $additional_notes, $issue_id, $officer_id)) {
                $error_message = "Binding parameters failed: " . $stmt->error;
            } else if (!$stmt->execute()) {
                $error_message = "Execute failed: " . $stmt->error;
            } else {
                // Handle photo deletions
                if (isset($_POST['delete_photo']) && is_array($_POST['delete_photo'])) {
                    foreach ($_POST['delete_photo'] as $photo_id) {
                        // Get file path first
                        $get_photo = "SELECT photo_url FROM issue_photos WHERE id = ? AND issue_id = ?";
                        $photo_stmt = $conn->prepare($get_photo);
                        $photo_stmt->bind_param("ii", $photo_id, $issue_id);
                        $photo_stmt->execute();
                        $photo_result = $photo_stmt->get_result();
                        
                        if ($photo_result->num_rows > 0) {
                            $photo_path = $photo_result->fetch_assoc()['photo_url'];
                            
                            // Delete from database
                            $delete_photo = "DELETE FROM issue_photos WHERE id = ? AND issue_id = ?";
                            $delete_stmt = $conn->prepare($delete_photo);
                            $delete_stmt->bind_param("ii", $photo_id, $issue_id);
                            $delete_stmt->execute();
                            
                            // Delete file from server if it exists
                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $photo_path)) {
                                unlink($_SERVER['DOCUMENT_ROOT'] . $photo_path);
                            }
                        }
                    }
                }
                
                // Handle new file uploads
                if (!empty($_FILES['photos']['name'][0])) {
                    $upload_dir = '../../../uploads/issues/' . $issue_id . '/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Upload each file
                    $total_files = count($_FILES['photos']['name']);
                    for ($i = 0; $i < $total_files; $i++) {
                        if ($_FILES['photos']['error'][$i] === 0) {
                            $tmp_name = $_FILES['photos']['tmp_name'][$i];
                            $name = time() . '_' . basename($_FILES['photos']['name'][$i]);
                            $file_path = $upload_dir . $name;
                            $db_path = '/uploads/issues/' . $issue_id . '/' . $name;
                            
                            if (move_uploaded_file($tmp_name, $file_path)) {
                                // Insert file info into database
                                $insert_file = "INSERT INTO issue_photos (issue_id, photo_url, uploaded_at) VALUES (?, ?, NOW())";
                                $file_stmt = $conn->prepare($insert_file);
                                $file_stmt->bind_param("is", $issue_id, $db_path);
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
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Issue | Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6">
                <div class="max-w-5xl mx-auto">
                    <div class="flex items-center mb-6">
                        <a href="../issue-detail/?id=<?php echo $issue_id; ?>"
                            class="text-amber-600 hover:text-amber-800 mr-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="text-2xl font-semibold text-gray-900">Edit Issue #<?php echo $issue_id; ?></h1>
                    </div>

                    <?php if (!empty($success_message)): ?>
                    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md fade-in"
                        role="alert">
                        <p class="font-bold">Success!</p>
                        <p><?php echo $success_message; ?></p>
                        <div class="mt-3">
                            <a href="../issue-detail/?id=<?php echo $issue_id; ?>"
                                class="text-green-700 font-medium hover:underline">
                                View Issue Details
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md fade-in"
                        role="alert">
                        <p class="font-bold">Error</p>
                        <p><?php echo $error_message; ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-lg shadow-sm overflow-hidden fade-in">
                        <div class="p-6">
                            <h2 class="text-lg font-medium mb-6 text-gray-800">Edit Issue Information</h2>

                            <form action="index.php?id=<?php echo $issue_id; ?>" method="POST"
                                enctype="multipart/form-data">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Title -->
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title
                                            <span class="text-red-500">*</span></label>
                                        <input type="text" name="title" id="title" required
                                            value="<?php echo htmlspecialchars($issue['title']); ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label for="description"
                                            class="block text-sm font-medium text-gray-700 mb-1">Description <span
                                                class="text-red-500">*</span></label>
                                        <textarea name="description" id="description" rows="4" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"><?php echo htmlspecialchars($issue['description']); ?></textarea>
                                    </div>

                                    <!-- Location -->
                                    <div>
                                        <label for="location"
                                            class="block text-sm font-medium text-gray-700 mb-1">Location <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="location" id="location" required
                                            value="<?php echo htmlspecialchars($issue['location']); ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    </div>

                                    <!-- Electoral Area -->
                                    <div>
                                        <label for="electoral_area_id"
                                            class="block text-sm font-medium text-gray-700 mb-1">Electoral Area <span
                                                class="text-red-500">*</span></label>
                                        <select name="electoral_area_id" id="electoral_area_id" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                            <option value="">Select Electoral Area</option>
                                            <?php foreach ($electoral_areas as $area): ?>
                                            <option value="<?php echo $area['id']; ?>"
                                                <?php echo ($issue['electoral_area_id'] == $area['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($area['name']); ?>
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
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                            <option value="critical"
                                                <?php echo ($issue['severity'] == 'critical') ? 'selected' : ''; ?>>
                                                Critical</option>
                                            <option value="high"
                                                <?php echo ($issue['severity'] == 'high') ? 'selected' : ''; ?>>High
                                            </option>
                                            <option value="medium"
                                                <?php echo ($issue['severity'] == 'medium') ? 'selected' : ''; ?>>Medium
                                            </option>
                                            <option value="low"
                                                <?php echo ($issue['severity'] == 'low') ? 'selected' : ''; ?>>Low
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
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    </div>

                                    <!-- Additional Notes -->
                                    <div>
                                        <label for="additional_notes"
                                            class="block text-sm font-medium text-gray-700 mb-1">Additional
                                            Notes</label>
                                        <textarea name="additional_notes" id="additional_notes" rows="3"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"><?php echo htmlspecialchars($issue['additional_notes']); ?></textarea>
                                    </div>

                                    <!-- Current Photos -->
                                    <?php if(count($photos) > 0): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Current
                                            Photos</label>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                            <?php foreach($photos as $photo): ?>
                                            <div class="relative">
                                                <img src="<?php echo htmlspecialchars($photo['photo_url']); ?>"
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
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
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
                                                    case 'pending':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'under_review':
                                                        echo 'bg-purple-100 text-purple-800';
                                                        break;
                                                    case 'in_progress':
                                                        echo 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'resolved':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'rejected':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                    default:
                                                        echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
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
                                        <a href="../issue-detail/?id=<?php echo $issue_id; ?>"
                                            class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            Cancel
                                        </a>
                                        <button type="submit"
                                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            Update Issue
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Delete Issue Section -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-6 fade-in"
                        style="animation-delay: 0.1s;">
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-red-600 mb-4">Danger Zone</h2>
                            <p class="text-sm text-gray-600 mb-4">
                                Deleting an issue will permanently remove it from the system. This action cannot be
                                undone.
                            </p>
                            <button type="button"
                                onclick="confirmDelete(<?php echo $issue_id; ?>, '<?php echo addslashes($issue['title']); ?>')"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Delete Issue
                            </button>
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
                                <p class="text-sm text-gray-500" id="delete-message">
                                    Are you sure you want to delete this issue? All data related to this issue will be
                                    permanently removed. This action cannot be undone.
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

    // Delete confirmation modal functions
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

    // Preview image uploads
    const photoInput = document.getElementById('photos');
    photoInput.addEventListener('change', function() {
        // Show how many files were selected
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