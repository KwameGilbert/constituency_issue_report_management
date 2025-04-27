<?php
// create-issue.php - Form for field officers to report new issues
session_start();

// Check if user is logged in and is a field officer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: login.php");
    exit();
}

require_once 'includes/db_connect.php';

// Get electoral areas for dropdown
$areas_query = "SELECT DISTINCT name FROM electoral_areas ORDER BY name";
$areas_result = $conn->query($areas_query);
$electoral_areas = [];
while($area = $areas_result->fetch_assoc()) {
    $electoral_areas[] = $area['name'];
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
    $officer_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($location) || empty($electoral_area) || empty($severity)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert the issue into database
        $insert_query = "INSERT INTO issues (title, description, location, electoral_area, severity, people_affected, additional_notes, officer_id, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssssisi", $title, $description, $location, $electoral_area, $severity, $people_affected, $additional_notes, $officer_id);
        
        if ($stmt->execute()) {
            $issue_id = $stmt->insert_id;
            
            // Handle file uploads
            if (!empty($_FILES['photos']['name'][0])) {
                $upload_dir = 'uploads/issues/' . $issue_id . '/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Upload each file
                $total_files = count($_FILES['photos']['name']);
                for ($i = 0; $i < $total_files; $i++) {
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
            
            $success_message = "Issue reported successfully! Issue ID: #" . $issue_id;
            
            // Clear form data after successful submission
            $title = $description = $location = $electoral_area = $severity = $additional_notes = '';
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
    <title>Report New Issue - Field Officer Dashboard</title>
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
                    <h1 class="text-2xl font-semibold text-gray-900">Report New Issue</h1>
                    <a href="issues.php" class="text-blue-600 hover:text-blue-800 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Issues
                    </a>
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
                            <a href="issues.php" class="text-green-700 font-medium hover:underline">View All Issues</a>
                            <span class="mx-2">|</span>
                            <a href="create-issue.php" class="text-green-700 font-medium hover:underline">Report Another
                                Issue</a>
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
                            <h2 class="text-lg font-medium mb-6">Issue Information</h2>

                            <form action="create-issue.php" method="POST" enctype="multipart/form-data">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Title -->
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title
                                            <span class="text-red-500">*</span></label>
                                        <input type="text" name="title" id="title" required
                                            value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">Provide a clear, concise title for the
                                            issue.</p>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label for="description"
                                            class="block text-sm font-medium text-gray-700 mb-1">Description <span
                                                class="text-red-500">*</span></label>
                                        <textarea name="description" id="description" rows="4" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
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
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">Specify the exact location of the issue
                                            (e.g., street address, landmark).</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Electoral Area -->
                                        <div>
                                            <label for="electoral_area"
                                                class="block text-sm font-medium text-gray-700 mb-1">Electoral Area
                                                <span class="text-red-500">*</span></label>
                                            <select name="electoral_area" id="electoral_area" required
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Select Electoral Area</option>
                                                <?php foreach ($electoral_areas as $area): ?>
                                                <option value="<?php echo htmlspecialchars($area); ?>"
                                                    <?php echo (isset($electoral_area) && $electoral_area === $area) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($area); ?>
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
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>

                                    <!-- Photo Upload -->
                                    <div>
                                        <label for="photos" class="block text-sm font-medium text-gray-700 mb-1">Photo
                                            Evidence</label>
                                        <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                        <p class="mt-1 text-sm text-gray-500">Upload up to 5 photos (optional). Accepted
                                            formats: JPG, PNG.</p>

                                        <!-- Preview area for selected images -->
                                        <div id="image-preview" class="mt-3 grid grid-cols-5 gap-2"></div>
                                    </div>

                                    <!-- Additional Notes -->
                                    <div>
                                        <label for="additional_notes"
                                            class="block text-sm font-medium text-gray-700 mb-1">Additional
                                            Notes</label>
                                        <textarea name="additional_notes" id="additional_notes" rows="3"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo isset($additional_notes) ? htmlspecialchars($additional_notes) : ''; ?></textarea>
                                        <p class="mt-1 text-sm text-gray-500">Any other relevant information about the
                                            issue.</p>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="pt-4">
                                        <button type="submit"
                                            class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md text-sm font-medium flex items-center justify-center">
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