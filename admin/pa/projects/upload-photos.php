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

// Decode existing images JSON
$images = [];
if (!empty($project['images'])) {
    $images = json_decode($project['images'], true) ?: [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success_message = '';
    
    // Handle image uploads
    if (!empty($_FILES['project_images']['name'][0])) {
        $upload_dir = '../../../uploads/projects/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $images_uploaded = 0;
        
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
                    $images_uploaded++;
                } else {
                    $errors[] = "Failed to upload file: $file_name";
                }
            } else if ($_FILES['project_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Upload error occurred for file: " . $_FILES['project_images']['name'][$key];
            }
        }
        
        if ($images_uploaded > 0) {
            // Update project with new images
            $images_json = json_encode($images);
            
            $update_query = "UPDATE projects SET images = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $images_json, $project_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Successfully uploaded " . $images_uploaded . " photo" . ($images_uploaded > 1 ? "s" : "") . " to the project.";
            } else {
                $errors[] = "Failed to update project with new images: " . $conn->error;
            }
        }
    } else {
        $errors[] = "No images were selected for upload.";
    }
    
    // Set session messages for the view page
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    
    if (!empty($success_message)) {
        $_SESSION['success'] = $success_message;
    }
    
    // Redirect back to view page
    header("Location: view.php?id=" . $project_id);
    exit;
}

// Handle AJAX image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $delete_index = intval($_POST['delete_image']);
    if (isset($images[$delete_index])) {
        $img_path = realpath(__DIR__ . '/../../../' . ltrim($images[$delete_index], '/'));
        if ($img_path && file_exists($img_path)) {
            unlink($img_path);
        }
        array_splice($images, $delete_index, 1);
        $images_json = json_encode($images);
        $update_query = "UPDATE projects SET images = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $images_json, $project_id);
        $update_stmt->execute();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Image not found.']);
        exit;
    }
}

