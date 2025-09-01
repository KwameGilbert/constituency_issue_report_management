<?php
// filepath: c:\xampp\htdocs\swma\web-admin\modules\profile\index.php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';

// Get the admin's data
$admin_id = $_SESSION['admin_id'];
$query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Initialize messages array
$messages = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $messages['error'] = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messages['error'] = "Please enter a valid email address";
    } else {
        // Check if email exists for another user
        $check_query = "SELECT id FROM admins WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $email, $admin_id);
        $check_stmt->execute();
        $existing_user = $check_stmt->get_result()->fetch_assoc();

        if ($existing_user) {
            $messages['error'] = "Email address is already in use by another account";
        } else {
            // Update profile
            $update_query = "UPDATE admins SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssi", $first_name, $last_name, $email, $admin_id);
            
            if ($update_stmt->execute()) {
                // Handle profile image upload if present
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                        $messages['error'] = "Only JPG, PNG and GIF images are allowed";
                    } elseif ($_FILES['profile_image']['size'] > $max_size) {
                        $messages['error'] = "Image size should not exceed 2MB";
                    } else {
                        $upload_dir = '../../../uploads/admin_profiles/';
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Generate unique filename
                        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $filename = 'profile_' . $admin_id . '_' . time() . '.' . $file_extension;
                        $target_file = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                            // Update profile image in database
                            $image_path = 'uploads/admin_profiles/' . $filename;
                            $image_query = "UPDATE admins SET profile_image = ? WHERE id = ?";
                            $image_stmt = $conn->prepare($image_query);
                            $image_stmt->bind_param("si", $image_path, $admin_id);
                            
                            if ($image_stmt->execute()) {
                                // Delete old image if exists
                                if (!empty($admin['profile_image']) && $admin['profile_image'] != $image_path) {
                                    $old_file = '../../../' . $admin['profile_image'];
                                    if (file_exists($old_file)) {
                                        unlink($old_file);
                                    }
                                }
                            } else {
                                $messages['error'] = "Failed to update profile image in database";
                            }
                        } else {
                            $messages['error'] = "Failed to upload image";
                        }
                    }
                }
                
                if (!isset($messages['error'])) {
                    $messages['success'] = "Profile updated successfully";
                    
                    // Refresh admin data
                    $stmt->execute();
                    $admin = $stmt->get_result()->fetch_assoc();
                }
            } else {
                $messages['error'] = "Failed to update profile";
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $messages['password_error'] = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $messages['password_error'] = "New passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $messages['password_error'] = "Password must be at least 8 characters long";
    } else {
        // Verify current password
        if (password_verify($current_password, $admin['password_hash'])) {
            // Hash new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_query = "UPDATE admins SET password_hash = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $new_password_hash, $admin_id);
            
            if ($update_stmt->execute()) {
                $messages['password_success'] = "Password updated successfully";
            } else {
                $messages['password_error'] = "Failed to update password";
            }
        } else {
            $messages['password_error'] = "Current password is incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | Web Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php require_once '../../includes/desktop_sidebar.php'; ?>

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
                        <h2 class="text-xl font-semibold text-gray-800">Profile Settings</h2>
                    </div>

                    <!-- User menu -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                                <span class="hidden md:block text-sm"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></span>
                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white overflow-hidden">
                                    <?php if (!empty($admin['profile_image']) && file_exists('../../../' . $admin['profile_image'])): ?>
                                        <img src="/<?= $admin['profile_image'] ?>" alt="Profile" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <?php require_once '../../includes/mobile_sidebar.php'; ?>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto p-4">
                <div class="max-w-4xl mx-auto">
                    <!-- Profile Info Section -->
                    <div class="bg-white rounded-lg shadow mb-6">
                        <div class="p-4 border-b">
                            <h2 class="text-lg font-semibold">Profile Information</h2>
                            <p class="text-sm text-gray-500">Update your account's profile information and email address.</p>
                        </div>

                        <div class="p-6">
                            <?php if (isset($messages['success'])): ?>
                                <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 text-green-700">
                                    <p><?= $messages['success'] ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($messages['error'])): ?>
                                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 text-red-700">
                                    <p><?= $messages['error'] ?></p>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="md:w-2/3 space-y-4">
                                        <!-- Username (non-editable) -->
                                        <div>
                                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                            <input type="text" id="username" value="<?= htmlspecialchars($admin['username']) ?>" class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm text-gray-600" readonly disabled>
                                            <p class="mt-1 text-xs text-gray-500">Username cannot be changed</p>
                                        </div>
                                        
                                        <!-- First Name -->
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                            <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($admin['first_name']) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <!-- Last Name -->
                                        <div>
                                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                            <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($admin['last_name']) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <!-- Email -->
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($admin['email']) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        </div>

                                        <!-- Role Information (non-editable) -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Role</label>
                                            <input type="text" value="<?= ucfirst(str_replace('_', ' ', $admin['role'])) ?>" class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm text-gray-600" disabled>
                                        </div>
                                    </div>
                                    
                                    <div class="md:w-1/3">
                                        <!-- Profile Image -->
                                        <div class="text-center">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                                            <div class="flex flex-col items-center">
                                                <div class="mb-4 h-32 w-32 rounded-full overflow-hidden bg-gray-100 border">
                                                    <?php if (!empty($admin['profile_image']) && file_exists('../../../' . $admin['profile_image'])): ?>
                                                        <img src="/<?= $admin['profile_image'] ?>" alt="Profile" class="h-full w-full object-cover preview-image" id="profile-preview">
                                                    <?php else: ?>
                                                        <div class="h-full w-full flex items-center justify-center bg-gray-200 text-gray-400">
                                                            <i class="fas fa-user text-4xl"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <label for="profile_image" class="cursor-pointer px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium text-gray-700">
                                                    Change Photo
                                                </label>
                                                <input type="file" id="profile_image" name="profile_image" class="hidden" accept="image/*" onchange="previewImage(this, 'profile-preview')">
                                                <p class="mt-1 text-xs text-gray-500">JPG, PNG or GIF (max. 2MB)</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t pt-5">
                                    <button type="submit" name="update_profile" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Password Update Section -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-4 border-b">
                            <h2 class="text-lg font-semibold">Update Password</h2>
                            <p class="text-sm text-gray-500">Ensure your account is using a secure password.</p>
                        </div>

                        <div class="p-6">
                            <?php if (isset($messages['password_success'])): ?>
                                <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 text-green-700">
                                    <p><?= $messages['password_success'] ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($messages['password_error'])): ?>
                                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 text-red-700">
                                    <p><?= $messages['password_error'] ?></p>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="space-y-4">
                                <!-- Current Password -->
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" name="current_password" id="current_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <!-- New Password -->
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                                </div>
                                
                                <!-- Confirm Password -->
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div class="border-t pt-5">
                                    <button type="submit" name="update_password" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="bg-white rounded-lg shadow mt-6">
                        <div class="p-4 border-b">
                            <h2 class="text-lg font-semibold">Account Information</h2>
                        </div>

                        <div class="p-6">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Account Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= $admin['status'] === 'active' ? 'bg-green-100 text-green-800' : ($admin['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                            <?= ucfirst($admin['status']) ?>
                                        </span>
                                    </dd>
                                </div>
                                
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= $admin['last_login'] ? date('M d, Y h:i A', strtotime($admin['last_login'])) : 'Never' ?>
                                    </dd>
                                </div>
                                
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Account Created</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= date('M d, Y', strtotime($admin['created_at'])) ?>
                                    </dd>
                                </div>
                                
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= date('M d, Y', strtotime($admin['updated_at'])) ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white p-4 border-t text-center text-sm text-gray-600">
                &copy; <?= date('Y') ?> Constituency Issue Report Management. All rights reserved.
            </footer>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const sidebar = document.getElementById('mobile-sidebar');
            sidebar.classList.toggle('hidden');
        });

        // Image preview functionality
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    
                    if (preview.tagName.toLowerCase() === 'img') {
                        preview.src = e.target.result;
                    } else {
                        // If the preview element is not an img, replace it with one
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'h-full w-full object-cover preview-image';
                        img.id = previewId;
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>