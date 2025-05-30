<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';

// Handle deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $carousel_id = (int) $_GET['delete'];
    // Get image path before deletion to remove the file
    $image_result = $conn->query("SELECT image_url FROM carousel_items WHERE id = $carousel_id");
    if ($image_result && $image_result->num_rows > 0) {
        $image_path = $image_result->fetch_assoc()['image_url'];
        // Remove the image file if it exists
        if ($image_path && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $image_path);
        }
    }
    // Delete the carousel item
    $conn->query("DELETE FROM carousel_items WHERE id = $carousel_id");
    // Set notification message
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Carousel item deleted successfully!'
    ];
    // Redirect to refresh the page
    header("Location: index.php");
    exit;
}

// Handle order updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    $items = $_POST['items'];
    $success = true;
    $now = date('Y-m-d H:i:s');
    foreach ($items as $position => $id) {
        $id = (int) $id;
        $position = (int) $position + 1; // Make position 1-based
        if (!$conn->query("UPDATE carousel_items SET position = $position, updated_at = '$now' WHERE id = $id")) {
            $success = false;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $link = $conn->real_escape_string($_POST['link']);
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '/uploads/carousel/';
        $server_upload_dir = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
        
        // Create directory if it doesn't exist
        if (!file_exists($server_upload_dir)) {
            mkdir($server_upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $file_path = $server_upload_dir . $file_name;
        
        // Check if it's a valid image
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image_url = $upload_dir . $file_name;
            }
        }
    }
    
    // Get the highest position value
    $max_position = $conn->query("SELECT MAX(position) as max_pos FROM carousel_items")->fetch_assoc()['max_pos'];
    $position = $max_position ? $max_position + 1 : 1;
    
    // Get current date and time
    $now = date('Y-m-d H:i:s');
    
    // Insert the carousel item
    $stmt = $conn->prepare("INSERT INTO carousel_items (title, image_url, link, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiss", $title, $image_url, $link, $position, $now, $now);
    
    if ($stmt->execute()) {
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Carousel item added successfully!'
        ];
        header("Location: index.php");
        exit;
    } else {
        $error = "Error adding carousel item: " . $conn->error;
    }
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total carousel items count
$total_items = $conn->query("SELECT COUNT(*) as count FROM carousel_items")->fetch_assoc()['count'];
$total_pages = ceil($total_items / $limit);

// Fetch carousel items with pagination
$carousel_items = $conn->query("SELECT id, title, image_url, link, position, created_at FROM carousel_items ORDER BY position, created_at DESC LIMIT $offset, $limit")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Carousel Item | Admin Panel</title>
     <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Layout structure with sidebar -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
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
                        <h2 class="text-xl font-semibold text-gray-800">Add Carousel Item</h2>
                    </div>

                    <!-- User profile -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                                <span
                                    class="hidden md:block text-sm"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                                <div
                                    class="h-8 w-8 rounded-full bg-purple-500 flex items-center justify-center text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Mobile sidebar -->
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

                <!-- Notification message -->
                <?php if (isset($_SESSION['notification'])): ?>
                <div
                    class="mb-4 p-4 rounded-md <?= $_SESSION['notification']['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                    <?= $_SESSION['notification']['message'] ?>
                    <button class="float-right focus:outline-none" onclick="this.parentElement.style.display='none';">
                        &times;
                    </button>
                </div>
                <?php unset($_SESSION['notification']); endif; ?>

                <!-- Carousel item form -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">Add New Carousel Item</h3>
                        <p class="text-gray-500 text-sm">Add a new image to the homepage carousel slideshow</p>
                    </div>

                    <form action="" method="post" enctype="multipart/form-data" class="p-6">
                        <div class="space-y-6">
                            <!-- Title input -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input type="text" id="title" name="title" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <p class="text-xs text-gray-500 mt-1">The title appears when hovering over the image</p>
                            </div>

                            <!-- Link input (optional) -->
                            <div>
                                <label for="link" class="block text-sm font-medium text-gray-700 mb-1">
                                    Link URL
                                    <span class="text-gray-400">(Optional)</span>
                                </label>
                                <input type="url" id="link" name="link" placeholder="https://"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <p class="text-xs text-gray-500 mt-1">Where users will go when they click on this
                                    carousel item</p>
                            </div>

                            <!-- Image upload -->
                            <div>
                                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Carousel
                                    Image</label>
                                <div class="flex items-center space-x-4">
                                    <div class="relative">
                                        <input type="file" id="image" name="image" accept="image/*" required
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div
                                            class="px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 cursor-pointer">
                                            Choose File
                                        </div>
                                    </div>
                                    <div id="file-name" class="text-sm text-gray-500">No file chosen</div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Recommended size: 1920×600 pixels. Max file size:
                                    2MB</p>
                                <div id="image-preview" class="mt-4 hidden">
                                    <img src="" alt="Preview" class="max-h-40 rounded">
                                </div>
                            </div>

                            <!-- Submit buttons -->
                            <div class="flex justify-end space-x-3">
                                <a href="index.php"
                                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    Cancel
                                </a>
                                <button type="submit"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                    Add Carousel Item
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
    const fileInput = document.getElementById('image');
    const fileNameDisplay = document.getElementById('file-name');
    const imagePreview = document.getElementById('image-preview');
    const previewImage = imagePreview.querySelector('img');

    fileInput.addEventListener('change', function() {
        if (fileInput.files && fileInput.files[0]) {
            fileNameDisplay.textContent = fileInput.files[0].name;

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                imagePreview.classList.remove('hidden');
            }
            reader.readAsDataURL(fileInput.files[0]);
        } else {
            fileNameDisplay.textContent = 'No file chosen';
            imagePreview.classList.add('hidden');
        }
    });
    </script>
</body>

</html>