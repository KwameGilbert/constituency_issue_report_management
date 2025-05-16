<?php
session_start();

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit;
}

require_once '../../../config/db.php';

// Get PA ID from session
$pa_id = $_SESSION['pa_id'];

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: index.php");
    exit;
}

$project_id = intval($_GET['id']);

// Verify that this PA owns the project (security check)
$check_query = "SELECT * FROM projects WHERE id = ? AND pa_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $project_id, $pa_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "You don't have permission to edit this project or the project doesn't exist.";
    header("Location: index.php");
    exit;
}

$project = $result->fetch_assoc();

// Get electoral areas for dropdown
$electoral_areas_query = "SELECT id, name FROM electoral_areas ORDER BY name";
$electoral_areas_result = $conn->query($electoral_areas_query);

// Decode images JSON
$images = [];
if (!empty($project['images'])) {
    $images = json_decode($project['images'], true) ?: [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $electoral_area_id = !empty($_POST['electoral_area_id']) ? intval($_POST['electoral_area_id']) : null;
    $location = trim($_POST['location']);
    $sector = trim($_POST['sector']);
    $people_benefitted = !empty($_POST['people_benefitted']) ? intval($_POST['people_benefitted']) : null;
    $budget_allocation = !empty($_POST['budget_allocation']) ? floatval($_POST['budget_allocation']) : null;
    $status = $_POST['status'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $progress = isset($_POST['progress']) ? intval($_POST['progress']) : 0;
    
    // Handle removing existing images
    $removed_images = isset($_POST['removed_images']) ? explode(',', $_POST['removed_images']) : [];
    
    if (!empty($removed_images) && !empty($images)) {
        foreach ($removed_images as $index) {
            if (isset($images[$index])) {
                // Get the file path to delete
                $file_path = "../../../" . ltrim($images[$index], '/');
                if (file_exists($file_path) && is_file($file_path)) {
                    unlink($file_path);
                }
                // Remove from array
                unset($images[$index]);
            }
        }
        // Reindex array
        $images = array_values($images);
    }
    
    // Generate slug from title (only if title changed)
    if ($title !== $project['title']) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    } else {
        $slug = $project['slug'];
    }
    
    // Validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Project title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Project description is required";
    }
    
    if (empty($location)) {
        $errors[] = "Project location is required";
    }
    
    if (empty($sector)) {
        $errors[] = "Project sector is required";
    }
    
    if (empty($start_date)) {
        $errors[] = "Start date is required";
    }
    
    // If end date is provided, ensure it's after start date
    if (!empty($end_date) && !empty($start_date) && strtotime($end_date) <= strtotime($start_date)) {
        $errors[] = "End date must be after start date";
    }
    
    // Handle new image uploads
    if (!empty($_FILES['project_images']['name'][0])) {
        $upload_dir = '../../../uploads/projects/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        foreach ($_FILES['project_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['project_images']['error'][$key] === 0) {
                $file_name = $_FILES['project_images']['name'][$key];
                $file_tmp = $_FILES['project_images']['tmp_name'][$key];
                $file_type = $_FILES['project_images']['type'][$key];
                $file_size = $_FILES['project_images']['size'][$key];
                
                // Validate file type and size
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "File type not allowed for: $file_name. Only JPG, JPEG and PNG are accepted.";
                    continue;
                }
                
                if ($file_size > $max_size) {
                    $errors[] = "File size exceeds 5MB limit for: $file_name";
                    continue;
                }
                
                // Generate unique filename
                $new_file_name = time() . '_' . uniqid() . '_' . $file_name;
                $file_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $file_path)) {
                    $images[] = '/uploads/projects/' . $new_file_name;
                } else {
                    $errors[] = "Failed to upload file: $file_name";
                }
            }
        }
    }
    
    // Convert images array to JSON for storage
    $images_json = !empty($images) ? json_encode(array_values($images)) : null;
    
    if (empty($errors)) {
        // Update project in database
        $query = "UPDATE projects SET 
                  title = ?, 
                  description = ?, 
                  electoral_area_id = ?, 
                  location = ?, 
                  sector = ?, 
                  people_benefitted = ?, 
                  budget_allocation = ?, 
                  images = ?, 
                  status = ?, 
                  featured = ?, 
                  start_date = ?, 
                  end_date = ?, 
                  progress = ?, 
                  slug = ?, 
                  updated_at = NOW() 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        // Bind parameters
        $stmt->bind_param(
            'ssissidssissisi',
            $title,             // s
            $description,       // s
            $electoral_area_id, // i
            $location,          // s
            $sector,            // s
            $people_benefitted, // i
            $budget_allocation, // d
            $images_json,       // s
            $status,            // s
            $featured,          // i
            $start_date,        // s
            $end_date,          // s
            $progress,          // i
            $slug,              // s
            $project_id         // i
        );
        
        if ($stmt->execute()) {
            // Log the activity
            if (function_exists('log_activity')) {
                log_activity($pa_id, 'pa', 'update', 'project', $project_id, $title);
            }
            
            // Set success message and redirect
            $_SESSION['success'] = "Project has been updated successfully.";
            header("Location: view.php?id=$project_id");
            exit;
        } else {
            $errors[] = "Error updating project: " . $conn->error;
        }
    }
}