$page_title = "Upload Photos - " . htmlspecialchars($project['title']);
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
                        <span class="text-sm font-medium text-gray-500">Upload Photos</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Upload Photos to Project</h1>
            <p class="mt-1 text-gray-600">Add new photos to "<?= htmlspecialchars($project['title']) ?>"</p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach($errors as $error): ?>
                            <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Photo Upload Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-800">
                    <i class="fas fa-images mr-2 text-green-600"></i>
                    Project Photos
                </h2>
            </div>
            <div class="p-6">
                <form id="upload-form" action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Current Photos Section -->
                    <?php if (!empty($images)): ?>
                    <div>
                        <h3 class="text-md font-medium text-gray-700 mb-3">Current Project Photos</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <?php foreach($images as $index => $image): ?>
                            <div class="relative group rounded-lg overflow-hidden border border-gray-200 h-40">
                                <img src="<?= htmlspecialchars($image) ?>" alt="Project Image <?= $index + 1 ?>"
                                    class="w-full h-full object-cover cursor-pointer lightbox-image"
                                    data-image="<?= htmlspecialchars($image) ?>" data-index="<?= $index ?>"
                                    loading="lazy">
                                <button type="button"
                                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center hover:bg-red-700 delete-image-btn"
                                    data-index="<?= $index ?>" aria-label="Delete image">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <div
                                    class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="text-white text-sm font-medium">Image <?= $index + 1 ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 my-6 pt-6"></div>
                    <?php endif; ?>

                    <!-- Upload New Photos Section -->
                    <div>
                        <h3 class="text-md font-medium text-gray-700 mb-3">Upload New Photos</h3>
                        <div id="dropzone"
                            class="cursor-pointer border-2 border-dashed border-gray-300 rounded-lg p-6 transition ease-in-out hover:border-green-500">
                            <div class="text-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                <p class="text-md text-gray-600">Drop your files here or click to browse</p>
                                <p class="text-sm text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG (Max size: 5MB)
                                </p>

                                <label for="project_images"
                                    class="mt-4 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    <i class="fas fa-folder-open mr-2"></i> Browse Files
                                </label>
                                <input id="project_images" name="project_images[]" type="file" multiple class="sr-only"
                                    accept=".jpg,.jpeg,.png">
                            </div>
                        </div>

                        <!-- Image Previews -->
                        <div class="mt-4">
                            <h4 id="selected-images-title" class="text-md font-medium text-gray-700 mb-2 hidden">
                                Selected Images:</h4>
                            <div id="preview-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mt-2">
                                <!-- Image previews will be inserted here by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <a href="view.php?id=<?= $project_id ?>"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Project
                        </a>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-upload mr-2"></i> Upload Photos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="image-lightbox" class="fixed inset-0 z-50 bg-black bg-opacity-80 items-center justify-center p-4 hidden"
    aria-modal="true" role="dialog" style="display:none;">
    <div class="relative max-w-4xl w-full">
        <div class="flex items-center justify-between text-white mb-2">
            <h3 id="lightbox-title" class="text-lg font-medium">Image <span id="lightbox-index">1</span></h3>
            <button id="close-lightbox" class="text-white focus:outline-none hover:text-green-400 transition-colors"
                aria-label="Close lightbox">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="bg-white p-1 rounded shadow-lg">
            <img id="lightbox-image" src="" alt="Enlarged project image"
                class="max-h-[80vh] max-w-full object-contain mx-auto">
        </div>
        <div class="flex justify-between mt-4">
            <button id="prev-image"
                class="px-4 py-2 bg-white bg-opacity-20 rounded text-white hover:bg-opacity-30 transition-all"
                aria-label="Previous image">
                <i class="fas fa-chevron-left mr-2"></i> Previous
            </button>
            <button id="next-image"
                class="px-4 py-2 bg-white bg-opacity-20 rounded text-white hover:bg-opacity-30 transition-all"
                aria-label="Next image">
                Next <i class="fas fa-chevron-right ml-2"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                    const previewItem = document.createElement('div');
                    previewItem.className =
                        'relative group rounded-lg overflow-hidden border border-gray-200 h-40';
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview ${index + 1}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-white text-sm font-medium">${file.name}</span>
                        </div>
                        <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-700" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    previewContainer.appendChild(previewItem);

                    // Add event listener to remove button
                    previewItem.querySelector('button').addEventListener('click', function() {
                        const fileIndex = parseInt(this.getAttribute('data-index'));
                        removeFile(fileIndex);
                    });
                }
                reader.readAsDataURL(file);
            });
        } else {
            selectedImagesTitle.classList.add('hidden');
        }
    }

    function removeFile(index) {
        const dt = new DataTransfer();
        const {
            files
        } = fileList;

        for (let i = 0; i < files.length; i++) {
            if (i !== index) {
                dt.items.add(files[i]);
            }
        }

        fileList = dt;
        imageInput.files = fileList.files;
        updateImagePreviews();
    }

    function addFiles(newFiles) {
        // Filter files based on type and size
        Array.from(newFiles).forEach(file => {
            // Check file type
            if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                alert(`File type not allowed for: ${file.name}. Only JPG, JPEG and PNG are accepted.`);
                return;
            }

            // Check file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert(`File size exceeds 5MB limit for: ${file.name}`);
                return;
            }

            fileList.items.add(file);
        });

        imageInput.files = fileList.files;
        updateImagePreviews();
    }

    imageInput.addEventListener('change', function(e) {
        if (this.files && this.files.length > 0) {
            addFiles(this.files);
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
            addFiles(droppedFiles);
        }
    }

    // Clicking on dropzone should trigger the file input
    dropZone.addEventListener('click', function() {
        imageInput.click();
    });

    // Form validation before submit
    document.getElementById('upload-form').addEventListener('submit', function(e) {
        if (fileList.files.length === 0) {
            e.preventDefault();
            alert('Please select at least one image to upload.');
        }
    });

    // Lightbox functionality
    const lightbox = document.getElementById('image-lightbox');
    const lightboxImage = document.getElementById('lightbox-image');
    const lightboxIndex = document.getElementById('lightbox-index');
    const closeButton = document.getElementById('close-lightbox');
    const prevButton = document.getElementById('prev-image');
    const nextButton = document.getElementById('next-image');
    const lightboxImages = document.querySelectorAll('.lightbox-image');
    let currentImageIndex = 0;

    function openLightbox(index) {
        currentImageIndex = index;
        const img = lightboxImages[index];

        // Preload the image to avoid flashing
        const preloadImage = new Image();
        preloadImage.src = img.getAttribute('data-image');
        preloadImage.onload = function() {
            lightboxImage.src = preloadImage.src;
            lightboxIndex.textContent = parseInt(index) + 1;
            lightbox.classList.remove('hidden');
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            updateNavigationButtons();
        };
    }

    function closeLightbox() {
        lightbox.classList.add('hidden');
        lightbox.style.display = 'none';
        document.body.style.overflow = '';
    }

    function showPreviousImage() {
        if (currentImageIndex > 0) openLightbox(currentImageIndex - 1);
    }

    function showNextImage() {
        if (currentImageIndex < lightboxImages.length - 1) openLightbox(currentImageIndex + 1);
    }

    // Update the variable reference to handle dynamic changes to the DOM
    let currentLightboxImages = document.querySelectorAll('.lightbox-image');

    function updateNavigationButtons() {
        // Refresh the reference to current images
        currentLightboxImages = document.querySelectorAll('.lightbox-image');
        prevButton.disabled = currentImageIndex <= 0;
        prevButton.classList.toggle('opacity-50', currentImageIndex <= 0);
        nextButton.disabled = currentImageIndex >= currentLightboxImages.length - 1;
        nextButton.classList.toggle('opacity-50', currentImageIndex >= currentLightboxImages.length - 1);
    }

    lightboxImages.forEach((img, index) => {
        img.addEventListener('click', () => openLightbox(index));
    });
    closeButton.addEventListener('click', closeLightbox);
    prevButton.addEventListener('click', showPreviousImage);
    nextButton.addEventListener('click', showNextImage);
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', function(e) {
        if (lightbox.style.display === 'flex') {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') showPreviousImage();
            if (e.key === 'ArrowRight') showNextImage();
        }
    });

    // Image Deletion functionality with enhanced UX
    document.querySelectorAll('.delete-image-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent lightbox from opening

            const imgIndex = this.getAttribute('data-index');
            const imageContainer = this.closest('.relative.group');
            const imageName = imageContainer.querySelector('img').alt;

            // Create and show a confirmation modal instead of using the browser's confirm dialog
            const modal = document.createElement('div');
            modal.className =
                'fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-transform">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Confirm Deletion</h3>
                        <button class="text-gray-400 hover:text-gray-500" id="cancel-delete">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mb-5">
                        <p class="text-gray-600">Are you sure you want to delete this image? This action cannot be undone.</p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button id="cancel-delete-btn" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-800 transition-colors">
                            Cancel
                        </button>
                        <button id="confirm-delete-btn" class="px-4 py-2 bg-red-500 hover:bg-red-600 rounded text-white transition-colors">
                            Delete Image
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Add initial entrance animation
            const modalContent = modal.querySelector('div');
            setTimeout(() => {
                modalContent.classList.add('scale-100');
            }, 10);

            // Handle cancel action
            const closeModal = () => {
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');
                setTimeout(() => {
                    modal.remove();
                }, 200);
            };

            document.getElementById('cancel-delete').addEventListener('click', closeModal);
            document.getElementById('cancel-delete-btn').addEventListener('click', closeModal);

            // Handle delete confirmation
            document.getElementById('confirm-delete-btn').addEventListener('click', function() {
                // Show deletion in progress
                this.disabled = true;
                this.innerHTML =
                    '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';

                // Perform AJAX delete request
                fetch('upload-photos.php?id=<?= $project_id ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'delete_image=' + encodeURIComponent(imgIndex)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Close the modal
                            modal.remove();

                            // Add a fading out animation to the deleted image
                            imageContainer.style.transition = 'all 0.3s ease';
                            imageContainer.style.opacity = '0';
                            imageContainer.style.transform = 'scale(0.9)';

                            setTimeout(() => {
                                // Remove the image from DOM
                                imageContainer.remove();

                                // Update lightbox images collection
                                currentLightboxImages = document
                                    .querySelectorAll('.lightbox-image');

                                // If all images are deleted, show a message
                                if (currentLightboxImages.length === 0) {
                                    const photosSection = document
                                        .querySelector(
                                            '.grid.grid-cols-2.sm\\:grid-cols-3.md\\:grid-cols-4.gap-3'
                                        );
                                    if (photosSection) {
                                        const emptyMessage = document
                                            .createElement('div');
                                        emptyMessage.className =
                                            'col-span-full py-8 text-center';
                                        emptyMessage.innerHTML = `
                                        <div class="text-gray-400 mb-3">
                                            <i class="fas fa-image text-5xl"></i>
                                        </div>
                                        <p class="text-gray-500">No images have been uploaded to this project yet.</p>
                                    `;
                                        photosSection.innerHTML = '';
                                        photosSection.appendChild(
                                            emptyMessage);
                                    }
                                }

                                // Show success notification
                                showToast('Image deleted successfully',
                                    'success');
                            }, 300);
                        } else {
                            // Close the modal
                            modal.remove();

                            // Show error notification
                            showToast(data.error || 'Failed to delete image',
                                'error');
                        }
                    })
                    .catch(error => {
                        // Close the modal
                        modal.remove();

                        // Show error notification
                        showToast('Network error occurred. Please try again.',
                            'error');
                        console.error('Error:', error);
                    });
            });
        });
    });

    // Improved toast notification system
    function showToast(message, type = 'info') {
        // Remove any existing toasts
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.remove(), 300);
        });

        // Create the toast element
        const toast = document.createElement('div');
        toast.className = `toast-notification fixed bottom-4 right-4 px-6 py-3 rounded-md shadow-lg z-50 flex items-center ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 'bg-blue-600'
        } text-white transition-all duration-300 transform translate-y-2 opacity-0`;

        // Add appropriate icon
        let icon = '';
        if (type === 'success') {
            icon = '<i class="fas fa-check-circle mr-2"></i>';
        } else if (type === 'error') {
            icon = '<i class="fas fa-exclamation-circle mr-2"></i>';
        } else {
            icon = '<i class="fas fa-info-circle mr-2"></i>';
        }

        toast.innerHTML = `
            ${icon}
            <span>${message}</span>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('opacity-0', 'translate-y-2');
        }, 10);

        // Auto remove after 4 seconds
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Lazy loading fallback for browsers without native support
    if (!('loading' in HTMLImageElement.prototype)) {
        const lazyImages = [].slice.call(document.querySelectorAll('img[loading="lazy"]'));

        if ('IntersectionObserver' in window) {
            let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        let img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.add('loaded');
                        lazyImageObserver.unobserve(img);
                    }
                });
            });

            lazyImages.forEach(function(img) {
                lazyImageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers without IntersectionObserver
            function lazyLoad() {
                const scrollTop = window.pageYOffset;
                lazyImages.forEach(function(img) {
                    if (img.offsetTop < window.innerHeight + scrollTop) {
                        img.src = img.dataset.src || img.src;
                        img.classList.add('loaded');
                    }
                });

                // If all images have been processed, remove the scroll event
                if (lazyImages.every(img => img.classList.contains('loaded'))) {
                    window.removeEventListener('scroll', lazyLoad);
                    window.removeEventListener('resize', lazyLoad);
                    window.removeEventListener('orientationChange', lazyLoad);
                }
            }

            // Add event listeners for scroll, resize, and orientation change
            window.addEventListener('scroll', lazyLoad);
            window.addEventListener('resize', lazyLoad);
            window.addEventListener('orientationChange', lazyLoad);

            // Initial load
            lazyLoad();
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>