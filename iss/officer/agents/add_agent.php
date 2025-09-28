<?php
// add_agent.php - Add new agent
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'agents';

// Initialize message variables
$message = '';
$message_type = '';
$form_data = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data for repopulating on error
    $form_data = $_POST;

    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $main_community_id = intval($_POST['main_community_id'] ?? 0);
    $smaller_community_id = intval($_POST['smaller_community_id'] ?? 0);
    $suburb_id = intval($_POST['suburb_id'] ?? 0);
    $cottage_id = intval($_POST['cottage_id'] ?? 0);
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

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if ($main_community_id <= 0) {
        $errors[] = "Please select a main community";
    }

    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email address already exists";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking email: " . $e->getMessage();
        }
    }

    // If no errors, create the agent
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new agent
            $stmt = $conn->prepare("
                INSERT INTO users (
                    name, email, password, role, phone, 
                    main_community_id, smaller_community_id, suburb_id, cottage_id,
                    department, status, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, 'agent', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )
            ");

            $stmt->execute([
                $name,
                $email,
                $password_hash,
                $phone,
                $main_community_id,
                $smaller_community_id,
                $suburb_id,
                $cottage_id,
                $department,
                $status
            ]);

            $agent_id = $conn->lastInsertId();

            // Log the activity
            $officer_id = $_SESSION['user_id'];
            $activity_description = "New agent '{$name}' created by officer";

            try {
                $stmt = $conn->prepare("
                    INSERT INTO activity_logs 
                        (user_id, action, details, created_at)
                    VALUES 
                        (?, 'agent_created', ?, NOW())
                ");
                $stmt->execute([$officer_id, $activity_description]);
            } catch (Exception $e) {
                // Log activity failure but don't fail the main operation
                error_log("Activity logging failed: " . $e->getMessage());
            }

            // Send welcome notification to the new agent
            try {
                $welcome_message = "Welcome to the SWMA system! Your agent account has been created. Your login email is: {$email}";
                $stmt = $conn->prepare("
                    INSERT INTO notifications 
                        (user_id, message, type, is_read, created_at)
                    VALUES 
                        (?, ?, 'welcome', FALSE, NOW())
                ");
                $stmt->execute([$agent_id, $welcome_message]);
            } catch (Exception $e) {
                // Log notification failure but don't fail the main operation
                error_log("Welcome notification failed: " . $e->getMessage());
            }

            $conn->commit();

            // Redirect to agent list with success message
            header('Location: ./?message=' . urlencode('Agent created successfully') . '&type=success');
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Error creating agent: " . $e->getMessage();
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

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Agents',
        'href' => './',
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
    <title>Add New Agent - Officer Dashboard</title>
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
    <?php renderOfficerSidebar($current_page, getPendingIssuesCount($conn)); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderOfficerHeader('Add New Agent', 'Create a new field agent account', $headerActionButtons); ?>

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
                        <h2 class="text-xl font-semibold text-gray-800">Agent Information</h2>
                        <p class="text-sm text-gray-600 mt-1">Fill in the details to create a new agent account</p>
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
                                    <!-- Main Community -->
                                    <div>
                                        <label for="main_community_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Main Community <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="main_community_id"
                                            name="main_community_id"
                                            required
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
                                                    data-suburb-id="<?php echo $cottage['suburb_id']; ?>"
                                                    <?php echo (isset($form_data['cottage_id']) && $form_data['cottage_id'] == $cottage['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cottage['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
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
                                            <option value="active" <?php echo (isset($form_data['status']) && $form_data['status'] === 'active') || !isset($form_data['status']) ? 'selected' : ''; ?>>Active</option>
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
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Password -->
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Password <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="password"
                                                id="password"
                                                name="password"
                                                required
                                                minlength="8"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors pr-10"
                                                placeholder="Enter password (min. 8 characters)">
                                            <button
                                                type="button"
                                                onclick="togglePasswordVisibility('password')"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-eye" id="password-eye"></i>
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Confirm Password <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="password"
                                                id="confirm_password"
                                                name="confirm_password"
                                                required
                                                minlength="8"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors pr-10"
                                                placeholder="Confirm password">
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

                            <!-- Information Note -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-blue-800">Important Information</h4>
                                        <div class="mt-1 text-sm text-blue-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>The agent will receive login credentials via email</li>
                                                <li>They can change their password after first login</li>
                                                <li>Make sure the community assignment is correct</li>
                                                <li>You can modify these details later if needed</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-100 mt-8">
                            <a
                                href="./"
                                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>

                            <button
                                type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-900 rounded-lg hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-900 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-user-plus mr-2"></i>Create Agent
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
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');

            // Real-time password matching validation
            function validatePasswords() {
                if (passwordField.value !== confirmPasswordField.value) {
                    confirmPasswordField.setCustomValidity("Passwords don't match");
                } else {
                    confirmPasswordField.setCustomValidity('');
                }
            }

            passwordField.addEventListener('input', validatePasswords);
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

            // Handle cascading location dropdowns
            const mainCommunitySelect = document.getElementById('main_community_id');
            const smallerCommunitySelect = document.getElementById('smaller_community_id');
            const suburbSelect = document.getElementById('suburb_id');
            const cottageSelect = document.getElementById('cottage_id');
            
            // Store all options for filtering
            const allSmallerCommunities = Array.from(smallerCommunitySelect.options);
            const allSuburbs = Array.from(suburbSelect.options);
            const allCottages = Array.from(cottageSelect.options);
            
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
            
            // Filter cottages based on selected suburb
            suburbSelect.addEventListener('change', function() {
                const selectedSuburbId = this.value;
                
                // Reset and populate cottages
                cottageSelect.innerHTML = '';
                cottageSelect.appendChild(new Option('Select Cottage', ''));
                
                if (selectedSuburbId) {
                    allCottages.forEach(option => {
                        if (option.value === '' || option.dataset.suburbId === selectedSuburbId) {
                            cottageSelect.appendChild(option.cloneNode(true));
                        }
                    });
                } else {
                    // If no suburb selected, show all options
                    allCottages.forEach(option => {
                        cottageSelect.appendChild(option.cloneNode(true));
                    });
                }
            });
        });
    </script>
</body>

</html>