<?php
// profile/index.php - Officer Profile Management
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'profile';
$officer_id = $_SESSION['user_id'];

// Initialize message variables
$message = '';
$message_type = '';
$form_data = [];

// Fetch current officer data
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
        WHERE u.id = ? AND u.role = 'officer'
    ");
    $stmt->execute([$officer_id]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$officer) {
        header('Location: ../login/logout.php');
        exit;
    }

    // Use existing data as default form data
    $form_data = $officer;
} catch (Exception $e) {
    $message = "Error loading profile data: " . $e->getMessage();
    $message_type = 'error';
    $officer = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data for repopulating on error
    $form_data = array_merge($officer, $_POST);

    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $current_password = trim($_POST['current_password'] ?? '');
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

    // Password validation (only if changing password)
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to set a new password";
        } elseif (!password_verify($current_password, $officer['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }

    // Check if email already exists (excluding current officer)
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $officer_id]);
            if ($stmt->fetch()) {
                $errors[] = "Email address already exists";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking email: " . $e->getMessage();
        }
    }

    // If no errors, update the profile
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Prepare update query
            if (!empty($new_password)) {
                // Update with new password
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE users SET 
                        name = ?, email = ?, password = ?, phone = ?, 
                        department = ?, updated_at = NOW()
                    WHERE id = ? AND role = 'officer'
                ");

                $stmt->execute([
                    $name,
                    $email,
                    $password_hash,
                    $phone,
                    $department,
                    $officer_id
                ]);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("
                    UPDATE users SET 
                        name = ?, email = ?, phone = ?, 
                        department = ?, updated_at = NOW()
                    WHERE id = ? AND role = 'officer'
                ");

                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $department,
                    $officer_id
                ]);
            }

            // Check if any rows were affected
            if ($stmt->rowCount() === 0) {
                throw new Exception('No changes were made');
            }

            // Log the activity
            try {
                $changes = [];
                if ($officer['name'] !== $name) $changes[] = "name";
                if ($officer['email'] !== $email) $changes[] = "email";
                if ($officer['phone'] !== $phone) $changes[] = "phone";
                if ($officer['department'] !== $department) $changes[] = "department";
                if (!empty($new_password)) $changes[] = "password";

                $activity_description = "Profile updated. Changed: " . implode(', ', $changes);

                $stmt = $conn->prepare("
                    INSERT INTO activity_logs 
                        (user_id, action, details, created_at)
                    VALUES 
                        (?, 'profile_updated', ?, NOW())
                ");
                $stmt->execute([$officer_id, $activity_description]);
            } catch (Exception $e) {
                error_log("Activity logging failed: " . $e->getMessage());
            }

            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            $conn->commit();

            $message = "Profile updated successfully";
            $message_type = 'success';

            // Refresh officer data
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
                WHERE u.id = ? AND u.role = 'officer'
            ");
            $stmt->execute([$officer_id]);
            $officer = $stmt->fetch(PDO::FETCH_ASSOC);
            $form_data = $officer;
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Get profile statistics
try {
    // Issues statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_issues,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues
        FROM issues 
        WHERE officer_id = ?
    ");
    $stmt->execute([$officer_id]);
    $issue_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Agent count
    $stmt = $conn->prepare("SELECT COUNT(*) as agent_count FROM users WHERE role = 'agent' AND status = 'active'");
    $stmt->execute();
    $agent_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $issue_stats = ['total_issues' => 0, 'pending_issues' => 0, 'resolved_issues' => 0];
    $agent_stats = ['agent_count' => 0];
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Dashboard',
        'href' => '../dashboard/',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

$userName = $_SESSION['user_name'] ?? 'Officer';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile Settings - Officer Dashboard</title>
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
    <?php renderOfficerSidebar($current_page, getPendingIssuesCount($conn)); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderOfficerHeader('Profile Settings', 'Manage your account information and preferences', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Overview -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <?php if (!empty($officer['profile_image'])) : ?>
                                        <img src="../../<?php echo htmlspecialchars($officer['profile_image']); ?>" alt="Profile Image" class="w-full h-full rounded-full object-cover">
                                    <?php else : ?>
                                        <i class="fas fa-user-shield text-indigo-700 text-2xl"></i>
                                    <?php endif; ?>
                                </div>

                                <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($officer['name'] ?? 'Officer'); ?></h2>
                                <p class="text-sm text-gray-500">Officer</p>
                                <div class="mt-2">
                                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo (!empty($officer['status']) && $officer['status'] === 'active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($officer['status'] ?? 'inactive'); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Email:</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($officer['email'] ?? ''); ?></span>
                                </div>

                                <?php if (!empty($officer['phone'])) : ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Phone:</span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($officer['phone']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($officer['department'])) : ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Department:</span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($officer['department']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($officer['main_community_name'])) : ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Main Community:</span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($officer['main_community_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($officer['smaller_community_name'])) : ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Smaller Community:</span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($officer['smaller_community_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($officer['suburb_name'])) : ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Suburb:</span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($officer['suburb_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($officer['cottage_name'])) : ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Cottage:</span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($officer['cottage_name']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex justify-between">
                                    <span class="text-gray-500">Member Since:</span>
                                    <span class="text-gray-900"><?php echo !empty($officer['created_at']) ? date('M Y', strtotime($officer['created_at'])) : 'N/A'; ?></span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-500">Last Login:</span>
                                    <span class="text-gray-900">
                                        <?php echo !empty($officer['last_login']) ? date('M d, Y', strtotime($officer['last_login'])) : 'Never'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">Activity Overview</h3>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-file-alt text-blue-600 text-sm"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Total Issues</span>
                                    </div>
                                    <span class="text-lg font-semibold text-gray-800"><?php echo number_format($issue_stats['total_issues']); ?></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-clock text-yellow-600 text-sm"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Pending Review</span>
                                    </div>
                                    <span class="text-lg font-semibold text-gray-800"><?php echo number_format($issue_stats['pending_issues']); ?></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-check-circle text-green-600 text-sm"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Resolved</span>
                                    </div>
                                    <span class="text-lg font-semibold text-gray-800"><?php echo number_format($issue_stats['resolved_issues']); ?></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-users text-purple-600 text-sm"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Active Agents</span>
                                    </div>
                                    <span class="text-lg font-semibold text-gray-800"><?php echo number_format($agent_stats['agent_count']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Settings Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-xl font-semibold text-gray-800">Update Profile Information</h2>
                            <p class="text-sm text-gray-600 mt-1">Keep your profile information up to date</p>
                        </div>

                        <form method="POST" action="" class="p-6">
                            <div class="space-y-6">
                                <!-- Personal Information -->
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
                                                placeholder="e.g., Operations">
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Information -->
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-map-marker-alt text-indigo-900 mr-2"></i>
                                        Location Information
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Main Community</label>
                                            <div class="px-3 py-2.5 text-sm border border-gray-200 bg-gray-50 rounded-lg text-gray-600">
                                                <?php echo htmlspecialchars($officer['main_community_name'] ?? 'Not Assigned'); ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Smaller Community</label>
                                            <div class="px-3 py-2.5 text-sm border border-gray-200 bg-gray-50 rounded-lg text-gray-600">
                                                <?php echo htmlspecialchars($officer['smaller_community_name'] ?? 'Not Assigned'); ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Suburb</label>
                                            <div class="px-3 py-2.5 text-sm border border-gray-200 bg-gray-50 rounded-lg text-gray-600">
                                                <?php echo htmlspecialchars($officer['suburb_name'] ?? 'Not Assigned'); ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Cottage</label>
                                            <div class="px-3 py-2.5 text-sm border border-gray-200 bg-gray-50 rounded-lg text-gray-600">
                                                <?php echo htmlspecialchars($officer['cottage_name'] ?? 'Not Assigned'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Location information can only be updated by an administrator.
                                    </p>
                                </div>

                                <!-- Security Information -->
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-lock text-indigo-900 mr-2"></i>
                                        Change Password
                                        <span class="ml-2 text-xs font-normal text-gray-500">(Leave blank to keep current password)</span>
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Current Password -->
                                        <div>
                                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                                Current Password
                                            </label>
                                            <div class="relative">
                                                <input
                                                    type="password"
                                                    id="current_password"
                                                    name="current_password"
                                                    class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors pr-10"
                                                    placeholder="Enter current password">
                                                <button
                                                    type="button"
                                                    onclick="togglePasswordVisibility('current_password')"
                                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                    <i class="fas fa-eye" id="current_password-eye"></i>
                                                </button>
                                            </div>
                                        </div>

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

                                <!-- Account Information -->
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-800 mb-3">Account Information</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Account Created:</span>
                                            <span class="text-gray-800 ml-2"><?php echo !empty($officer['created_at']) ? date('M d, Y H:i', strtotime($officer['created_at'])) : 'N/A'; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Last Updated:</span>
                                            <span class="text-gray-800 ml-2"><?php echo !empty($officer['updated_at']) ? date('M d, Y H:i', strtotime($officer['updated_at'])) : 'N/A'; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Account Status:</span>
                                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full <?php echo (!empty($officer['status']) && $officer['status'] === 'active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($officer['status'] ?? 'inactive'); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">User Role:</span>
                                            <span class="text-gray-800 ml-2">Officer</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end pt-6 border-t border-gray-100 mt-8">
                                <button
                                    type="submit"
                                    class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-900 rounded-lg hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-900 focus:ring-offset-2 transition-colors">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
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

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const currentPasswordField = document.getElementById('current_password');
            const newPasswordField = document.getElementById('new_password');
            const confirmPasswordField = document.getElementById('confirm_password');

            // Real-time password validation
            function validatePasswords() {
                const currentPassword = currentPasswordField.value;
                const newPassword = newPasswordField.value;
                const confirmPassword = confirmPasswordField.value;

                // Clear previous validation messages
                newPasswordField.setCustomValidity('');
                confirmPasswordField.setCustomValidity('');

                // Only validate if trying to change password
                if (newPassword || confirmPassword || currentPassword) {
                    if (newPassword && !currentPassword) {
                        currentPasswordField.setCustomValidity('Current password is required to set a new password');
                    } else {
                        currentPasswordField.setCustomValidity('');
                    }

                    if (newPassword && newPassword.length > 0 && newPassword.length < 8) {
                        newPasswordField.setCustomValidity('Password must be at least 8 characters long');
                    }

                    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                        confirmPasswordField.setCustomValidity("Passwords don't match");
                    }
                } else {
                    // Clear all validation if no password fields are filled
                    currentPasswordField.setCustomValidity('');
                }
            }

            currentPasswordField.addEventListener('input', validatePasswords);
            newPasswordField.addEventListener('input', validatePasswords);
            confirmPasswordField.addEventListener('input', validatePasswords);

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