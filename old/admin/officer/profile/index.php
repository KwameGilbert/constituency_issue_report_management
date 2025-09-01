<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if (!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Set active page for sidebar
$active_page = 'profile';
$pageTitle = 'My Profile';
$basePath = '../';

// Get officer details
$officer_id = $_SESSION['officer_id'];
$query = "SELECT * FROM field_officers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$result = $stmt->get_result();
$officer = $result->fetch_assoc();

// Process profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Basic validation
    if (empty($name) || empty($email) || empty($phone)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update profile information
            $update_query = "UPDATE field_officers SET name = ?, email = ?, phone = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssi", $name, $email, $phone, $officer_id);
            $update_stmt->execute();
            
            // If password change is requested
            if (!empty($password) && !empty($new_password)) {
                // Verify current password
                $check_query = "SELECT password FROM field_officers WHERE id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("i", $officer_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $user_data = $check_result->fetch_assoc();
                
                if (password_verify($password, $user_data['password'])) {
                    // Check if new passwords match
                    if ($new_password === $confirm_password) {
                        // Hash the new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Update password
                        $password_query = "UPDATE field_officers SET password = ? WHERE id = ?";
                        $password_stmt = $conn->prepare($password_query);
                        $password_stmt->bind_param("si", $hashed_password, $officer_id);
                        $password_stmt->execute();
                    } else {
                        throw new Exception("New passwords do not match.");
                    }
                } else {
                    throw new Exception("Current password is incorrect.");
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Update session name
            $_SESSION['officer_name'] = $name;
            
            // Success message
            $success_message = "Profile updated successfully.";
            
            // Refresh officer data
            $stmt->execute();
            $result = $stmt->get_result();
            $officer = $result->fetch_assoc();
            
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Field Officer Dashboard</title>
     <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/main.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in-out forwards;
    }

    .staggered-item {
        opacity: 0;
        animation: fadeIn 0.5s ease-out forwards;
    }

    @keyframes slideInFromRight {
        from {
            transform: translateX(20px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .slide-in-right {
        animation: slideInFromRight 0.3s ease-out forwards;
    }

    /* Custom form input styling for better visibility */
    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"],
    textarea,
    select {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        width: 100%;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="tel"]:focus,
    textarea:focus,
    select:focus {
        outline: none;
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    }

    input:hover,
    textarea:hover,
    select:hover {
        border-color: #f59e0b !important;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar component -->
        <?php include_once '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header component -->
            <?php include_once '../includes/header.php'; ?>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Action Bar -->
                    <div
                        class="bg-gradient-to-r from-amber-600 to-amber-800 rounded-xl shadow-lg mb-6 p-6 text-white fade-in">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <h1 class="text-2xl font-bold">My Profile</h1>
                                <p class="mt-1 opacity-90">Manage your personal information and account settings</p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 fade-in"
                        role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 fade-in"
                        role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Profile Summary Card -->
                        <div class="md:col-span-1">
                            <div class="bg-white rounded-lg shadow-sm p-6 staggered-item"
                                style="animation-delay: 0.1s;">
                                <div class="flex flex-col items-center text-center">
                                    <div
                                        class="h-24 w-24 rounded-full bg-amber-600 flex items-center justify-center text-white text-3xl font-semibold">
                                        <?php echo strtoupper(substr($officer['name'], 0, 1)); ?>
                                    </div>
                                    <h3 class="mt-4 text-xl font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($officer['name']); ?>
                                    </h3>
                                    <p class="text-amber-600 font-medium">Field Officer</p>
                                    <div class="mt-4 text-gray-600 text-sm">
                                        <p class="mb-2">
                                            <i class="fas fa-envelope mr-2"></i>
                                            <?php echo htmlspecialchars($officer['email']); ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-phone mr-2"></i>
                                            <?php echo htmlspecialchars($officer['phone'] ?? 'Not provided'); ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-clock mr-2"></i> Member since
                                            <?php echo date('M Y', strtotime($officer['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="mt-6 border-t pt-4 w-full">
                                        <div class="flex justify-around">
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-amber-600">
                                                    <?php 
                                                    // Get total issues count
                                                    $issues_query = "SELECT COUNT(*) as total FROM issues WHERE officer_id = ?";
                                                    $issues_stmt = $conn->prepare($issues_query);
                                                    $issues_stmt->bind_param("i", $officer_id);
                                                    $issues_stmt->execute();
                                                    $issues_result = $issues_stmt->get_result();
                                                    $issues_count = $issues_result->fetch_assoc()['total'];
                                                    echo $issues_count;
                                                    ?>
                                                </p>
                                                <p class="text-sm text-gray-600">Total Issues</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-green-600">
                                                    <?php 
                                                    // Get resolved issues count
                                                    $resolved_query = "SELECT COUNT(*) as resolved FROM issues WHERE officer_id = ? AND status = 'resolved'";
                                                    $resolved_stmt = $conn->prepare($resolved_query);
                                                    $resolved_stmt->bind_param("i", $officer_id);
                                                    $resolved_stmt->execute();
                                                    $resolved_result = $resolved_stmt->get_result();
                                                    $resolved_count = $resolved_result->fetch_assoc()['resolved'];
                                                    echo $resolved_count;
                                                    ?>
                                                </p>
                                                <p class="text-sm text-gray-600">Resolved</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div class="bg-white rounded-lg shadow-sm p-6 mt-6 staggered-item"
                                style="animation-delay: 0.2s;">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                                <div class="flow-root">
                                    <ul class="-mb-8">
                                        <?php
                                        // Get recent activity
                                        $activity_query = "SELECT 'issue' as type, id, title, created_at 
                                                         FROM issues 
                                                         WHERE officer_id = ?
                                                         UNION
                                                         SELECT 'comment' as type, ic.issue_id as id, i.title, ic.created_at
                                                         FROM issue_comments ic
                                                         JOIN issues i ON ic.issue_id = i.id
                                                         WHERE ic.officer_id = ?
                                                         ORDER BY created_at DESC
                                                         LIMIT 5";
                                        $activity_stmt = $conn->prepare($activity_query);
                                        $activity_stmt->bind_param("ii", $officer_id, $officer_id);
                                        $activity_stmt->execute();
                                        $activity_result = $activity_stmt->get_result();
                                        
                                        if ($activity_result->num_rows === 0): 
                                        ?>
                                        <li class="text-center text-gray-500 py-4">
                                            <i class="fas fa-info-circle mb-2 text-xl"></i>
                                            <p>No recent activity</p>
                                        </li>
                                        <?php else: ?>
                                        <?php 
                                        $i = 0;
                                        while ($activity = $activity_result->fetch_assoc()): 
                                            $i++;
                                        ?>
                                        <li>
                                            <div class="relative pb-8">
                                                <?php if ($i < $activity_result->num_rows): ?>
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                                    aria-hidden="true"></span>
                                                <?php endif; ?>
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <?php if ($activity['type'] === 'issue'): ?>
                                                        <span
                                                            class="h-8 w-8 rounded-full bg-amber-500 flex items-center justify-center ring-8 ring-white">
                                                            <i class="fas fa-clipboard-list text-white text-sm"></i>
                                                        </span>
                                                        <?php else: ?>
                                                        <span
                                                            class="h-8 w-8 rounded-full bg-amber-400 flex items-center justify-center ring-8 ring-white">
                                                            <i class="fas fa-comment text-white text-sm"></i>
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                        <div>
                                                            <a href="../issue-detail/?id=<?php echo $activity['id']; ?>"
                                                                class="text-sm text-amber-700 hover:underline font-medium">
                                                                <?php echo htmlspecialchars($activity['title']); ?>
                                                            </a>
                                                            <p class="text-xs text-gray-500">
                                                                <?php if ($activity['type'] === 'issue'): ?>
                                                                Created a new issue
                                                                <?php else: ?>
                                                                Commented on issue
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                        <div class="text-right text-xs whitespace-nowrap text-gray-500">
                                                            <?php echo date('M d, Y', strtotime($activity['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <?php endwhile; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Edit Form -->
                        <div class="md:col-span-2">
                            <div class="bg-white rounded-lg shadow-sm p-6 staggered-item"
                                style="animation-delay: 0.3s;">
                                <h3 class="text-lg font-medium text-gray-900 mb-4" id="edit-profile">Edit Profile</h3>
                                <form action="" method="POST">
                                    <div class="space-y-4">
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full
                                                Name</label>
                                            <input type="text" name="name" id="name"
                                                value="<?php echo htmlspecialchars($officer['name']); ?>" required
                                                class="w-full rounded-md border-gray-300">
                                        </div>
                                        <div>
                                            <label for="email"
                                                class="block text-sm font-medium text-gray-700 mb-1">Email
                                                Address</label>
                                            <input type="email" name="email" id="email"
                                                value="<?php echo htmlspecialchars($officer['email']); ?>" required
                                                class="w-full rounded-md border-gray-300">
                                        </div>
                                        <div>
                                            <label for="phone"
                                                class="block text-sm font-medium text-gray-700 mb-1">Phone
                                                Number</label>
                                            <input type="tel" name="phone" id="phone"
                                                value="<?php echo htmlspecialchars($officer['phone'] ?? ''); ?>"
                                                class="w-full rounded-md border-gray-300">
                                        </div>
                                        <div class="border-t border-gray-200 pt-4 mt-4">
                                            <h4 class="text-md font-medium text-gray-900 mb-3" id="settings">Change
                                                Password</h4>
                                            <p class="text-sm text-gray-600 mb-4">Leave these fields blank if you don't
                                                want
                                                to change your password.</p>
                                            <div class="space-y-4">
                                                <div>
                                                    <label for="password"
                                                        class="block text-sm font-medium text-gray-700 mb-1">Current
                                                        Password</label>
                                                    <input type="password" name="password" id="password"
                                                        class="w-full rounded-md border-gray-300">
                                                </div>
                                                <div>
                                                    <label for="new_password"
                                                        class="block text-sm font-medium text-gray-700 mb-1">New
                                                        Password</label>
                                                    <input type="password" name="new_password" id="new_password"
                                                        class="w-full rounded-md border-gray-300">
                                                </div>
                                                <div>
                                                    <label for="confirm_password"
                                                        class="block text-sm font-medium text-gray-700 mb-1">Confirm New
                                                        Password</label>
                                                    <input type="password" name="confirm_password" id="confirm_password"
                                                        class="w-full rounded-md border-gray-300">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-6 flex items-center justify-end">
                                            <button type="submit" name="update_profile"
                                                class="bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-md font-medium transition-colors duration-300 flex items-center">
                                                <i class="fas fa-save mr-2"></i> Save Changes
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Account Settings -->
                            <div class="bg-white rounded-lg shadow-sm p-6 mt-6 staggered-item"
                                style="animation-delay: 0.4s;">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Account Settings</h3>
                                <div class="space-y-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-md font-medium text-gray-900">Email Notifications</h4>
                                            <p class="text-sm text-gray-600">Receive email updates about your account
                                                activities</p>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button"
                                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 transition-colors duration-200 ease-in-out focus:outline-none"
                                                role="switch" aria-checked="true" id="notification-toggle">
                                                <span class="sr-only">Toggle notifications</span>
                                                <span
                                                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-5"
                                                    id="toggle-dot"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="border-t border-gray-200 pt-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-md font-medium text-gray-900">Two-Factor Authentication
                                                </h4>
                                                <p class="text-sm text-gray-600">Add an extra layer of security to your
                                                    account</p>
                                            </div>
                                            <button type="button"
                                                class="bg-amber-100 text-amber-800 py-2 px-4 rounded-md text-sm font-medium hover:bg-amber-200 transition-colors duration-300">
                                                Set Up
                                            </button>
                                        </div>
                                    </div>
                                    <div class="border-t border-gray-200 pt-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-md font-medium text-gray-900">Account Data</h4>
                                                <p class="text-sm text-gray-600">Download all data associated with your
                                                    account</p>
                                            </div>
                                            <button type="button"
                                                class="bg-gray-100 text-gray-800 py-2 px-4 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors duration-300">
                                                Download
                                            </button>
                                        </div>
                                    </div>
                                    <div class="border-t border-gray-200 pt-4">
                                        <div>
                                            <h4 class="text-md font-medium text-red-600">Delete Account</h4>
                                            <p class="text-sm text-gray-600 mt-1">Once you delete your account, there is
                                                no going back. Please be certain.</p>
                                            <button type="button"
                                                class="mt-4 bg-white border border-red-600 text-red-600 py-2 px-4 rounded-md text-sm font-medium hover:bg-red-50 transition-colors duration-300">
                                                Delete Account
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation for staggered items
        document.querySelectorAll('.staggered-item').forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = "1";
            }, 100 * index);
        });

        // Email notification toggle
        const toggleButton = document.getElementById('notification-toggle');
        const toggleDot = document.getElementById('toggle-dot');
        let isToggled = true;

        toggleButton.addEventListener('click', function() {
            isToggled = !isToggled;

            if (isToggled) {
                toggleButton.classList.remove('bg-gray-200');
                toggleButton.classList.add('bg-amber-600');
                toggleDot.classList.add('translate-x-5');
                toggleDot.classList.remove('translate-x-0');
            } else {
                toggleButton.classList.remove('bg-amber-600');
                toggleButton.classList.add('bg-gray-200');
                toggleDot.classList.remove('translate-x-5');
                toggleDot.classList.add('translate-x-0');
            }
        });
    });
    </script>
</body>

</html>