<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in as PA
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit;
}

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: index.php");
    exit;
}

$project_id = intval($_GET['id']);
$pa_id = $_SESSION['pa_id'];

// Verify project belongs to the PA
$query = "SELECT * FROM projects WHERE id = ? AND pa_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $project_id, $pa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Project not found or you don't have permission to manage it.";
    header("Location: index.php");
    exit;
}

$project = $result->fetch_assoc();

// Decode images JSON
$images = [];
if (!empty($project['images'])) {
    $images = json_decode($project['images'], true) ?: [];
}

// Handle AJAX image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $response = ['success' => false];
    $delete_index = intval($_POST['delete_image']);
    
    if (isset($images[$delete_index])) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . $images[$delete_index];
        
        // Try to delete the file from server
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        // Remove from array
        array_splice($images, $delete_index, 1);
        
        // Update database
        $images_json = json_encode(array_values($images));
        $update_query = "UPDATE projects SET images = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $images_json, $project_id);
        
        if ($update_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Image deleted successfully.";
            $response['images'] = $images;
        } else {
            $response['message'] = "Failed to update database: " . $conn->error;
        }
    } else {
        $response['message'] = "Image not found.";
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle image uploads
$errors = [];
$success_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['project_images'])) {
    $upload_dir = '../../../uploads/projects/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $new_images = [];
    
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
                $new_images[] = '/uploads/projects/' . $new_file_name;
            } else {
                $errors[] = "Failed to upload file: $file_name";
            }
        }
    }
    
    if (!empty($new_images)) {
        // Add new images to existing ones
        $images = array_merge($images, $new_images);
        
        // Update database
        $images_json = json_encode($images);
        $update_query = "UPDATE projects SET images = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $images_json, $project_id);
        
        if ($update_stmt->execute()) {
            $success_messages[] = count($new_images) . " image(s) uploaded successfully.";
        } else {
            $errors[] = "Failed to update database: " . $conn->error;
        }
    }
    
    // Handle AJAX uploads
    if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
        $response = [
            'success' => empty($errors),
            'errors' => $errors,
            'messages' => $success_messages,
            'images' => $images
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

$page_title = "Manage Project Photos - PA Portal";
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
                        <span class="text-sm font-medium text-gray-500">Manage Photos</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (!empty($success_messages)): ?>
        <div id="successAlert" class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-md p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <div>
                    <?php foreach ($success_messages as $message): ?>
                    <p><?= $message ?></p>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="ml-auto text-green-500 hover:text-green-700"
                    onclick="this.parentElement.parentElement.style.display='none';">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div id="errorAlert" class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-md p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <div>
                    <?php foreach ($errors as $error): ?>
                    <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="ml-auto text-red-500 hover:text-red-700"
                    onclick="this.parentElement.parentElement.style.display='none';">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Manage Project Photos</h1>
            <p class="mt-1 text-gray-600">Upload, view, and delete photos for
                "<?= htmlspecialchars($project['title']) ?>"</p>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-6">
                <!-- Upload Form -->
                <div class="mb-8">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Upload New Photos</h2>
                    <form id="uploadForm" action="upload-photos.php?id=<?= $project_id ?>" method="POST"
                        enctype="multipart/form-data" class="space-y-4">
                        <div id="dropzone"
                            class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md transition-colors duration-200 hover:border-green-400 cursor-pointer">
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

                        <div id="previewContainer"
                            class="hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mt-4">
                            <!-- Image previews will be added here dynamically -->
                        </div>

                        <div id="uploadProgress" class="hidden">
                            <div class="mb-2 flex justify-between">
                                <span>Uploading images...</span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div id="progressBar" class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" id="uploadButton"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                <i class="fas fa-cloud-upload-alt mr-2"></i>
                                <span>Upload Photos</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Current Photos -->
                <div>
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Current Photos</h2>
                    <?php if (empty($images)): ?>
                    <div class="text-center py-8 bg-gray-50 rounded-md">
                        <i class="fas fa-images text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-500">No images have been uploaded for this project yet.</p>
                    </div>
                    <?php else: ?>
                    <div id="photoGallery" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="photo-item rounded-lg overflow-hidden border border-gray-200 relative group h-40"
                            data-index="<?= $index ?>">
                            <img loading="lazy" data-image="<?= htmlspecialchars($image) ?>"
                                class="w-full h-full object-cover cursor-pointer lazy-image"
                                src="<?= htmlspecialchars($image) ?>" alt="Project Image <?= $index + 1 ?>">
                            <div
                                class="absolute inset-0 bg-black bg-opacity-40 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-2">
                                <button type="button"
                                    class="view-btn p-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 focus:outline-none"
                                    onclick="openLightbox(<?= $index ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button"
                                    class="delete-btn p-2 bg-red-600 text-white rounded-full hover:bg-red-700 focus:outline-none"
                                    onclick="confirmDeleteImage(<?= $index ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center hidden">
    <button id="closeLightbox" class="absolute top-4 right-4 text-white text-xl hover:text-gray-300 focus:outline-none">
        <i class="fas fa-times"></i>
    </button>

    <div class="max-w-4xl w-full relative">
        <img id="lightboxImage" src="" alt="Lightbox Image" class="max-h-[80vh] mx-auto">

        <div class="absolute inset-y-0 left-0 flex items-center">
            <button id="prevImage"
                class="p-2 bg-black bg-opacity-50 text-white rounded-r-md hover:bg-opacity-70 focus:outline-none">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <div class="absolute inset-y-0 right-0 flex items-center">
            <button id="nextImage"
                class="p-2 bg-black bg-opacity-50 text-white rounded-l-md hover:bg-opacity-70 focus:outline-none">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <div class="text-center text-white mt-4">
            <span id="lightboxIndex">1</span> / <span id="lightboxTotal"><?= count($images) ?></span>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <div class="text-center mb-4">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-5xl"></i>
        </div>
        <h3 class="text-xl font-semibold text-center mb-4">Confirm Deletion</h3>
        <p class="text-gray-600 mb-6 text-center">Are you sure you want to delete this image? This action cannot be
            undone.</p>
        <div class="flex justify-center gap-4">
            <button id="cancelDelete"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none">
                Cancel
            </button>
            <button id="confirmDelete"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none flex items-center">
                <i class="fas fa-trash-alt mr-2"></i>
                <span>Delete Image</span>
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-4 flex items-start max-w-xs">
        <div id="toastIcon" class="mr-3"></div>
        <div>
            <div id="toastTitle" class="font-medium"></div>
            <div id="toastMessage" class="text-sm text-gray-600"></div>
        </div>
        <button type="button" class="ml-4 text-gray-400 hover:text-gray-500" onclick="hideToast()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
// Global variables
let lightbox = document.getElementById('lightbox');
let lightboxImage = document.getElementById('lightboxImage');
let lightboxIndex = document.getElementById('lightboxIndex');
let lightboxTotal = document.getElementById('lightboxTotal');
let lightboxImages = document.querySelectorAll('.lazy-image');
let currentImageIndex = 0;
let toBeDeletedIndex = null;

// Initialization
document.addEventListener("DOMContentLoaded", function() {
    // Drag and drop functionality
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('project_images');
    const previewContainer = document.getElementById('previewContainer');
    const uploadForm = document.getElementById('uploadForm');
    const uploadButton = document.getElementById('uploadButton');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');

    // Initialize lightbox navigation
    updateLightboxNavigation();

    // Handle file selection via file input
    fileInput.addEventListener('change', function(e) {
        handleFiles(this.files);
    });

    // Handle drag and drop events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    dropzone.addEventListener('dragenter', function() {
        this.classList.add('border-green-500');
    });

    dropzone.addEventListener('dragleave', function() {
        this.classList.remove('border-green-500');
    });

    dropzone.addEventListener('drop', function(e) {
        this.classList.remove('border-green-500');
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    });

    // Handle file preview
    function handleFiles(files) {
        if (files.length > 0) {
            previewContainer.classList.remove('hidden');
            previewContainer.innerHTML = '';

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (!file.type.match('image.*')) {
                    continue;
                }

                const reader = new FileReader();
                const imgContainer = document.createElement('div');
                imgContainer.className = 'relative rounded-lg overflow-hidden h-40';

                const img = document.createElement('img');
                img.className = 'w-full h-full object-cover';

                reader.onload = (function(aImg) {
                    return function(e) {
                        aImg.src = e.target.result;
                    };
                })(img);

                reader.readAsDataURL(file);
                imgContainer.appendChild(img);
                previewContainer.appendChild(imgContainer);
            }

            uploadButton.disabled = false;
        }
    }

    // Handle form submission with AJAX
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (fileInput.files.length === 0) {
            showToast('warning', 'Warning', 'Please select at least one image to upload.');
            return;
        }

        const formData = new FormData(this);
        formData.append('ajax', 'true');

        // Disable button and show progress
        uploadButton.disabled = true;
        uploadProgress.classList.remove('hidden');

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressPercent.textContent = percentComplete + '%';
            }
        });

        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.success) {
                        // Show success message
                        showToast('success', 'Success', response.messages[0]);

                        // Reset form and preview
                        uploadForm.reset();
                        previewContainer.classList.add('hidden');
                        previewContainer.innerHTML = '';

                        // Refresh gallery
                        refreshGallery(response.images);

                        // Update lightbox total
                        lightboxTotal.textContent = response.images.length;

                        // Reload lightbox images
                        lightboxImages = document.querySelectorAll('.lazy-image');
                        updateLightboxNavigation();
                    } else {
                        // Show error message
                        showToast('error', 'Error', response.errors[0]);
                    }
                } catch (e) {
                    showToast('error', 'Error', 'An unexpected error occurred.');
                }
            } else {
                showToast('error', 'Error', 'Failed to upload images. Please try again.');
            }

            // Hide progress and re-enable button
            uploadProgress.classList.add('hidden');
            uploadButton.disabled = false;
        });

        xhr.addEventListener('error', function() {
            showToast('error', 'Error', 'Network error occurred. Please try again.');
            uploadProgress.classList.add('hidden');
            uploadButton.disabled = false;
        });

        xhr.open('POST', 'upload-photos.php?id=<?= $project_id ?>');
        xhr.send(formData);
    });

    // Set up lightbox event listeners
    document.getElementById('closeLightbox').addEventListener('click', closeLightbox);
    document.getElementById('prevImage').addEventListener('click', showPrevImage);
    document.getElementById('nextImage').addEventListener('click', showNextImage);

    // Set up delete modal event listeners
    document.getElementById('cancelDelete').addEventListener('click', cancelDelete);
    document.getElementById('confirmDelete').addEventListener('click', deleteConfirmed);

    // Initialize lazy loading for browsers that don't support it natively
    if (!('loading' in HTMLImageElement.prototype)) {
        const lazyImages = [].slice.call(document.querySelectorAll('img.lazy-image'));

        if ('IntersectionObserver' in window) {
            let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        let lazyImage = entry.target;
                        lazyImage.src = lazyImage.dataset.image;
                        lazyImageObserver.unobserve(lazyImage);
                    }
                });
            });

            lazyImages.forEach(function(lazyImage) {
                lazyImageObserver.observe(lazyImage);
            });
        } else {
            // Fallback for browsers without IntersectionObserver support
            let active = false;

            const lazyLoad = function() {
                if (active === false) {
                    active = true;

                    setTimeout(function() {
                        lazyImages.forEach(function(lazyImage) {
                            if ((lazyImage.getBoundingClientRect().top <= window
                                    .innerHeight && lazyImage.getBoundingClientRect()
                                    .bottom >= 0) && getComputedStyle(lazyImage).display !==
                                "none") {
                                lazyImage.src = lazyImage.dataset.image;
                                lazyImages = lazyImages.filter(function(image) {
                                    return image !== lazyImage;
                                });

                                if (lazyImages.length === 0) {
                                    document.removeEventListener('scroll', lazyLoad);
                                    window.removeEventListener('resize', lazyLoad);
                                    window.removeEventListener('orientationchange',
                                        lazyLoad);
                                }
                            }
                        });

                        active = false;
                    }, 200);
                }
            };

            document.addEventListener('scroll', lazyLoad);
            window.addEventListener('resize', lazyLoad);
            window.addEventListener('orientationchange', lazyLoad);
            lazyLoad();
        }
    }
});

