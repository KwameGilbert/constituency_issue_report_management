<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$carousel_id = (int) $_GET['id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $link = $conn->real_escape_string($_POST['link']);
    
    // Get current image
    $current_image = $conn->query("SELECT image_url FROM carousel_items WHERE id = $carousel_id")->fetch_assoc()['image_url'];
    
    // Handle image upload if a new one is provided
    $image_url = $current_image;
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
                // Delete old image if it exists
                if (!empty($current_image) && file_exists($_SERVER['DOCUMENT_ROOT'] . $current_image)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $current_image);
                }
                $image_url = $upload_dir . $file_name;
            }
        }
    }
    
    // Current timestamp for updated_at
    $now = date('Y-m-d H:i:s');
    
    // Update the carousel item
    $stmt = $conn->prepare("UPDATE carousel_items SET title = ?, image_url = ?, link = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $image_url, $link, $now, $carousel_id);
    
    if ($stmt->execute()) {
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Carousel item updated successfully!'
        ];
        header("Location: index.php");
        exit;
    } else {
        $error = "Error updating carousel item: " . $conn->error;
    }
}

// Fetch the carousel item data
$carousel_item = $conn->query("SELECT * FROM carousel_items WHERE id = $carousel_id")->fetch_assoc();

// If item doesn't exist, redirect back to index
if (!$carousel_item) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Carousel Item | Admin Panel</title>
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
                        <h2 class="text-xl font-semibold text-gray-800">Edit Carousel Item</h2>
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

                <!-- Carousel item form -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">Edit Carousel Item</h3>
                        <p class="text-gray-500 text-sm">Update the details for this carousel item</p>
                    </div>

                    <form action="" method="post" enctype="multipart/form-data" class="p-6">
                        <div class="space-y-6">
                            <!-- Title input -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input type="text" id="title" name="title"
                                    value="<?= htmlspecialchars($carousel_item['title']) ?>" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <p class="text-xs text-gray-500 mt-1">The title appears when hovering over the image</p>
                            </div>

                            <!-- Link input (optional) -->
                            <div>
                                <label for="link" class="block text-sm font-medium text-gray-700 mb-1">
                                    Link URL
                                    <span class="text-gray-400">(Optional)</span>
                                </label>
                                <input type="text" id="link" name="link"
                                    value="<?= htmlspecialchars($carousel_item['link']) ?>" placeholder="https://"
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
                                        <input type="file" id="image" name="image" accept="image/*"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div
                                            class="px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 cursor-pointer">
                                            Choose New Image
                                        </div>
                                    </div>
                                    <div id="file-name" class="text-sm text-gray-500">
                                        <?= !empty($carousel_item['image_url']) ? 'Current image: ' . basename($carousel_item['image_url']) : 'No image selected' ?>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Recommended size: 1920×600 pixels. Max file size:
                                    2MB</p>
                                <div id="image-preview"
                                    class="mt-4 <?= empty($carousel_item['image_url']) ? 'hidden' : '' ?>">
                                    <img src="<?= htmlspecialchars($carousel_item['image_url']) ?>" alt="Preview"
                                        class="max-h-40 rounded">
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
                                    Update Carousel Item
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
            fileNameDisplay.textContent = 'New image: ' + fileInput.files[0].name;

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                imagePreview.classList.remove('hidden');
            }
            reader.readAsDataURL(fileInput.files[0]);
        }
    });
    </script>
</body>

</html>