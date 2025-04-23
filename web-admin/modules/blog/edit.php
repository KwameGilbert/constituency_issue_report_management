<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$post_id = (int) $_GET['id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $excerpt = $conn->real_escape_string($_POST['excerpt']);
       $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle slug
    if (!empty($_POST['slug'])) {
        // Use manually entered slug and sanitize it
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($_POST['slug'])));
    } else {
        // Generate slug from title
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($title)));
    }
    
    // Check if slug exists and is not the current post's slug
    $existing = $conn->query("SELECT id FROM blog_posts WHERE slug = '$slug' AND id != $post_id");
    if ($existing->num_rows > 0) {
        // Slug already exists, make it unique
        $original_slug = $slug;
        $counter = 1;
        
        while ($conn->query("SELECT id FROM blog_posts WHERE slug = '$slug' AND id != $post_id")->num_rows > 0) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
    }
    
    // Get current featured image
    $current_image = $conn->query("SELECT image_url FROM blog_posts WHERE id = $post_id")->fetch_assoc()['image_url'];
    
    // Handle image upload if a new one is provided
    $image_url = $current_image;
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
        $upload_dir = '/uploads/blog/';
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
    
    // Update the blog post - add slug to the query
    $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, content = ?, excerpt = ?, image_url = ?, slug = ?, featured = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssssii", $title, $content, $excerpt, $image_url, $slug, $featured, $post_id);
    
    
    if ($stmt->execute()) {
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Blog post updated successfully!'
        ];
        header("Location: index.php");
        exit;
    } else {
        $error = "Error updating blog post: " . $conn->error;
    }
}

// Fetch the post data
$post = $conn->query("SELECT * FROM blog_posts WHERE id = $post_id")->fetch_assoc();

// If post doesn't exist, redirect back to index
if (!$post) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Blog Post | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Include TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
                        <h2 class="text-xl font-semibold text-gray-800">Edit Blog Post</h2>
                    </div>

                    <!-- User profile -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                                <span
                                    class="hidden md:block text-sm"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                                <div
                                    class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
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

                <!-- Blog post form -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold">Edit Blog Post</h3>
                        <p class="text-gray-500 text-sm">Update the details for this blog post</p>
                    </div>

                    <form action="" method="post" enctype="multipart/form-data" class="p-6">
                        <div class="space-y-6">
                            <!-- Title input -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Post
                                    Title</label>
                                <input type="text" id="title" name="title"
                                    value="<?= htmlspecialchars($post['title']) ?>" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Slug input -->
                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                                    URL Slug
                                    <span class="text-gray-400">(How the post appears in URLs)</span>
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="slug" name="slug"
                                        value="<?= htmlspecialchars($post['slug']) ?>"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="button" id="generate-slug"
                                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                        Regenerate
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">This is used in the post's URL:
                                    yourdomain.com/blog/<span class="font-mono"
                                        id="slug-preview"><?= htmlspecialchars($post['slug']) ?></span></p>
                            </div>

                            <!-- Excerpt input -->
                            <div>
                                <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-1">
                                    Excerpt
                                    <span class="text-gray-400">(A short summary of the post)</span>
                                </label>
                                <textarea id="excerpt" name="excerpt" rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($post['excerpt']) ?></textarea>
                            </div>

                            <!-- Add this after the excerpt input -->
                            <div class="flex items-center">
                                <input type="checkbox" id="featured" name="featured"
                                    class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                    <?= $post['featured'] ? 'checked' : '' ?>>
                                <label for="featured" class="ml-2 block text-sm text-gray-700">
                                    Feature this post
                                    <span class="text-xs text-gray-500">(Featured posts appear in highlights
                                        sections)</span>
                                </label>
                            </div>

                            <!-- Featured image upload -->
                            <div>
                                <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Featured
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
                                        <?= !empty($post['image_url']) ? 'Current image: ' . basename($post['image_url']) : 'No image selected' ?>
                                    </div>
                                </div>
                                <div id="image-preview" class="mt-4 <?= empty($post['image_url']) ? 'hidden' : '' ?>">
                                    <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Preview"
                                        class="max-h-40 rounded">
                                </div>
                            </div>

                            <?php require_once __DIR__ . '/../../includes/tinymce.php'; ?>

                            <!-- Submit buttons -->
                            <div class="flex justify-end space-x-3">
                                <a href="index.php"
                                    class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    Cancel
                                </a>
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    Update Post
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
    const titleInput = document.getElementById('title');
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
        if (titleInput.value) {
            const newSlug = generateSlug(titleInput.value);
            slugInput.value = newSlug;
            slugPreview.textContent = newSlug;
        }
    });
    </script>
</body>

</html>