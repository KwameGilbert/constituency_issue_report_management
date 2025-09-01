<?php
// admin/agents/edit_agent.php - Edit Agent Details
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'agents';

// Check if agent ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ./');
    exit;
}

$agent_id = $_GET['id'];
$message = '';
$message_type = '';

// Fetch agent details
try {
    $sql = "
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
        WHERE u.id = ? AND u.role = 'agent'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        header('Location: ./');
        exit;
    }
} catch (Exception $e) {
    $message = "Error fetching agent details: " . $e->getMessage();
    $message_type = 'error';
    $agent = null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $agent) {
    // Sanitize and validate input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? '';
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

    if (empty($status)) {
        $errors[] = "Status is required";
    }

    if (empty($main_community_id)) {
        $errors[] = "Main community is required";
    }

    if (empty($errors)) {
        try {
            // Check if email already exists (excluding current agent)
            $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$email, $agent_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = "Email already exists";
            } else {
                // Update agent
                $update_sql = "UPDATE users SET name = ?, email = ?, phone = ?, status = ?, main_community_id = ?, smaller_community_id = ?, suburb_id = ?, cottage_id = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->execute([$name, $email, $phone, $status, $main_community_id, $smaller_community_id, $suburb_id, $cottage_id, $agent_id]);
                
                if ($update_stmt->rowCount() > 0) {
                    $message = "Agent updated successfully";
                    $message_type = 'success';
                    
                    // Refresh agent data
                    $stmt->execute([$agent_id]);
                    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $errors[] = "No changes were made";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = 'error';
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
        'icon' => 'fas fa-eye',
        'label' => 'View Agent',
        'href' => './view_agent.php?id=' . $agent_id,
        'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'
    ],
    [
        'icon' => 'fas fa-key',
        'label' => 'Reset Password',
        'href' => '../users/reset_password.php?id=' . $agent_id,
        'class' => 'bg-yellow-600 text-white hover:bg-yellow-700',
        'onclick' => "return confirm('Are you sure you want to reset password for this agent?')"
    ],
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
    <title>Edit Agent - Admin Dashboard</title>
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
    <?php renderAdminSidebar($current_page); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAdminHeader('Edit Agent', 'Update agent information', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($agent): ?>
                <!-- Edit Agent Form -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($agent['name']); ?>" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($agent['email']); ?>" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($agent['phone']); ?>" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                                <select name="status" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="active" <?php echo $agent['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $agent['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
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
                                            <option value="<?php echo $community['id']; ?>" <?php echo $agent['main_community_id'] == $community['id'] ? 'selected' : ''; ?>>
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
                                            <option value="<?php echo $smaller_community['id']; ?>" <?php echo $agent['smaller_community_id'] == $smaller_community['id'] ? 'selected' : ''; ?>>
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
                                            <option value="<?php echo $suburb['id']; ?>" <?php echo $agent['suburb_id'] == $suburb['id'] ? 'selected' : ''; ?>>
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
                                            <option value="<?php echo $cottage['id']; ?>" <?php echo $agent['cottage_id'] == $cottage['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cottage['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4">
                            <a href="./view_agent.php?id=<?php echo $agent_id; ?>" class="px-6 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                                Update Agent
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-times text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-800 mb-2">Agent Not Found</h3>
                    <p class="text-gray-600 mb-4">The agent you're trying to edit doesn't exist or has been removed.</p>
                    <a href="./" class="inline-block px-4 py-2 text-sm font-medium text-indigo-900 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Agents
                    </a>
                </div>
            <?php endif; ?>
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