// Lightbox functions
function openLightbox(index) {
    currentImageIndex = index;
    const img = lightboxImages[index];
    lightboxImage.src = img.getAttribute('data-image');
    lightboxIndex.textContent = parseInt(index) + 1;
    lightbox.classList.remove('hidden');
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    updateNavigationButtons();
}

function closeLightbox() {
    lightbox.classList.add('hidden');
    document.body.style.overflow = '';
}

function showPrevImage() {
    if (currentImageIndex > 0) {
        openLightbox(currentImageIndex - 1);
    }
}

function showNextImage() {
    if (currentImageIndex < lightboxImages.length - 1) {
        openLightbox(currentImageIndex + 1);
    }
}

function updateNavigationButtons() {
    document.getElementById('prevImage').style.visibility = currentImageIndex > 0 ? 'visible' : 'hidden';
    document.getElementById('nextImage').style.visibility = currentImageIndex < lightboxImages.length - 1 ? 'visible' :
        'hidden';
}

function updateLightboxNavigation() {
    // Add keyboard navigation for lightbox
    document.addEventListener('keydown', function(e) {
        if (lightbox.classList.contains('hidden')) return;

        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            showPrevImage();
        } else if (e.key === 'ArrowRight') {
            showNextImage();
        }
    });
}

// Delete image functions
function confirmDeleteImage(index) {
    toBeDeletedIndex = index;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function cancelDelete() {
    document.getElementById('deleteModal').classList.add('hidden');
    toBeDeletedIndex = null;
}

function deleteConfirmed() {
    if (toBeDeletedIndex === null) return;

    // Show loading state in button
    const confirmBtn = document.getElementById('confirmDelete');
    const originalContent = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Deleting...</span>';
    confirmBtn.disabled = true;

    // Send delete request
    const formData = new FormData();
    formData.append('delete_image', toBeDeletedIndex);

    fetch('upload-photos.php?id=<?= $project_id ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Hide the modal
            document.getElementById('deleteModal').classList.add('hidden');

            if (data.success) {
                // Show success message
                showToast('success', 'Success', 'Image deleted successfully.');

                // Remove the deleted image from display
                const photoItems = document.querySelectorAll('.photo-item');
                photoItems.forEach(item => {
                    if (parseInt(item.dataset.index) === toBeDeletedIndex) {
                        item.remove();
                    }
                });

                // Refresh gallery with updated images
                refreshGallery(data.images);

                // Update lightbox total
                lightboxTotal.textContent = data.images.length;

                // Reload lightbox images
                lightboxImages = document.querySelectorAll('.lazy-image');
                updateLightboxNavigation();
            } else {
                // Show error message
                showToast('error', 'Error', data.message || 'Failed to delete image.');
            }

            // Reset button state
            confirmBtn.innerHTML = originalContent;
            confirmBtn.disabled = false;
            toBeDeletedIndex = null;
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error', 'An unexpected error occurred.');

            // Reset button state
            confirmBtn.innerHTML = originalContent;
            confirmBtn.disabled = false;
            document.getElementById('deleteModal').classList.add('hidden');
            toBeDeletedIndex = null;
        });
}

