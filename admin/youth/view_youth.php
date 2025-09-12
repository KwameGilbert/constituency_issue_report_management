<?php
// admin/youth/view_youth.php - Youth Record Detailed View
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'youth';

// Initialize message variables
$message = '';
$message_type = '';

// Process GET parameters for messages
if (isset($_GET['message']) && !empty($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info';
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?message=Invalid youth record ID&type=error');
    exit;
}

$id = (int)$_GET['id'];

// Process status update (approve/reject)
if (isset($_GET['action']) && in_array($_GET['action'], ['approve', 'reject', 'archive']) && isset($_GET['confirmed']) && $_GET['confirmed'] === 'true') {
    $action = $_GET['action'];
    $status_map = [
        'approve' => 'approved',
        'reject' => 'rejected',
        'archive' => 'archived'
    ];
    
    try {
        $admin_id = $_SESSION['admin_id'];
        $status = $status_map[$action];
        
        $stmt = $conn->prepare("
            UPDATE youth_records 
            SET status = ?, reviewed_by = ?, reviewed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$status, $admin_id, $id]);
        
        // Record the action in activity log
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
            VALUES (:user_id, 'update_youth_status', :details, :ip_address, :user_agent)
        ");
        $details = "Updated youth record ID: $id status to $status";
        $stmt->bindParam(':user_id', $admin_id);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        $stmt->execute();
        
        $message = "Youth record status updated to " . ucfirst($status) . ".";
        $message_type = 'success';
        
        // Redirect to remove the action parameters from URL
        header("Location: view_youth.php?id=$id&message=" . urlencode($message) . "&type=" . urlencode($message_type));
        exit;
    } catch (Exception $e) {
        $message = "Error updating youth record status: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch the youth record
try {
    $stmt = $conn->prepare("
        SELECT * FROM youth_records WHERE id = ?
    ");
    $stmt->execute([$id]);
    $youth = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$youth) {
        header('Location: index.php?message=Youth record not found&type=error');
        exit;
    }
    
    // Fetch the reviewer details if reviewed
    $reviewer_name = null;
    if ($youth['reviewed_by']) {
        $stmt = $conn->prepare("
            SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id = ?
        ");
        $stmt->execute([$youth['reviewed_by']]);
        $reviewer = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reviewer) {
            $reviewer_name = $reviewer['name'];
        }
    }
} catch (Exception $e) {
    header('Location: index.php?message=' . urlencode("Error fetching youth record: " . $e->getMessage()) . '&type=error');
    exit;
}

// Calculate age
$dob = new DateTime($youth['date_of_birth']);
$now = new DateTime();
$age = $now->diff($dob)->y;

// Get counts for sidebar
$pendingIssuesCount = getSystemPendingIssuesCount($conn);
$activeUsersCount = getActiveUsersCount($conn);

// Status badge styling
$status_badges = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'archived' => 'bg-gray-100 text-gray-800'
];

$employment_badges = [
    'unemployed' => 'bg-red-100 text-red-800',
    'employed' => 'bg-green-100 text-green-800',
    'self_employed' => 'bg-blue-100 text-blue-800',
    'student' => 'bg-purple-100 text-purple-800'
];

$availability_badges = [
    'available' => 'bg-green-100 text-green-800',
    'unavailable' => 'bg-red-100 text-red-800',
    'part_time' => 'bg-blue-100 text-blue-800'
];

$status_badge_class = $status_badges[$youth['status']] ?? 'bg-gray-100 text-gray-800';
$employment_badge_class = $employment_badges[$youth['employment_status']] ?? 'bg-gray-100 text-gray-800';
$availability_badge_class = $availability_badges[$youth['availability_status']] ?? 'bg-gray-100 text-gray-800';

?>

<?php renderAdminSidebar($current_page, $pendingIssuesCount, $activeUsersCount); ?>

<div class="lg:pl-64 flex flex-col flex-1">
    <?php require_once __DIR__ . '/../components/header.php'; ?>
    
    <main class="flex-1 pb-8 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <!-- Page header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center">
                            <div class="bg-purple-100 rounded-full h-12 w-12 flex items-center justify-center mr-4">
                                <span class="text-purple-700 font-bold text-xl"><?php echo strtoupper(substr($youth['name'], 0, 1)); ?></span>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:leading-9 sm:truncate">
                                    <?php echo htmlspecialchars($youth['name']); ?>
                                </h1>
                                <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <i class="fas fa-id-card flex-shrink-0 mr-1.5 text-gray-400"></i>
                                        <?php echo htmlspecialchars($youth['national_id']); ?>
                                    </div>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <i class="fas fa-phone flex-shrink-0 mr-1.5 text-gray-400"></i>
                                        <?php echo htmlspecialchars($youth['phone_number']); ?>
                                    </div>
                                    <div class="mt-2 flex items-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_badge_class; ?>">
                                            <?php echo ucfirst($youth['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-4 flex-shrink-0 flex md:mt-0 md:ml-4 space-x-3">
                        <a href="edit_youth.php?id=<?php echo $youth['id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Record
                        </a>
                        <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to List
                        </a>
                        
                        <?php if ($youth['status'] === 'pending'): ?>
                            <div class="relative inline-block text-left" x-data="{ open: false }">
                                <button @click="open = !open" type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500" id="options-menu" aria-haspopup="true" aria-expanded="true">
                                    <i class="fas fa-cog mr-2"></i>
                                    Actions
                                    <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                                <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                        <a href="#" onclick="confirmAction('approve', <?php echo $youth['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 flex items-center" role="menuitem">
                                            <i class="fas fa-check-circle mr-3 text-green-500"></i>
                                            Approve Record
                                        </a>
                                        <a href="#" onclick="confirmAction('reject', <?php echo $youth['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 flex items-center" role="menuitem">
                                            <i class="fas fa-times-circle mr-3 text-red-500"></i>
                                            Reject Record
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="rounded-md p-4 mb-4 <?php echo $message_type === 'success' ? 'bg-green-50' : ($message_type === 'error' ? 'bg-red-50' : ($message_type === 'warning' ? 'bg-yellow-50' : 'bg-blue-50')); ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <?php if ($message_type === 'success'): ?>
                            <i class="fas fa-check-circle text-green-400"></i>
                        <?php elseif ($message_type === 'error'): ?>
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        <?php elseif ($message_type === 'warning'): ?>
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        <?php else: ?>
                            <i class="fas fa-info-circle text-blue-400"></i>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium <?php echo $message_type === 'success' ? 'text-green-800' : ($message_type === 'error' ? 'text-red-800' : ($message_type === 'warning' ? 'text-yellow-800' : 'text-blue-800')); ?>">
                            <?php echo $message; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Personal Information -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-user-circle mr-2 text-purple-500"></i>
                        Personal Information
                    </h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($youth['name']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">National ID</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($youth['national_id']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Date of Birth</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo date('F d, Y', strtotime($youth['date_of_birth'])); ?> 
                                <span class="text-gray-500">(<?php echo $age; ?> years old)</span>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($youth['phone_number']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Home Town</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($youth['home_town']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Residential Community</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($youth['residential_community']); ?></dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            <!-- Employment Information -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-briefcase mr-2 text-blue-500"></i>
                        Employment Information
                    </h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Employment Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $employment_badge_class; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $youth['employment_status'])); ?>
                                </span>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Current Employment</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['current_employment']) ? htmlspecialchars($youth['current_employment']) : '<span class="text-gray-400">Not specified</span>'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Availability Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $availability_badge_class; ?>">
                                    <?php echo ucfirst($youth['availability_status']); ?>
                                </span>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Preferred Work Location</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['preferred_work_location']) ? htmlspecialchars($youth['preferred_work_location']) : '<span class="text-gray-400">Not specified</span>'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Salary Expectation</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['salary_expectation']) ? 'GH₵ ' . number_format($youth['salary_expectation'], 2) : '<span class="text-gray-400">Not specified</span>'; ?>
                            </dd>
                        </div>
                    </dl>
                    
                    <?php if (!empty($youth['employment_notes'])): ?>
                        <div class="mt-4 sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Employment Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($youth['employment_notes']); ?></dd>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Educational Qualifications -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-graduation-cap mr-2 text-green-500"></i>
                        Educational Qualifications
                    </h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">JHS Completed</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo $youth['jhs_completed'] ? 'Yes' : 'No'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">SHS Qualification</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['shs_qualification']) ? htmlspecialchars($youth['shs_qualification']) : '<span class="text-gray-400">None</span>'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Certificate Qualification</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['certificate_qualification']) ? htmlspecialchars($youth['certificate_qualification']) : '<span class="text-gray-400">None</span>'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Diploma Qualification</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['diploma_qualification']) ? htmlspecialchars($youth['diploma_qualification']) : '<span class="text-gray-400">None</span>'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">First Degree</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['first_degree']) ? htmlspecialchars($youth['first_degree']) : '<span class="text-gray-400">None</span>'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Postgraduate Qualification</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['postgraduate_qualification']) ? htmlspecialchars($youth['postgraduate_qualification']) : '<span class="text-gray-400">None</span>'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Professional Qualification</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php echo !empty($youth['professional_qualification']) ? htmlspecialchars($youth['professional_qualification']) : '<span class="text-gray-400">None</span>'; ?>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            <!-- Skills & Interests -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-tools mr-2 text-indigo-500"></i>
                        Skills & Interests
                    </h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Skills</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                                <?php echo !empty($youth['skills']) ? htmlspecialchars($youth['skills']) : '<span class="text-gray-400">No skills listed</span>'; ?>
                            </dd>
                        </div>
                        <div class="sm:col-span-1 mt-4">
                            <dt class="text-sm font-medium text-gray-500">Interests</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">
                                <?php echo !empty($youth['interests']) ? htmlspecialchars($youth['interests']) : '<span class="text-gray-400">No interests listed</span>'; ?>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            <!-- Work Experience -->
            <div class="lg:col-span-2 bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-history mr-2 text-yellow-500"></i>
                        Work Experience
                    </h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <?php 
                    $has_experience = false;
                    for ($i = 1; $i <= 6; $i++) {
                        $exp_field = 'work_experience_' . $i;
                        if (!empty($youth[$exp_field])) {
                            $has_experience = true;
                            echo '<div class="mb-4 p-3 bg-gray-50 rounded-lg">';
                            echo '<p class="text-sm text-gray-900 whitespace-pre-line">' . htmlspecialchars($youth[$exp_field]) . '</p>';
                            echo '</div>';
                        }
                    }
                    
                    if (!$has_experience) {
                        echo '<p class="text-gray-400">No work experience listed</p>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Administrative Information -->
            <div class="lg:col-span-2 bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-cogs mr-2 text-gray-500"></i>
                        Administrative Information
                    </h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Record Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_badge_class; ?>">
                                    <?php echo ucfirst($youth['status']); ?>
                                </span>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Created On</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo date('F d, Y \a\t h:i A', strtotime($youth['created_at'])); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo date('F d, Y \a\t h:i A', strtotime($youth['updated_at'])); ?></dd>
                        </div>
                        <?php if ($youth['reviewed_by']): ?>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Reviewed By</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($reviewer_name ?? 'Unknown'); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Reviewed On</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo date('F d, Y \a\t h:i A', strtotime($youth['reviewed_at'])); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                    
                    <?php if (!empty($youth['admin_notes'])): ?>
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Admin Notes</h4>
                            <p class="text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($youth['admin_notes']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Action Confirmation Modal -->
<div id="actionModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div id="modalIcon" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i id="modalIconInner" class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title"></h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modal-description"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirmActionBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Confirm
                </button>
                <button type="button" id="cancelActionBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AlpineJS components
    if (typeof Alpine !== 'undefined') {
        Alpine.start();
    }
    
    const modal = document.getElementById('actionModal');
    const modalTitle = document.getElementById('modal-title');
    const modalDescription = document.getElementById('modal-description');
    const confirmBtn = document.getElementById('confirmActionBtn');
    const cancelBtn = document.getElementById('cancelActionBtn');
    const modalIcon = document.getElementById('modalIcon');
    const modalIconInner = document.getElementById('modalIconInner');
    
    // Action confirmation
    window.confirmAction = function(action, id) {
        let title, description, buttonText, iconColor, icon;
        
        if (action === 'approve') {
            title = 'Approve Youth Record';
            description = 'Are you sure you want to approve this youth record? This will mark it as reviewed and approved in the system.';
            buttonText = 'Approve';
            iconColor = 'bg-green-100';
            icon = 'fas fa-check-circle text-green-600';
            confirmBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
            confirmBtn.classList.add('bg-green-600', 'hover:bg-green-700');
        } else if (action === 'reject') {
            title = 'Reject Youth Record';
            description = 'Are you sure you want to reject this youth record? This will mark it as reviewed and rejected in the system.';
            buttonText = 'Reject';
            iconColor = 'bg-red-100';
            icon = 'fas fa-times-circle text-red-600';
            confirmBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            confirmBtn.classList.add('bg-red-600', 'hover:bg-red-700');
        }
        
        modalTitle.textContent = title;
        modalDescription.textContent = description;
        confirmBtn.textContent = buttonText;
        modalIcon.className = `mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full ${iconColor} sm:mx-0 sm:h-10 sm:w-10`;
        modalIconInner.className = icon;
        
        modal.classList.remove('hidden');
        
        confirmBtn.onclick = function() {
            window.location.href = `view_youth.php?id=${id}&action=${action}&confirmed=true`;
        };
        
        cancelBtn.onclick = function() {
            modal.classList.add('hidden');
        };
    };
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>