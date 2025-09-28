<?php
// users/edit_user.php - Edit existing user
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'users';

// Initialize message variables
$message = '';
$message_type = '';

// Get user ID from query parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header('Location: ./index.php?message=Invalid+user+ID&type=error');
    exit;
}

// Prevent editing yourself (should be done in profile instead)
if ($user_id == $_SESSION['user_id']) {
    header('Location: ./view_user.php?id=' . $user_id . '&message=' . urlencode('Please use the profile page to edit your own account') . '&type=error');
    exit;
}

// Fetch user data
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
               mc.name as main_community_name,
               sc.name as smaller_community_name,
               sb.name as suburb_name,
               ct.name as cottage_name
        FROM users u
        LEFT JOIN communities mc ON u.main_community_id = mc.id
        LEFT JOIN smaller_communities sc ON u.smaller_community_id = sc.id
        LEFT JOIN suburbs sb ON u.suburb_id = sb.id
        LEFT JOIN cottages ct ON u.cottage_id = ct.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ./index.php?message=User+not+found&type=error');
        exit;
    }
    
    // Use existing data as default form data
    $form_data = $user;
    
} catch (Exception $e) {
    header('Location: ./index.php?message=' . urlencode('Error fetching user data: ' . $e->getMessage()) . '&type=error');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data for repopulating on error
    $form_data = array_merge($user, $_POST);

    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $main_community_id = intval($_POST['main_community_id'] ?? 0);
    $smaller_community_id = intval($_POST['smaller_community_id'] ?? 0);
    $suburb_id = intval($_POST['suburb_id'] ?? 0);
    $cottage_id = intval($_POST['cottage_id'] ?? 0);
    $department = trim($_POST['department'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validation
    $errors = [];

    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($role)) {
        $errors[] = "Role is required";
    }

    // Password validation (only if changing password)
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }

    // Additional validations based on role
    if (($role === 'agent' || $role === 'officer') && $main_community_id <= 0) {
        $errors[] = "Main community is required for agents and officers";
    }

    // Check if email already exists (excluding current user)
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "Email address already exists";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking email: " . $e->getMessage();
        }
    }

    // If no errors, update the user
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Prepare the base update query
            $sql = "
                UPDATE users SET 
                    name = ?, email = ?, role = ?, phone = ?, 
                    main_community_id = ?, smaller_community_id = ?, suburb_id = ?, cottage_id = ?,
                    department = ?, status = ?, updated_at = NOW()
            ";
            
            $params = [
                $name,
                $email,
                $role,
                $phone,
                $main_community_id ?: null,
                $smaller_community_id ?: null,
                $suburb_id ?: null,
                $cottage_id ?: null,
                $department,
                $status
            ];
            
            // If password is being changed, update it
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                $params[] = $password_hash;
            }
            
            // Complete the query
            $sql .= " WHERE id = ?";
            $params[] = $user_id;
            
            // Execute the update
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Log the activity
            $admin_id = $_SESSION['user_id'];
            
            // Track changes for activity log
            $changes = [];
            if ($user['name'] !== $name) $changes[] = "name";
            if ($user['email'] !== $email) $changes[] = "email";
            if ($user['role'] !== $role) $changes[] = "role";
            if ($user['phone'] !== $phone) $changes[] = "phone";
            if ($user['main_community_id'] != $main_community_id) $changes[] = "main community";
            if ($user['smaller_community_id'] != $smaller_community_id) $changes[] = "smaller community";
            if ($user['suburb_id'] != $suburb_id) $changes[] = "suburb";
            if ($user['cottage_id'] != $cottage_id) $changes[] = "cottage";
            if ($user['department'] !== $department) $changes[] = "department";
            if ($user['status'] !== $status) $changes[] = "status";
            if (!empty($new_password)) $changes[] = "password";
            
            $activity_description = "User '{$name}' (ID: {$user_id}) was updated. Changed: " . implode(', ', $changes);

            try {
                $stmt = $conn->prepare("
                    INSERT INTO activity_logs 
                        (user_id, action, details, created_at)
                    VALUES 
                        (?, 'user_updated', ?, NOW())
                ");
                $stmt->execute([$admin_id, $activity_description]);
            } catch (Exception $e) {
                // Log activity failure but don't fail the main operation
                error_log("Activity logging failed: " . $e->getMessage());
            }

            // If status changed to inactive, log them out
            if ($user['status'] === 'active' && $status === 'inactive') {
                // Add a notification about account deactivation
                try {
                    $notification_message = "Your account has been deactivated. Please contact administration for assistance.";
                    $stmt = $conn->prepare("
                        INSERT INTO notifications 
                            (user_id, message, type, is_read, created_at)
                        VALUES 
                            (?, ?, 'account_status', FALSE, NOW())
                    ");
                    $stmt->execute([$user_id, $notification_message]);
                } catch (Exception $e) {
                    error_log("Account deactivation notification failed: " . $e->getMessage());
                }
            }

            $conn->commit();

            // Redirect to user view with success message
            header('Location: ./view_user.php?id=' . $user_id . '&message=' . urlencode('User updated successfully') . '&type=success');
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Error updating user: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Fetch location data for dropdowns
try {
    // Main communities
    $stmt = $conn->prepare("SELECT id, name FROM communities ORDER BY name");
    $stmt->execute();
    $main_communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Smaller communities
    $stmt = $conn->prepare("SELECT id, name FROM smaller_communities ORDER BY name");
    $stmt->execute();
    $smaller_communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Suburbs
    $stmt = $conn->prepare("SELECT id, name, community_id FROM suburbs ORDER BY name");
    $stmt->execute();
    $suburbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cottages
    $stmt = $conn->prepare("SELECT id, name, smaller_community_id FROM cottages ORDER BY name");
    $stmt->execute();
    $cottages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $main_communities = [];
    $smaller_communities = [];
    $suburbs = [];
    $cottages = [];
    if (empty($message)) {
        $message = "Error loading location data: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Role options
$role_options = [
    'agent' => 'Field Agent',
    'officer' => 'Constituency Officer',
    'pa' => 'Personal Assistant',
    'mce' => 'Municipal Chief Executive',
    'mp' => 'Member of Parliament'
];

// Only admin can create/edit admin users
if ($_SESSION['user_role'] === 'admin') {
    $role_options['admin'] = 'System Administrator';
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-eye',
        'label' => 'View User',
        'href' => './view_user.php?id=' . $user_id,
        'class' => 'bg-indigo-900 text-white hover:bg-indigo-800'
    ],
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Users',
        'href' => './',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

$userName = $_SESSION['user_name'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit User - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
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
    <?php renderAdminSidebar($current_page); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAdminHeader('Edit User', htmlspecialchars($user['name']), $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Form Header -->
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-semibold text-gray-800">Edit User Information</h2>
                        <p class="text-sm text-gray-600 mt-1">Update user account details</p>
                    </div>

                    <!-- Form Body -->
                    <form method="POST" action="" class="p-6">
                        <div class="space-y-6">
                            <!-- Personal Information Section -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-user text-indigo-900 mr-2"></i>
                                    Personal Information
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Full Name -->
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Full Name <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>"
                                            required
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors"
                                            placeholder="Enter full name">
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Email Address <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                                            required
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors"
                                            placeholder="Enter email address">
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                            Phone Number
                                        </label>
                                        <input
                                            type="tel"
                                            id="phone"
                                            name="phone"
                                            value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors"
                                            placeholder="e.g., +233 20 123 4567">
                                    </div>

                                    <!-- Department -->
                                    <div>
                                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
                                            Department
                                        </label>
                                        <input
                                            type="text"
                                            id="department"
                                            name="department"
                                            value="<?php echo htmlspecialchars($form_data['department'] ?? ''); ?>"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors"
                                            placeholder="e.g., Community Relations">
                                    </div>

                                    <!-- User Role -->
                                    <div>
                                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                            User Role <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="role"
                                            name="role"
                                            required
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors">
                                            <option value="">Select Role</option>
                                            <?php foreach ($role_options as $value => $label) : ?>
                                                <option
                                                    value="<?php echo $value; ?>"
                                                    <?php echo (isset($form_data['role']) && $form_data['role'] == $value) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- User Status -->
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                            Account Status
                                        </label>
                                        <select
                                            id="status"
                                            name="status"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors">
                                            <option value="active" <?php echo (isset($form_data['status']) && $form_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo (isset($form_data['status']) && $form_data['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Information Section -->
                            <div id="locationSection">
                                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-map-marker-alt text-indigo-900 mr-2"></i>
                                    Location Assignment
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Main Community -->
                                    <div>
                                        <label for="main_community_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Main Community <span class="text-red-500 location-required hidden">*</span>
                                        </label>
                                        <select
                                            id="main_community_id"
                                            name="main_community_id"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors">
                                            <option value="">Select Main Community</option>
                                            <?php foreach ($main_communities as $community) : ?>
                                                <option
                                                    value="<?php echo $community['id']; ?>"
                                                    <?php echo (isset($form_data['main_community_id']) && $form_data['main_community_id'] == $community['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($community['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Smaller Community -->
                                    <div>
                                        <label for="smaller_community_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Smaller Community
                                        </label>
                                        <select
                                            id="smaller_community_id"
                                            name="smaller_community_id"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors">
                                            <option value="">Select Smaller Community</option>
                                            <?php foreach ($smaller_communities as $community) : ?>
                                                <option
                                                    value="<?php echo $community['id']; ?>"
                                                    <?php echo (isset($form_data['smaller_community_id']) && $form_data['smaller_community_id'] == $community['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($community['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Suburb -->
                                    <div>
                                        <label for="suburb_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Suburb
                                        </label>
                                        <select
                                            id="suburb_id"
                                            name="suburb_id"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors">
                                            <option value="">Select Suburb</option>
                                            <?php foreach ($suburbs as $suburb) : ?>
                                                <option
                                                    value="<?php echo $suburb['id']; ?>"
                                                    data-community-id="<?php echo $suburb['community_id']; ?>"
                                                    <?php echo (isset($form_data['suburb_id']) && $form_data['suburb_id'] == $suburb['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($suburb['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Cottage -->
                                    <div>
                                        <label for="cottage_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Cottage
                                        </label>
                                        <select
                                            id="cottage_id"
                                            name="cottage_id"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors">
                                            <option value="">Select Cottage</option>
                                            <?php foreach ($cottages as $cottage) : ?>
                                                <option
                                                    value="<?php echo $cottage['id']; ?>"
                                                    data-smaller-community-id="<?php echo $cottage['smaller_community_id']; ?>"
                                                    <?php echo (isset($form_data['cottage_id']) && $form_data['cottage_id'] == $cottage['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cottage['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Information Section -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-lock text-indigo-900 mr-2"></i>
                                    Change Password
                                    <span class="ml-2 text-xs font-normal text-gray-500">(Leave blank to keep current password)</span>
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- New Password -->
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            New Password
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="password"
                                                id="new_password"
                                                name="new_password"
                                                minlength="8"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors pr-10"
                                                placeholder="Enter new password">
                                            <button
                                                type="button"
                                                onclick="togglePasswordVisibility('new_password')"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-eye" id="new_password-eye"></i>
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                                    </div>

                                    <!-- Confirm New Password -->
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Confirm New Password
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="password"
                                                id="confirm_password"
                                                name="confirm_password"
                                                minlength="8"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors pr-10"
                                                placeholder="Confirm new password">
                                            <button
                                                type="button"
                                                onclick="togglePasswordVisibility('confirm_password')"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-eye" id="confirm_password-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Creation Information -->
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-800 mb-3">Account Information</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Account Created:</span>
                                        <span class="text-gray-800 ml-2"><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Last Updated:</span>
                                        <span class="text-gray-800 ml-2"><?php echo date('M d, Y H:i', strtotime($user['updated_at'])); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Last Login:</span>
                                        <span class="text-gray-800 ml-2">
                                            <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-100 mt-8">
                            <a
                                href="./view_user.php?id=<?php echo $user_id; ?>"
                                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>

                            <button
                                type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-900 rounded-lg hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-900 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-save mr-2"></i>Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Toggle password visibility
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(fieldId + '-eye');

            if (field.type === 'password') {
                field.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const newPasswordField = document.getElementById('new_password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const roleField = document.getElementById('role');
            const locationSection = document.getElementById('locationSection');
            const mainCommunitySelect = document.getElementById('main_community_id');
            const locationRequired = document.querySelectorAll('.location-required');
            const smallerCommunitySelect = document.getElementById('smaller_community_id');
            const suburbSelect = document.getElementById('suburb_id');
            const cottageSelect = document.getElementById('cottage_id');
            
            // Store all options for filtering
            const allSuburbs = Array.from(suburbSelect.options);
            const allCottages = Array.from(cottageSelect.options);

            // Toggle location requirement based on role
            function updateLocationRequirement() {
                const role = roleField.value;
                if (role === 'agent' || role === 'officer') {
                    locationSection.classList.remove('opacity-50', 'pointer-events-none');
                    mainCommunitySelect.setAttribute('required', 'required');
                    locationRequired.forEach(el => el.classList.remove('hidden'));
                } else {
                    locationSection.classList.add('opacity-50', 'pointer-events-none');
                    mainCommunitySelect.removeAttribute('required');
                    locationRequired.forEach(el => el.classList.add('hidden'));
                }
            }

            // Real-time password matching validation
            function validatePasswords() {
                if (newPasswordField.value || confirmPasswordField.value) {
                    if (newPasswordField.value !== confirmPasswordField.value) {
                        confirmPasswordField.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPasswordField.setCustomValidity('');
                    }
                } else {
                    confirmPasswordField.setCustomValidity('');
                }
            }

            // Filter suburbs based on selected main community
            mainCommunitySelect.addEventListener('change', function() {
                const selectedMainCommunityId = this.value;
                
                // Reset subsequent dropdowns
                smallerCommunitySelect.value = '';
                suburbSelect.innerHTML = '';
                suburbSelect.appendChild(new Option('Select Suburb', ''));
                cottageSelect.innerHTML = '<option value="">Select Cottage</option>';
                
                // Populate suburbs based on the selected main community
                if (selectedMainCommunityId) {
                    allSuburbs.forEach(option => {
                        if (option.value === '' || option.dataset.communityId === selectedMainCommunityId) {
                            suburbSelect.appendChild(option.cloneNode(true));
                        }
                    });
                } else {
                    // If no main community selected, show all suburbs
                    allSuburbs.forEach(option => {
                        suburbSelect.appendChild(option.cloneNode(true));
                    });
                }
            });
            
            // Filter cottages based on selected smaller community
            smallerCommunitySelect.addEventListener('change', function() {
                const selectedSmallerCommunityId = this.value;
                
                // Reset and populate cottages
                cottageSelect.innerHTML = '';
                cottageSelect.appendChild(new Option('Select Cottage', ''));
                
                if (selectedSmallerCommunityId) {
                    allCottages.forEach(option => {
                        if (option.value === '' || option.dataset.smallerCommunityId === selectedSmallerCommunityId) {
                            cottageSelect.appendChild(option.cloneNode(true));
                        }
                    });
                } else {
                    // If no smaller community selected, show all options
                    allCottages.forEach(option => {
                        cottageSelect.appendChild(option.cloneNode(true));
                    });
                }
            });

            // Set up event listeners
            newPasswordField.addEventListener('input', validatePasswords);
            confirmPasswordField.addEventListener('input', validatePasswords);
            roleField.addEventListener('change', updateLocationRequirement);

            // Initial setup
            updateLocationRequirement();

            // Form submission validation
            form.addEventListener('submit', function(e) {
                validatePasswords();

                if (!form.checkValidity()) {
                    e.preventDefault();

                    // Find first invalid field and focus it
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }
            });
        });
    </script>
</body>

</html>
