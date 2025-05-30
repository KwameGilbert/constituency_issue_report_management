<?php
// create-issue.php - Form for field officers to report new issues
session_start();

// Check if user is logged in and is a field officer
if(!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Get electoral areas for dropdown
$areas_query = "SELECT * FROM electoral_areas ORDER BY name";
$areas_result = $conn->query($areas_query);
$electoral_areas = [];
while($area = $areas_result->fetch_assoc()) {
    $electoral_areas[] = $area;
}

$sup_query = "SELECT * FROM supervisors ORDER BY position";
$sup_results = $conn->query($sup_query);
$sups = [];
while($sup = $sup_results->fetch_assoc()){
    $sups[] = $sup;
}

// Set active page for sidebar
$active_page = 'create-issue';
$pageTitle = 'Report New Issue';
$basePath = '../';

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
    $officer_id = $_SESSION['officer_id'];
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($location) || empty($severity)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert the issue into database
        $insert_query = "INSERT INTO issues (title, description, location, electoral_area_id, severity, people_affected, additional_notes, officer_id, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssissii", $title, $description, $location, $electoral_area_id, $severity, $people_affected, $additional_notes, $officer_id);
        
        if ($stmt->execute()) {
            $issue_id = $stmt->insert_id;
            
            // Handle file uploads
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
            
            $success_message = "Issue reported successfully! Issue ID: #" . $issue_id;
            
            // Clear form data after successful submission
            $title = $description = $location = $electoral_area_id = $severity = $additional_notes = '';
            $people_affected = 0;
        } else {
            $error_message = "Error reporting issue: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report New Issue | Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/main.css">
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

    /* Custom form input styling for better visibility */
    input[type="text"],
    input[type="number"],
    textarea,
    select {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        width: 100%;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    textarea:focus,
    select:focus {
        outline: none;
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    }

    input:hover,
    textarea:hover,
    select:hover {
        border-color: #f59e0b !important;
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
                    <!-- Action Bar -->
                    <div class="bg-amber-600 rounded-xl shadow-lg mb-6 p-6 text-white fade-in">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <h1 class="text-2xl font-bold">Report New Issue</h1>
                                <p class="mt-1 opacity-90">Submit details about a constituency issue</p>
                            </div>
                            <a href="../issues/"
                                class="inline-flex items-center px-4 py-2 bg-white text-amber-800 rounded-lg font-medium shadow hover:bg-amber-50 transition-colors duration-300">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Issues
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($success_message)): ?>
                    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md fade-in"
                        role="alert">
                        <p class="font-bold">Success!</p>
                        <p><?php echo $success_message; ?></p>
                        <div class="mt-3">
                            <a href="../issues/" class="text-green-700 font-medium hover:underline">View All Issues</a>
                            <span class="mx-2">|</span>
                            <a href="../issue-detail/?id=<?php echo $issue_id; ?>"
                                class="text-green-700 font-medium hover:underline">
                                View Issue Details
                            </a>
                            <span class="mx-2">|</span>
                            <a href="../create-issue/" class="text-green-700 font-medium hover:underline">Report Another
                                Issue</a>
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
                            <h2 class="text-lg font-medium mb-6 text-gray-800">Issue Information</h2>

                            <form action="index.php" method="POST" enctype="multipart/form-data">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Title -->
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title
                                            <span class="text-red-500">*</span></label>
                                        <input type="text" name="title" id="title" required
                                            value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                        <p class="mt-1 text-sm text-gray-500">Provide a clear, concise title for the
                                            issue.</p>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label for="description"
                                            class="block text-sm font-medium text-gray-700 mb-1">Description <span
                                                class="text-red-500">*</span></label>
                                        <textarea name="description" id="description" rows="4" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                        <p class="mt-1 text-sm text-gray-500">Provide detailed information about the
                                            issue.</p>
                                    </div>

                                    <!-- Location -->
                                    <div>
                                        <label for="location"
                                            class="block text-sm font-medium text-gray-700 mb-1">Location <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="location" id="location" required
                                            value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                        <p class="mt-1 text-sm text-gray-500">Specify the exact location of the issue
                                            (e.g., street address, landmark).</p>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Electoral Area -->
                                        <div>
                                            <label for="electoral_area_id"
                                                class="block text-sm font-medium text-gray-700 mb-1">Electoral Area
                                                <span class="text-red-500">*</span></label>
                                            <select name="electoral_area_id" id="electoral_area_id" required
                                                class="h-11 w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                                <option value="">Select Electoral Area</option>
                                                <?php foreach ($electoral_areas as $area): ?>
                                                <option value="<?php echo $area['id']; ?>"
                                                    <?php echo (isset($electoral_area_id) && $electoral_area_id == $area['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($area['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Severity -->
                                        <div>
                                            <label for="severity"
                                                class="block text-sm font-medium text-gray-700 mb-1">Severity Level
                                                <span class="text-red-500">*</span></label>
                                            <select name="severity" id="severity" required
                                                class="h-11 w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                                <option value="">Select Severity</option>
                                                <option value="critical"
                                                    <?php echo (isset($severity) && $severity === 'critical') ? 'selected' : ''; ?>>
                                                    Critical</option>
                                                <option value="high"
                                                    <?php echo (isset($severity) && $severity === 'high') ? 'selected' : ''; ?>>
                                                    High</option>
                                                <option value="medium"
                                                    <?php echo (isset($severity) && $severity === 'medium') ? 'selected' : ''; ?>>
                                                    Medium</option>
                                                <option value="low"
                                                    <?php echo (isset($severity) && $severity === 'low') ? 'selected' : ''; ?>>
                                                    Low</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- People Affected -->
                                    <div>
                                        <label for="people_affected"
                                            class="block text-sm font-medium text-gray-700 mb-1">Estimated People
                                            Affected</label>
                                        <input type="number" name="people_affected" id="people_affected" min="0"
                                            value="<?php echo isset($people_affected) ? $people_affected : ''; ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    </div>

                                    <!-- Supervisor -->
                                    <div>
                                        <label for="supervisor">Supervisor</label>
                                        <select name="supervisor" id="supervisor" required
                                            class="h-11 w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                            <option value="">Select Supervisor</option>
                                            <?php foreach ($sups as $sup): ?>
                                            <option value="<?php echo $sup['id']; ?>"
                                                <?php echo (isset($supervisor_id) && $supervisor_id == $sup['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sup['position']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Photo Upload -->
                                    <div>
                                        <label for="photos" class="block text-sm font-medium text-gray-700 mb-1">Photo
                                            Evidence</label>
                                        <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                                        <p class="mt-1 text-sm text-gray-500">Upload up to 5 photos (optional). Accepted
                                            formats: JPG, PNG.</p>

                                        <!-- Preview area for selected images -->
                                        <div id="image-preview" class="mt-3 grid grid-cols-2 md:grid-cols-5 gap-2">
                                        </div>
                                    </div>

                                    <!-- Additional Notes -->
                                    <div>
                                        <label for="additional_notes"
                                            class="block text-sm font-medium text-gray-700 mb-1">Additional
                                            Notes</label>
                                        <textarea name="additional_notes" id="additional_notes" rows="3"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"><?php echo isset($additional_notes) ? htmlspecialchars($additional_notes) : ''; ?></textarea>
                                        <p class="mt-1 text-sm text-gray-500">Any other relevant information about the
                                            issue.</p>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="pt-4 flex justify-end">
                                        <a href="../issues/"
                                            class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            Cancel
                                        </a>
                                        <button type="submit"
                                            class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 flex items-center">
                                            <i class="fas fa-paper-plane mr-2"></i> Submit Issue
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
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

    // Image preview functionality
    document.getElementById('photos').addEventListener('change', function(event) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';

        if (this.files) {
            const files = Array.from(this.files).slice(0, 5); // Limit to 5 files

            files.forEach(file => {
                if (!file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'h-24 w-full object-cover rounded-md';
                    div.appendChild(img);

                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }
    });
    </script>
</body>

</html>