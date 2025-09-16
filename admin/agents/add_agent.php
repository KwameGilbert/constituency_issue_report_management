<?php
// admin/agents/add_agent.php - Add New Agent
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'agents';

// Initialize variables
$message = '';
$message_type = '';
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'main_community_id' => '',
    'smaller_community_id' => '',
    'suburb_id' => '',
    'cottage_id' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $main_community_id = $_POST['main_community_id'] ?? '';
    $smaller_community_id = $_POST['smaller_community_id'] ?? '';
    $suburb_id = $_POST['suburb_id'] ?? '';
    $cottage_id = $_POST['cottage_id'] ?? '';

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

    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }

    if (empty($main_community_id)) {
        $errors[] = "Main community is required";
    }

    if (empty($errors)) {
        try {
            // Check if email already exists
            $check_sql = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$email]);
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = "Email already exists";
            } else {
                // Generate a temporary password (phone number)
                $temp_password = password_hash($phone, PASSWORD_DEFAULT);
                
                // Insert new agent
                $insert_sql = "INSERT INTO users (name, email, phone, password, role, main_community_id, smaller_community_id, suburb_id, cottage_id, status, created_at) 
                              VALUES (?, ?, ?, ?, 'agent', ?, ?, ?, ?, 'active', NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->execute([$name, $email, $phone, $temp_password, $main_community_id, $smaller_community_id, $suburb_id, $cottage_id]);
                
                if ($insert_stmt->rowCount() > 0) {
                    $message = "Agent added successfully. Temporary password is the phone number.";
                    $message_type = 'success';
                    
                    // Clear form data
                    $form_data = [
                        'name' => '',
                        'email' => '',
                        'phone' => '',
                        'main_community_id' => '',
                        'smaller_community_id' => '',
                        'suburb_id' => '',
                        'cottage_id' => ''
                    ];
                } else {
                    $errors[] = "Failed to add agent";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = 'error';
        
        // Preserve form data
        $form_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'main_community_id' => $main_community_id,
            'smaller_community_id' => $smaller_community_id,
            'suburb_id' => $suburb_id,
            'cottage_id' => $cottage_id
        ];
    }
}

// Fetch location data for dropdowns
try {
    $communities = $conn->query("SELECT id, name FROM communities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $smaller_communities = $conn->query("SELECT id, name FROM smaller_communities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $suburbs = $conn->query("SELECT id, name FROM suburbs ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $cottages = $conn->query("SELECT id, name FROM cottages ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $communities = [];
    $smaller_communities = [];
    $suburbs = [];
    $cottages = [];
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

// Get the logged-in user's name
$userName = $_SESSION['user_name'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add New Agent - Admin Dashboard</title>
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
        <?php renderAdminHeader('Add New Agent', 'Create a new agent account', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Add Agent Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <form method="POST" action="" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($form_data['name']); ?>" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($form_data['phone']); ?>" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                    </div>

                    <!-- Location Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Location Assignment</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Main Community -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Main Community *</label>
                                <select name="main_community_id" id="main_community" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="">Select Main Community</option>
                                    <?php foreach ($communities as $community): ?>
                                        <option value="<?php echo $community['id']; ?>" <?php echo $form_data['main_community_id'] == $community['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($community['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Smaller Community -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Smaller Community</label>
                                <select name="smaller_community_id" id="smaller_community" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Smaller Community</option>
                                    <?php foreach ($smaller_communities as $smaller_community): ?>
                                        <option value="<?php echo $smaller_community['id']; ?>" <?php echo $form_data['smaller_community_id'] == $smaller_community['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($smaller_community['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Suburb -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Suburb</label>
                                <select name="suburb_id" id="suburb" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Suburb</option>
                                    <?php foreach ($suburbs as $suburb): ?>
                                        <option value="<?php echo $suburb['id']; ?>" <?php echo $form_data['suburb_id'] == $suburb['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($suburb['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Cottage -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cottage</label>
                                <select name="cottage_id" id="cottage" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Cottage</option>
                                    <?php foreach ($cottages as $cottage): ?>
                                        <option value="<?php echo $cottage['id']; ?>" <?php echo $form_data['cottage_id'] == $cottage['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cottage['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4">
                        <a href="./" class="px-6 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            Add Agent
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Cascading dropdowns for location selection
        document.getElementById('main_community').addEventListener('change', function() {
            const mainCommunityId = this.value;
            // You can add AJAX calls here to filter smaller communities based on main community
            // For now, we'll keep it simple
        });
    </script>
</body>

</html>
