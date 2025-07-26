<?php
// edit_agent.php - Edit agent details
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'agents';

// Get agent ID from URL parameter
$agent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize message variables
$message = '';
$message_type = '';
$form_data = [];
$agent = null;

// Check if valid agent ID was provided
if ($agent_id <= 0) {
    header('Location: ./?message=' . urlencode('Invalid agent ID') . '&type=error');
    exit;
}

// Fetch existing agent data
try {
    $stmt = $conn->prepare("
        SELECT u.*, ea.name as electoral_area_name 
        FROM users u
        LEFT JOIN electoral_areas ea ON u.electoral_area = ea.id
        WHERE u.id = ? AND u.role = 'agent'
    ");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        header('Location: ./?message=' . urlencode('Agent not found') . '&type=error');
        exit;
    }

    // Use existing data as default form data
    $form_data = $agent;
} catch (Exception $e) {
    header('Location: ./?message=' . urlencode('Error loading agent data: ' . $e->getMessage()) . '&type=error');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data for repopulating on error
    $form_data = array_merge($agent, $_POST);

    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $electoral_area = intval($_POST['electoral_area'] ?? 0);
    $department = trim($_POST['department'] ?? '');
    $status = $_POST['status'] ?? 'active';

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

    // Password validation (only if new password is provided)
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    }

    if ($electoral_area <= 0) {
        $errors[] = "Please select an electoral area";
    }

    // Check if email already exists (excluding current agent)
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $agent_id]);
            if ($stmt->fetch()) {
                $errors[] = "Email address already exists";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking email: " . $e->getMessage();
        }
    }

    // If no errors, update the agent
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
                        electoral_area = ?, department = ?, status = ?, updated_at = NOW()
                    WHERE id = ? AND role = 'agent'
                ");

                $stmt->execute([
                    $name,
                    $email,
                    $password_hash,
                    $phone,
                    $electoral_area,
                    $department,
                    $status,
                    $agent_id
                ]);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("
                    UPDATE users SET 
                        name = ?, email = ?, phone = ?, 
                        electoral_area = ?, department = ?, status = ?, updated_at = NOW()
                    WHERE id = ? AND role = 'agent'
                ");

                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $electoral_area,
                    $department,
                    $status,
                    $agent_id
                ]);
            }

            // Check if any rows were affected
            if ($stmt->rowCount() === 0) {
                throw new Exception('No changes were made or agent not found');
            }

            // Log the activity
            $officer_id = $_SESSION['user_id'];
            $changes = [];

            // Track what changed
            if ($agent['name'] !== $name) $changes[] = "name";
            if ($agent['email'] !== $email) $changes[] = "email";
            if ($agent['phone'] !== $phone) $changes[] = "phone";
            if ($agent['electoral_area'] != $electoral_area) $changes[] = "electoral area";
            if ($agent['department'] !== $department) $changes[] = "department";
            if ($agent['status'] !== $status) $changes[] = "status";
            if (!empty($new_password)) $changes[] = "password";

            $activity_description = "Agent '{$name}' updated by officer. Changed: " . implode(', ', $changes);

            try {
                $stmt = $conn->prepare("
                    INSERT INTO activity_logs 
                        (user_id, action, details, created_at)
                    VALUES 
                        (?, 'agent_updated', ?, NOW())
                ");
                $stmt->execute([$officer_id, $activity_description]);
            } catch (Exception $e) {
                error_log("Activity logging failed: " . $e->getMessage());
            }

            // Send notification to agent about profile update
            try {
                $notification_message = "Your profile has been updated by an officer. Please review your account details.";
                if (!empty($new_password)) {
                    $notification_message .= " Your password has been changed.";
                }

                $stmt = $conn->prepare("
                    INSERT INTO notifications 
                        (user_id, message, type, is_read, created_at)
                    VALUES 
                        (?, ?, 'profile_update', FALSE, NOW())
                ");
                $stmt->execute([$agent_id, $notification_message]);
            } catch (Exception $e) {
                error_log("Agent notification failed: " . $e->getMessage());
            }

            $conn->commit();

            // Redirect with success message
            header('Location: view_agent.php?id=' . $agent_id . '&message=' . urlencode('Agent updated successfully') . '&type=success');
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Error updating agent: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Fetch electoral areas for dropdown
try {
    $stmt = $conn->prepare("SELECT id, name, constituency, region FROM electoral_areas ORDER BY name");
    $stmt->execute();
    $electoral_areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $electoral_areas = [];
    if (empty($message)) {
        $message = "Error loading electoral areas: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Agent',
        'href' => 'view_agent.php?id=' . $agent_id,
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
    <title>Edit Agent - Officer Dashboard</title>
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
        <?php renderOfficerHeader('Edit Agent: ' . htmlspecialchars($agent['name']), 'Update agent information and settings', $headerActionButtons); ?>

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
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800">Agent Information</h2>
                                <p class="text-sm text-gray-600 mt-1">Update agent details and settings</p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Agent ID: <?php echo $agent_id; ?></div>
                                <div class="text-xs text-gray-500">Created: <?php echo date('M d, Y', strtotime($agent['created_at'])); ?></div>
                            </div>
                        </div>
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
                                </div>
                            </div>

                            <!-- Assignment Information Section -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-map-marker-alt text-indigo-900 mr-2"></i>
                                    Assignment Information
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Electoral Area -->
                                    <div>
                                        <label for="electoral_area" class="block text-sm font-medium text-gray-700 mb-2">
                                            Electoral Area <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="electoral_area"
                                            name="electoral_area"
                                            required
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors">
                                            <option value="">Select Electoral Area</option>
                                            <?php foreach ($electoral_areas as $area) : ?>
                                                <option
                                                    value="<?php echo $area['id']; ?>"
                                                    <?php echo (isset($form_data['electoral_area']) && $form_data['electoral_area'] == $area['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($area['name'] . ' - ' . $area['constituency'] . ', ' . $area['region']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($agent['electoral_area_name'])) : ?>
                                            <p class="text-xs text-gray-500 mt-1">Current: <?php echo htmlspecialchars($agent['electoral_area_name']); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Status -->
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

                            <!-- Security Information Section -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-lock text-indigo-900 mr-2"></i>
                                    Security Information
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
                                                placeholder="Enter new password (optional)">
                                            <button
                                                type="button"
                                                onclick="togglePasswordVisibility('new_password')"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-eye" id="new_password-eye"></i>
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
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
                                        <span class="text-gray-500">Created:</span>
                                        <span class="text-gray-800 ml-2"><?php echo date('M d, Y H:i', strtotime($agent['created_at'])); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Last Updated:</span>
                                        <span class="text-gray-800 ml-2"><?php echo date('M d, Y H:i', strtotime($agent['updated_at'])); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Last Login:</span>
                                        <span class="text-gray-800 ml-2">
                                            <?php echo $agent['last_login'] ? date('M d, Y H:i', strtotime($agent['last_login'])) : 'Never'; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Current Status:</span>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full <?php echo $agent['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($agent['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Warning Note -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-yellow-800">Important Notes</h4>
                                        <div class="mt-1 text-sm text-yellow-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>The agent will be notified of any profile changes</li>
                                                <li>If you change the password, the agent will be informed</li>
                                                <li>Changing status to inactive will prevent login</li>
                                                <li>Electoral area changes affect issue assignment</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-100 mt-8">
                            <a
                                href="view_agent.php?id=<?php echo $agent_id; ?>"
                                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>

                            <button
                                type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-900 rounded-lg hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-900 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-save mr-2"></i>Update Agent
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

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const newPasswordField = document.getElementById('new_password');
            const confirmPasswordField = document.getElementById('confirm_password');

            // Real-time password matching validation
            function validatePasswords() {
                const newPassword = newPasswordField.value;
                const confirmPassword = confirmPasswordField.value;

                // Only validate if new password is entered
                if (newPassword || confirmPassword) {
                    if (newPassword !== confirmPassword) {
                        confirmPasswordField.setCustomValidity("Passwords don't match");
                    } else if (newPassword.length > 0 && newPassword.length < 8) {
                        newPasswordField.setCustomValidity("Password must be at least 8 characters long");
                    } else {
                        newPasswordField.setCustomValidity('');
                        confirmPasswordField.setCustomValidity('');
                    }
                } else {
                    // Clear any previous validation messages
                    newPasswordField.setCustomValidity('');
                    confirmPasswordField.setCustomValidity('');
                }
            }

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