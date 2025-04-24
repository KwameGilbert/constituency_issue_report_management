<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$event_id = (int) $_GET['id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $_POST['description'];
    $location = $conn->real_escape_string($_POST['location']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? $conn->real_escape_string($_POST['end_date']) : $start_date;
    $event_time = !empty($_POST['event_time']) ? $conn->real_escape_string($_POST['event_time']) : null;
    
    // Handle slug
    if (!empty($_POST['slug'])) {
        // Use manually entered slug and sanitize it
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($_POST['slug'])));
    } else {
        // Generate slug from name
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
    }
    
    // Check if slug exists and is not the current event's slug
    $existing = $conn->query("SELECT id FROM events WHERE slug = '$slug' AND id != $event_id");
    if ($existing->num_rows > 0) {
        // Slug already exists, make it unique
        $original_slug = $slug;
        $counter = 1;
        
        while ($conn->query("SELECT id FROM events WHERE slug = '$slug' AND id != $event_id")->num_rows > 0) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
    }
    
    // Get current image
    $current_image = $conn->query("SELECT image_url FROM events WHERE id = $event_id")->fetch_assoc()['image_url'];
    
    // Handle image upload if a new one is provided
    $image_url = $current_image;
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
        $upload_dir = '/uploads/events/';
        $server_upload_dir = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
        
        // Create directory if it doesn't exist
        if (!file_exists($server_upload_dir)) {
            mkdir($server_upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image_url']['name']);
        $file_path = $server_upload_dir . $file_name;
        
        // Check if it's a valid image
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['image_url']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['image_url']['tmp_name'], $file_path)) {
                // Delete old image if it exists
                if (!empty($current_image) && file_exists($_SERVER['DOCUMENT_ROOT'] . $current_image)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $current_image);
                }
                $image_url = $upload_dir . $file_name;
            }
        }
    }
    
    // Update the event
    $stmt = $conn->prepare("UPDATE events SET name = ?, slug = ?, description = ?, location = ?, start_date = ?, end_date = ?, event_time = ?, image_url = ? WHERE id = ?");
    $stmt->bind_param("ssssssssi", $name, $slug, $description, $location, $start_date, $end_date, $event_time, $image_url, $event_id);
    
    if ($stmt->execute()) {
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Event updated successfully!'
        ];
        header("Location: index.php");
        exit;
    } else {
        $error = "Error updating event: " . $conn->error;
    }
}

// Fetch the event data
$event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();