$page_title = "Edit Project - PA Portal";
include '../includes/header.php';
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <!-- Breadcrumb -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="../dashboard/"
                        class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-green-600">
                        <i class="fas fa-home mr-2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2 text-sm"></i>
                        <a href="index.php" class="text-sm font-medium text-gray-700 hover:text-green-600">
                            Projects
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2 text-sm"></i>
                        <a href="view.php?id=<?= $project_id ?>"
                            class="text-sm font-medium text-gray-700 hover:text-green-600">
                            <?= htmlspecialchars($project['title']) ?>
                        </a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2 text-sm"></i>
                        <span class="text-sm font-medium text-gray-500">Edit Project</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Edit Project</h1>
            <p class="mt-1 text-gray-600">Update the details of your project</p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-md p-4">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-0.5"></i>
                <div>
                    <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Project Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-800">Project Details</h2>
            </div>
            <form method="post" action="" class="p-6" enctype="multipart/form-data" id="project-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Project Title -->
                    <div class="col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Project Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            value="<?= htmlspecialchars($project['title']) ?>" required>
                        <p class="mt-1 text-xs text-gray-500">Choose a clear, descriptive title for your project</p>
                    </div>

                    <!-- Project Description -->
                    <div class="col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Project Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" id="description" rows="6"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            required><?= htmlspecialchars($project['description']) ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Provide a detailed description of the project, its goals,
                            and impact</p>
                    </div>

                    <!-- Electoral Area -->
                    <div>
                        <label for="electoral_area_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Electoral Area
                        </label>
                        <select name="electoral_area_id" id="electoral_area_id"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                            <option value="">-- Select Electoral Area --</option>
                            <?php 
                            // Reset result pointer
                            $electoral_areas_result->data_seek(0);
                            while ($area = $electoral_areas_result->fetch_assoc()): 
                            ?>
                            <option value="<?= $area['id'] ?>"
                                <?= ($project['electoral_area_id'] == $area['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($area['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">
                            Location <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="location" id="location"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            value="<?= htmlspecialchars($project['location']) ?>" required>
                        <p class="mt-1 text-xs text-gray-500">Specific location where the project is implemented</p>
                    </div>

                    <!-- Sector -->
                    <div>
                        <label for="sector" class="block text-sm font-medium text-gray-700 mb-1">
                            Sector <span class="text-red-500">*</span>
                        </label>
                        <select name="sector" id="sector"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            required>
                            <option value="">-- Select Sector --</option>
                            <option value="Education" <?= ($project['sector'] == 'Education') ? 'selected' : '' ?>>
                                Education</option>
                            <option value="Health" <?= ($project['sector'] == 'Health') ? 'selected' : '' ?>>
                                Health</option>
                            <option value="Infrastructure"
                                <?= ($project['sector'] == 'Infrastructure') ? 'selected' : '' ?>>
                                Infrastructure</option>
                            <option value="Water & Sanitation"
                                <?= ($project['sector'] == 'Water & Sanitation') ? 'selected' : '' ?>>
                                Water & Sanitation</option>
                            <option value="Agriculture" <?= ($project['sector'] == 'Agriculture') ? 'selected' : '' ?>>
                                Agriculture</option>
                            <option value="Energy" <?= ($project['sector'] == 'Energy') ? 'selected' : '' ?>>
                                Energy</option>
                            <option value="Social Protection"
                                <?= ($project['sector'] == 'Social Protection') ? 'selected' : '' ?>>
                                Social Protection</option>
                            <option value="Security" <?= ($project['sector'] == 'Security') ? 'selected' : '' ?>>
                                Security</option>
                            <option value="Other" <?= ($project['sector'] == 'Other') ? 'selected' : '' ?>>
                                Other</option>
                        </select>
                    </div>

                    <!-- People Benefitted -->
                    <div>
                        <label for="people_benefitted" class="block text-sm font-medium text-gray-700 mb-1">
                            People Benefitted
                        </label>
                        <input type="number" name="people_benefitted" id="people_benefitted" min="0"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            value="<?= htmlspecialchars($project['people_benefitted'] ?? '') ?>">
                        <p class="mt-1 text-xs text-gray-500">Estimated number of people who will benefit from this
                            project</p>
                    </div>

                    <!-- Budget Allocation -->
                    <div>
                        <label for="budget_allocation" class="block text-sm font-medium text-gray-700 mb-1">
                            Budget Allocation (GHS)
                        </label>
                        <input type="number" name="budget_allocation" id="budget_allocation" min="0" step="0.01"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            value="<?= htmlspecialchars($project['budget_allocation'] ?? '') ?>">
                        <p class="mt-1 text-xs text-gray-500">Total budget allocated for this project</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            required>
                            <option value="planned" <?= ($project['status'] == 'planned') ? 'selected' : '' ?>>
                                Planned</option>
                            <option value="ongoing" <?= ($project['status'] == 'ongoing') ? 'selected' : '' ?>>
                                Ongoing</option>
                            <option value="completed" <?= ($project['status'] == 'completed') ? 'selected' : '' ?>>
                                Completed</option>
                        </select>
                    </div>

                    <!-- Progress -->
                    <div>
                        <label for="progress" class="block text-sm font-medium text-gray-700 mb-1">
                            Progress (%)
                        </label>
                        <input type="range" name="progress" id="progress" min="0" max="100"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                            value="<?= htmlspecialchars($project['progress']) ?>"
                            oninput="document.getElementById('progress_value').textContent = this.value">
                        <p class="mt-1 text-xs text-gray-500">
                            Project completion: <span
                                id="progress_value"><?= htmlspecialchars($project['progress']) ?></span>%
                        </p>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Start Date <span class="text-red-500">*</span>
                            <?= !empty($project['start_date']) ? date('Y-m-d', strtotime($project['start_date'])) : '' ?>
                        </label>
                        <input type="date" name="start_date" id="start_date"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            value="<?= !empty($project['start_date']) ? date('Y-m-d', strtotime($project['start_date'])) : '' ?>"
                            required>
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
                            End Date
                        </label>
                        <input type="date" name="end_date" id="end_date"
                            class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            value="<?= !empty($project['end_date']) ? date('Y-m-d', strtotime($project['end_date'])) : '' ?>"
                            placeholder="Optional">
                        <p class="mt-1 text-xs text-gray-500">Leave blank if the project end date is not determined yet
                        </p>
                    </div>

                    <!-- Current Project Images -->
                    <?php if (!empty($images)): ?>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Current Project Images
                        </label>
                        <input type="hidden" name="removed_images" id="removed_images" value="">

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <?php foreach ($images as $index => $image): ?>
                            <div class="relative group border border-gray-300 rounded-lg overflow-hidden"
                                id="image-container-<?= $index ?>">
                                <div class="aspect-w-1 aspect-h-1">
                                    <img src="<?= htmlspecialchars($image) ?>" alt="Project Image"
                                        class="w-full h-40 object-cover">
                                </div>
                                <button type="button"
                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                    onclick="markImageForRemoval(<?= $index ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div id="remove-overlay-<?= $index ?>"
                                    class="absolute inset-0 bg-red-500 bg-opacity-30 flex items-center justify-center hidden">
                                    <span class="text-white font-medium">Marked for removal</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Upload New Images -->
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Upload New Images
                        </label>
                        <div id="dropzone"
                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md transition-colors duration-200">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                    viewBox="0 0 48 48" aria-hidden="true">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="project_images"
                                        class="relative cursor-pointer rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500 mr-2">
                                        <span>Upload images</span>
                                        <input id="project_images" name="project_images[]" type="file" class="sr-only"
                                            multiple accept="image/jpeg,image/png,image/jpg">
                                    </label>
                                    <p class="">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    PNG, JPG, JPEG up to 5MB each
                                </p>
                            </div>
                        </div>

                        <!-- Image Preview Section -->
                        <div id="image-preview-container" class="mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2 hidden" id="selected-images-title">
                                New Images to Upload</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"
                                id="preview-grid">
                                <!-- Image previews will be dynamically added here -->
                            </div>
                        </div>
                    </div>

                    <!-- Featured Project -->
                    <div class="col-span-2">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="featured" id="featured"
                                    class="h-4 w-4 text-green-600 border border-gray-300 rounded focus:ring-green-500"
                                    <?= ($project['featured'] == 1) ? 'checked' : '' ?>>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="featured" class="font-medium text-gray-700">Featured Project</label>
                                <p class="text-gray-500">Feature this project on the homepage for increased visibility
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="view.php?id=<?= $project_id ?>"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set initial progress value display
    const progress = document.getElementById('progress');
    document.getElementById('progress_value').textContent = progress.value;

    // If status changes to completed, set progress to 100
    document.getElementById('status').addEventListener('change', function() {
        if (this.value === 'completed') {
            document.getElementById('progress').value = 100;
            document.getElementById('progress_value').textContent = 100;
        } else if (this.value === 'planned' && document.getElementById('progress').value > 0) {
            document.getElementById('progress').value = 0;
            document.getElementById('progress_value').textContent = 0;
        }
    });

    // Track removed images
    window.markImageForRemoval = function(index) {
        const removedImagesInput = document.getElementById('removed_images');
        let removedIndices = removedImagesInput.value ? removedImagesInput.value.split(',') : [];

        // Check if this index is already marked for removal
        const indexPosition = removedIndices.indexOf(index.toString());

        if (indexPosition === -1) {
            // Mark for removal
            removedIndices.push(index);
            document.getElementById(`remove-overlay-${index}`).classList.remove('hidden');
        } else {
            // Unmark for removal
            removedIndices.splice(indexPosition, 1);
            document.getElementById(`remove-overlay-${index}`).classList.add('hidden');
        }

        // Update the hidden input with the new list of indices
        removedImagesInput.value = removedIndices.join(',');
    };

    // Image preview functionality with file removal capability
    const imageInput = document.getElementById('project_images');
    const previewContainer = document.getElementById('preview-grid');
    const selectedImagesTitle = document.getElementById('selected-images-title');
    const dropZone = document.getElementById('dropzone');
    let fileList = new DataTransfer(); // To manage the FileList object

    function updateImagePreviews() {
        previewContainer.innerHTML = '';

        if (fileList.files.length > 0) {
            selectedImagesTitle.classList.remove('hidden');

            Array.from(fileList.files).forEach((file, index) => {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.className =
                        'relative group bg-gray-50 rounded-lg border border-gray-300 overflow-hidden';

                    const imgWrapper = document.createElement('div');
                    imgWrapper.className = 'aspect-w-1 aspect-h-1';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-40 object-cover';
                    img.alt = file.name;

                    const nameOverlay = document.createElement('div');
                    nameOverlay.className =
                        'absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 truncate';
                    nameOverlay.textContent = file.name;

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className =
                        'absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.addEventListener('click', function() {
                        // Remove file from DataTransfer object
                        const newFileList = new DataTransfer();
                        Array.from(fileList.files)
                            .filter((_, i) => i !== index)
                            .forEach(file => newFileList.items.add(file));

                        fileList = newFileList;
                        imageInput.files = fileList.files;

                        // Update previews
                        updateImagePreviews();
                    });

                    imgContainer.appendChild(imgWrapper);
                    imgWrapper.appendChild(img);
                    imgContainer.appendChild(nameOverlay);
                    imgContainer.appendChild(removeBtn);
                    previewContainer.appendChild(imgContainer);
                };

                reader.readAsDataURL(file);
            });
        } else {
            selectedImagesTitle.classList.add('hidden');
        }
    }

    imageInput.addEventListener('change', function(e) {
        if (this.files && this.files.length > 0) {
            // Add new files to our fileList object
            Array.from(this.files).forEach(file => {
                fileList.items.add(file);
            });

            // Reset the file input value so we can detect if the same file is selected again
            this.value = '';

            // Update the FileList on the input
            imageInput.files = fileList.files;

            // Update previews
            updateImagePreviews();
        }
    });

    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.classList.add('bg-green-50');
        dropZone.classList.add('border-green-300');
    }

    function unhighlight() {
        dropZone.classList.remove('bg-green-50');
        dropZone.classList.remove('border-green-300');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const droppedFiles = dt.files;

        if (droppedFiles.length > 0) {
            // Add new files to our fileList object
            Array.from(droppedFiles).forEach(file => {
                if (file.type.match('image.*')) {
                    fileList.items.add(file);
                }
            });

            // Update the FileList on the input
            imageInput.files = fileList.files;

            // Update previews
            updateImagePreviews();
        }
    }

    // Form validation before submit
    document.getElementById('project-form').addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let hasEmptyFields = false;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                hasEmptyFields = true;
            } else {
                field.classList.remove('border-red-500');
            }
        });

        if (hasEmptyFields) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });

    // Input border focus effects
    const formInputs = document.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.add('ring-1', 'ring-green-500');
        });

        input.addEventListener('blur', function() {
            this.classList.remove('ring-1', 'ring-green-500');
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>