// Refresh gallery with new images
function refreshGallery(images) {
    const gallery = document.getElementById('photoGallery');

    if (!gallery) return;

    if (images.length === 0) {
        gallery.innerHTML = `
                <div class="col-span-full text-center py-8 bg-gray-50 rounded-md">
                    <i class="fas fa-images text-gray-400 text-4xl mb-3"></i>
                    <p class="text-gray-500">No images have been uploaded for this project yet.</p>
                </div>
            `;
        return;
    }

    // Create gallery HTML
    gallery.innerHTML = '';

    images.forEach((image, index) => {
        const item = document.createElement('div');
        item.className = 'photo-item rounded-lg overflow-hidden border border-gray-200 relative group h-40';
        item.dataset.index = index;

        item.innerHTML = `
                <img loading="lazy" data-image="${image}" class="w-full h-full object-cover cursor-pointer lazy-image" 
                     src="${image}" alt="Project Image ${index + 1}">
                <div class="absolute inset-0 bg-black bg-opacity-40 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-2">
                    <button type="button" class="view-btn p-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 focus:outline-none"
                            onclick="openLightbox(${index})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="delete-btn p-2 bg-red-600 text-white rounded-full hover:bg-red-700 focus:outline-none"
                            onclick="confirmDeleteImage(${index})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;

        gallery.appendChild(item);
    });
}

// Toast notification functions
function showToast(type, title, message) {
    const toast = document.getElementById('toast');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');

    // Set icon based on type
    let iconHtml = '';
    let iconColor = '';

    switch (type) {
        case 'success':
            iconHtml = '<i class="fas fa-check-circle"></i>';
            iconColor = 'text-green-500';
            break;
        case 'error':
            iconHtml = '<i class="fas fa-exclamation-circle"></i>';
            iconColor = 'text-red-500';
            break;
        case 'warning':
            iconHtml = '<i class="fas fa-exclamation-triangle"></i>';
            iconColor = 'text-yellow-500';
            break;
        default:
            iconHtml = '<i class="fas fa-info-circle"></i>';
            iconColor = 'text-blue-500';
    }

    toastIcon.className = iconColor;
    toastIcon.innerHTML = iconHtml;
    toastTitle.textContent = title;
    toastMessage.textContent = message;

    // Show the toast
    toast.classList.remove('hidden');

    // Hide after 5 seconds
    setTimeout(hideToast, 5000);
}

function hideToast() {
    const toast = document.getElementById('toast');
    toast.classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>