// If event doesn't exist, redirect back to index
if (!$event) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Layout structure with sidebar -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar (same as index.php) -->
        <?php require_once '../../includes/desktop_sidebar.php';?>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top navbar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4">
                    <!-- Mobile menu button -->
                    <button id="mobile-menu-button" class="md:hidden text-gray-700 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <div class="flex items-center ml-4 md:ml-0">
                        <h2 class="text-xl font-semibold text-gray-800">Edit Event</h2>
                    </div>

                    <!-- User profile -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                                <span
                                    class="hidden md:block text-sm"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                                <div
                                    class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Mobile sidebar (hidden) -->
            <?php require_once '../../includes/mobile_sidebar.php';?>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto p-4">
                <!-- Error message -->
                <?php if (isset($error)): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">
                    <?= $error ?>
                    <button class="float-right focus:outline-none" onclick="this.parentElement.style.display='none';">
                        &times;
                    </button>
                </div>
                <?php endif; ?>

                <!-- Event form -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">Edit Event</h3>
                        <p class="text-gray-500 text-sm">Update the details for this event</p>
                    </div>

                    <form action="" method="post" enctype="multipart/form-data" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Event Name -->
                            <div class="md:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Event
                                    Name</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($event['name']) ?>"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>

                            <!-- Slug field -->
                            <div class="md:col-span-2">
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                                    URL Slug
                                    <span class="text-gray-400">(How the event appears in URLs)</span>
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="slug" name="slug"
                                        value="<?= htmlspecialchars($event['slug']) ?>"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <button type="button" id="generate-slug"
                                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                        Regenerate
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">This is used in the event's URL:
                                    yourdomain.com/events/<span class="font-mono"
                                        id="slug-preview"><?= htmlspecialchars($event['slug']) ?></span></p>
                            </div>

                            <!-- Location -->
                            <div class="md:col-span-2">
                                <label for="location"
                                    class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input type="text" id="location" name="location"
                                    value="<?= htmlspecialchars($event['location']) ?>" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>

                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start
                                    Date</label>
                                <input type="date" id="start_date" name="start_date"
                                    value="<?= htmlspecialchars($event['start_date']) ?>" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>

                            <!-- End Date (optional) -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    End Date
                                    <span class="text-gray-400">(Optional, for multi-day events)</span>
                                </label>
                                <input type="date" id="end_date" name="end_date"
                                    value="<?= htmlspecialchars($event['end_date']) ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>

                            <!-- Event Time -->
                            <div class="md:col-span-2">
                                <label for="event_time" class="block text-sm font-medium text-gray-700 mb-1">
                                    Event Time
                                    <span class="text-gray-400">(Leave empty for all-day events)</span>
                                </label>
                                <input type="time" id="event_time" name="event_time"
                                    value="<?= htmlspecialchars($event['event_time'] ?? '') ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>

                            <!-- Image upload -->
                            <div class="md:col-span-2">
                                <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Event
                                    Image</label>
                                <div class="flex items-center space-x-4">
                                    <div class="relative">
                                        <input type="file" id="image_url" name="image_url" accept="image/*"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div
                                            class="px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 cursor-pointer">
                                            Choose New Image
                                        </div>
                                    </div>
                                    <div id="file-name" class="text-sm text-gray-500">
                                        <?= !empty($event['image_url']) ? 'Current image: ' . basename($event['image_url']) : 'No image selected' ?>
                                    </div>
                                </div>
                                <div id="image-preview" class="mt-4 <?= empty($event['image_url']) ? 'hidden' : '' ?>">
                                    <img src="<?= htmlspecialchars($event['image_url']) ?>" alt="Preview"
                                        class="max-h-40 rounded">
                                </div>
                            </div>

                            <!-- Description editor -->
                            <?php require_once './event_tinymce.php'; ?>

                            <!-- Submit buttons -->
                            <div class="md:col-span-2 flex justify-end space-x-3">
                                <a href="index.php"
                                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    Cancel
                                </a>
                                <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                    Update Event
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
    // File upload preview
    const fileInput = document.getElementById('image_url');
    const fileNameDisplay = document.getElementById('file-name');
    const imagePreview = document.getElementById('image-preview');
    const previewImage = imagePreview.querySelector('img');

    fileInput.addEventListener('change', function() {
        if (fileInput.files && fileInput.files[0]) {
            fileNameDisplay.textContent = 'New image: ' + fileInput.files[0].name;

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                imagePreview.classList.remove('hidden');
            }
            reader.readAsDataURL(fileInput.files[0]);
        }
    });

    // Slug generation and preview
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const generateSlugBtn = document.getElementById('generate-slug');
    const slugPreview = document.getElementById('slug-preview');

    // Function to generate slug from text
    function generateSlug(text) {
        return text.toLowerCase()
            .replace(/[^\w\s-]/g, '') // Remove special chars
            .replace(/\s+/g, '-') // Replace spaces with hyphens
            .replace(/-+/g, '-') // Replace multiple hyphens with single hyphen
            .trim(); // Trim leading/trailing spaces
    }

    // Update slug preview when slug input changes
    slugInput.addEventListener('input', function() {
        slugPreview.textContent = slugInput.value;
    });

    // Generate slug from title
    generateSlugBtn.addEventListener('click', function() {
        if (nameInput.value) {
            const newSlug = generateSlug(nameInput.value);
            slugInput.value = newSlug;
            slugPreview.textContent = newSlug;
        }
    });

    // End date validation - should not be before start date
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    // Set min attribute on page load
    if (startDateInput.value) {
        endDateInput.min = startDateInput.value;
    }

    startDateInput.addEventListener('change', function() {
        if (endDateInput.value && endDateInput.value < startDateInput.value) {
            endDateInput.value = startDateInput.value;
        }

        // Set min attribute to prevent selecting earlier date
        endDateInput.min = startDateInput.value;
    });

    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileSidebar = document.getElementById('mobile-sidebar');

    if (mobileMenuButton && mobileSidebar) {
        mobileMenuButton.addEventListener('click', function() {
            mobileSidebar.classList.toggle('hidden');
        });
    }
    </script>
</body>

</html>