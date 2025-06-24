<?php
// profile_settings/index.php - Agent Profile Settings Page
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../login/session_check.php';

$database = new Database();
$conn = $database->getConnection();

// Set current page for sidebar highlighting
$current_page = 'profile_settings';

// Get current user data from session
$userId = $_SESSION['user_id'] ?? 1;

// Initialize message variables
$message = '';
$message_type = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check which form was submitted
        if (isset($_POST['update_profile'])) {
            // Update user profile information
            $stmt = $conn->prepare("
                UPDATE users SET
                    name = :name,
                    email = :email,
                    phone = :phone,
                    department = :department
                WHERE id = :user_id
            ");

            $stmt->bindParam(':name', $_POST['name']);
            $stmt->bindParam(':email', $_POST['email']);
            $stmt->bindParam(':phone', $_POST['phone']);
            $stmt->bindParam(':department', $_POST['department']);
            $stmt->bindParam(':user_id', $userId);

            // Execute update
            $stmt->execute();
            $message = "Profile information updated successfully!";
            $message_type = "success";

            // Update session if applicable
            if (isset($_SESSION['user_name'])) {
                $_SESSION['user_name'] = $_POST['name'];
                $_SESSION['user_email'] = $_POST['email'];
            }
        } elseif (isset($_POST['update_password'])) {
            // Validate current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $currentPasswordHash = $stmt->fetchColumn();

            if (password_verify($_POST['current_password'], $currentPasswordHash)) {
                // Validate new password
                if ($_POST['new_password'] === $_POST['confirm_password'] && strlen($_POST['new_password']) >= 8) {
                    // Update password
                    $newPasswordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

                    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
                    $stmt->bindParam(':password', $newPasswordHash);
                    $stmt->bindParam(':user_id', $userId);
                    $stmt->execute();

                    $message = "Password updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "New passwords don't match or are too short (minimum 8 characters)";
                    $message_type = "error";
                }
            } else {
                $message = "Current password is incorrect";
                $message_type = "error";
            }
        } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            // Handle profile image upload
            $targetDir = "../../uploads/profile_images/";

            // Create directory if it doesn't exist
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // Generate unique filename
            $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = $userId . '_' . time() . '.' . $fileExtension;
            $targetFilePath = $targetDir . $fileName;

            // Check file type (only allow images)
            $allowedTypes = ['jpg', 'jpeg', 'png'];
            if (in_array(strtolower($fileExtension), $allowedTypes)) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFilePath)) {
                    // Update database with new profile image path
                    $relativeFilePath = "uploads/profile_images/" . $fileName;

                    $stmt = $conn->prepare("UPDATE users SET profile_image = :profile_image WHERE id = :user_id");
                    $stmt->bindParam(':profile_image', $relativeFilePath);
                    $stmt->bindParam(':user_id', $userId);
                    $stmt->execute();

                    $message = "Profile image updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Failed to upload image. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Only JPG, JPEG, and PNG files are allowed.";
                $message_type = "error";
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch user data
try {
    $stmt = $conn->prepare("
        SELECT u.*, ea.name as electoral_area_name 
        FROM users u
        LEFT JOIN electoral_areas ea ON u.electoral_area = ea.id
        WHERE u.id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch electoral areas for dropdown
    $stmt = $conn->prepare("SELECT id, name FROM electoral_areas ORDER BY name");
    $stmt->execute();
    $electoralAreas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Error fetching user data: " . $e->getMessage();
    $message_type = "error";

    // Fallback user data for demo
    $user = [
        'id' => 1,
        'name' => 'Sarah Agent',
        'email' => 'sarah.agent@example.com',
        'role' => 'agent',
        'phone' => '+233 20 123 4567',
        'profile_image' => null,
        'electoral_area' => 2,
        'electoral_area_name' => 'North District',
        'department' => 'Community Relations',
        'status' => 'active',
        'created_at' => '2023-01-15 08:30:00',
        'last_login' => '2023-06-23 14:25:00'
    ];
}

// Define action buttons for the header
$headerActionButtons = [];

// Get user's role for display
$roleLabels = [
    'mp' => 'Member of Parliament',
    'mce' => 'Municipal Chief Executive',
    'pa' => 'Personal Assistant',
    'officer' => 'Officer',
    'agent' => 'Field Agent',
    'admin' => 'Administrator'
];

$userName = $user['name'] ?? 'User';
$userRoleLabel = $roleLabels[$user['role']] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile Settings - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        success: '#10b981',
                        warning: '#f59e0b',
                        error: '#ef4444',
                        slate: {
                            50: '#f8fafc',
                            900: '#0f172a',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 min-h-screen font-sans">
    <?php renderAgentSidebar($current_page); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAgentHeader('Profile Settings', 'Manage your account details and preferences', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-4 p-3 rounded-xl text-xs <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Overview Card -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
                <div class="flex flex-col md:flex-row md:items-center">
                    <div class="flex-shrink-0 flex items-center justify-center mb-4 md:mb-0 md:mr-6">
                        <div class="relative">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                <?php if (!empty($user['profile_image'])) : ?>
                                    <img src="../../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="w-full h-full object-cover">
                                <?php else : ?>
                                    <i class="fas fa-user text-gray-400 text-4xl"></i>
                                <?php endif; ?>
                            </div>
                            <!-- Update Profile Image Button (Overlaid on the image) -->
                            <label for="profile_image_upload" class="absolute bottom-0 right-0 w-8 h-8 bg-slate-900 rounded-full flex items-center justify-center text-white cursor-pointer">
                                <i class="fas fa-camera text-xs"></i>
                            </label>
                            <form id="image-upload-form" method="POST" enctype="multipart/form-data" class="hidden">
                                <input type="file" name="profile_image" id="profile_image_upload" class="hidden" accept="image/jpeg,image/png">
                            </form>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($userRoleLabel); ?></p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs flex items-center">
                                <i class="fas fa-envelope text-xs mr-1"></i> <?php echo htmlspecialchars($user['email']); ?>
                            </span>
                            <?php if (!empty($user['phone'])) : ?>
                                <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs flex items-center">
                                    <i class="fas fa-phone text-xs mr-1"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="px-2 py-1 bg-<?php echo $user['status'] === 'active' ? 'green' : 'red'; ?>-100 text-<?php echo $user['status'] === 'active' ? 'green' : 'red'; ?>-700 rounded-lg text-xs flex items-center">
                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'check-circle' : 'times-circle'; ?> text-xs mr-1"></i> <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-100">
                    <div class="flex overflow-x-auto">
                        <button id="tab-profile" class="px-6 py-3 text-sm font-medium border-b-2 border-slate-900 text-slate-900">Profile Information</button>
                        <button id="tab-password" class="px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">Change Password</button>
                        <button id="tab-account" class="px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">Account Settings</button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-5">
                    <!-- Profile Information Tab -->
                    <div id="content-profile" class="tab-content">
                        <form method="POST" action="" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-xs font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900">
                                </div>
                                <div>
                                    <label for="email" class="block text-xs font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="phone" class="block text-xs font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900" placeholder="e.g., +233 20 123 4567">
                                </div>
                                <div>
                                    <label for="department" class="block text-xs font-medium text-gray-700 mb-1">Department</label>
                                    <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900" placeholder="e.g., Community Relations">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="role" class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                                    <input type="text" id="role" value="<?php echo htmlspecialchars($userRoleLabel); ?>" class="w-full px-3 py-2 text-sm border border-gray-200 bg-gray-50 rounded-lg" disabled>
                                </div>
                                <div>
                                    <label for="electoral_area" class="block text-xs font-medium text-gray-700 mb-1">Electoral Area</label>
                                    <input type="text" id="electoral_area" value="<?php echo htmlspecialchars($user['electoral_area_name'] ?? 'Not Assigned'); ?>" class="w-full px-3 py-2 text-sm border border-gray-200 bg-gray-50 rounded-lg" disabled>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4 flex justify-end">
                                <button type="submit" name="update_profile" class="px-4 py-2 bg-slate-900 text-white text-xs font-medium rounded-xl hover:bg-slate-800 transition-colors">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password Tab -->
                    <div id="content-password" class="tab-content hidden">
                        <form method="POST" action="" class="space-y-4">
                            <div class="max-w-md">
                                <div class="mb-4">
                                    <label for="current_password" class="block text-xs font-medium text-gray-700 mb-1">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900">
                                </div>

                                <div class="mb-4">
                                    <label for="new_password" class="block text-xs font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="8" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900">
                                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="block text-xs font-medium text-gray-700 mb-1">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900">
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4 flex justify-end">
                                <button type="submit" name="update_password" class="px-4 py-2 bg-slate-900 text-white text-xs font-medium rounded-xl hover:bg-slate-800 transition-colors">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Settings Tab -->
                    <div id="content-account" class="tab-content hidden">
                        <div class="space-y-6">
                            <!-- Account Activity -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Account Activity</h3>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="text-xs text-gray-500">Last Login</div>
                                        <div class="text-xs font-medium">
                                            <?php echo !empty($user['last_login']) ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-500">Account Created</div>
                                        <div class="text-xs font-medium">
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notification Preferences (Placeholder) -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Notification Preferences</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="notify_issue_updates" class="h-4 w-4 text-slate-900 rounded border-gray-300 focus:ring-slate-900" checked>
                                        <label for="notify_issue_updates" class="ml-2 block text-xs text-gray-700">
                                            Issue status updates
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="notify_reminders" class="h-4 w-4 text-slate-900 rounded border-gray-300 focus:ring-slate-900" checked>
                                        <label for="notify_reminders" class="ml-2 block text-xs text-gray-700">
                                            Task reminders
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="notify_system" class="h-4 w-4 text-slate-900 rounded border-gray-300 focus:ring-slate-900" checked>
                                        <label for="notify_system" class="ml-2 block text-xs text-gray-700">
                                            System notifications
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Danger Zone -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Danger Zone</h3>
                                <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-red-800">Deactivate Account</h4>
                                            <p class="text-xs text-red-600 mt-1">
                                                Temporarily disable your account. You would have to contact your supervisor to reactivate your account.
                                            </p>
                                        </div>
                                        <button class="px-3 py-1.5 bg-white text-red-600 border border-red-300 text-xs font-medium rounded-lg hover:bg-red-50 transition-colors">
                                            Deactivate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            const tabs = ['profile', 'password', 'account'];
            const tabButtons = tabs.map(tab => document.getElementById(`tab-${tab}`));
            const tabContents = tabs.map(tab => document.getElementById(`content-${tab}`));

            function showTab(index) {
                tabButtons.forEach((btn, i) => {
                    if (i === index) {
                        btn.classList.add('border-slate-900', 'text-slate-900');
                        btn.classList.remove('border-transparent', 'text-gray-500');
                    } else {
                        btn.classList.remove('border-slate-900', 'text-slate-900');
                        btn.classList.add('border-transparent', 'text-gray-500');
                    }
                });

                tabContents.forEach((content, i) => {
                    if (i === index) {
                        content.classList.remove('hidden');
                    } else {
                        content.classList.add('hidden');
                    }
                });
            }

            // Set up tab click handlers
            tabButtons.forEach((btn, i) => {
                btn.addEventListener('click', () => showTab(i));
            });

            // Profile image upload
            const imageUploadInput = document.getElementById('profile_image_upload');
            const imageUploadForm = document.getElementById('image-upload-form');

            imageUploadInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    // Submit form automatically when a file is selected
                    imageUploadForm.submit();
                }
            });

            // Password confirmation validation
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            function validatePasswords() {
                if (newPasswordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity("Passwords don't match");
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }

            newPasswordInput.addEventListener('input', validatePasswords);
            confirmPasswordInput.addEventListener('input', validatePasswords);
        });
    </script>
</body>

</html>