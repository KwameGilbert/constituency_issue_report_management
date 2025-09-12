<?php
// admin/youth/index.php - Youth Records Management
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

// Delete youth record (via GET request with confirmation)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $confirmed = isset($_GET['confirmed']) && $_GET['confirmed'] === 'true';
    
    if ($confirmed) {
        try {
            // Record the deletion in activity log
            $admin_id = $_SESSION['admin_id'];
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
                VALUES (:user_id, 'delete_youth_record', :details, :ip_address, :user_agent)
            ");
            $details = "Deleted youth record ID: $id";
            $stmt->bindParam(':user_id', $admin_id);
            $stmt->bindParam(':details', $details);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
            $stmt->execute();
            
            // Delete the youth record
            $stmt = $conn->prepare("DELETE FROM youth_records WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = "Youth record deleted successfully.";
            $message_type = 'success';
            
            // Redirect to remove the delete parameters from URL
            header("Location: index.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
            exit;
        } catch (Exception $e) {
            $message = "Error deleting youth record: " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        // Get the name of the youth record for the confirmation dialog
        try {
            $stmt = $conn->prepare("SELECT name FROM youth_records WHERE id = ?");
            $stmt->execute([$id]);
            $youth_name = $stmt->fetchColumn();
            
            $message = "Are you sure you want to delete the record for \"" . htmlspecialchars($youth_name) . "\"?";
            $message_type = 'warning';
            $message_confirmation = true;
            $message_confirm_url = "index.php?delete=$id&confirmed=true";
            $message_cancel_url = "index.php";
        } catch (Exception $e) {
            $message = "Error retrieving youth record: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Process status update (approve/reject)
if (isset($_GET['action']) && in_array($_GET['action'], ['approve', 'reject', 'archive']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
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
        header("Location: index.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
        exit;
    } catch (Exception $e) {
        $message = "Error updating youth record status: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Get filter parameters
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$employment_filter = isset($_GET['employment']) ? $_GET['employment'] : '';
$qualification_filter = isset($_GET['qualification']) ? $_GET['qualification'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Prepare WHERE clause based on filters
$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "(name LIKE ? OR phone_number LIKE ? OR national_id LIKE ? OR residential_community LIKE ?)";
    $search_term = "%{$search_query}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($employment_filter)) {
    $where_clauses[] = "employment_status = ?";
    $params[] = $employment_filter;
}

if (!empty($qualification_filter)) {
    // Check for non-empty value in the selected qualification column
    switch($qualification_filter) {
        case 'jhs':
            $where_clauses[] = "jhs_completed = 1";
            break;
        case 'shs':
            $where_clauses[] = "shs_qualification IS NOT NULL AND shs_qualification != ''";
            break;
        case 'certificate':
            $where_clauses[] = "certificate_qualification IS NOT NULL AND certificate_qualification != ''";
            break;
        case 'diploma':
            $where_clauses[] = "diploma_qualification IS NOT NULL AND diploma_qualification != ''";
            break;
        case 'degree':
            $where_clauses[] = "first_degree IS NOT NULL AND first_degree != ''";
            break;
        case 'postgrad':
            $where_clauses[] = "postgraduate_qualification IS NOT NULL AND postgraduate_qualification != ''";
            break;
        case 'professional':
            $where_clauses[] = "professional_qualification IS NOT NULL AND professional_qualification != ''";
            break;
    }
}

// Count total youth records for pagination
try {
    $count_sql = "
        SELECT COUNT(*) 
        FROM youth_records
        " . (!empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "") . "
    ";
    $stmt = $conn->prepare($count_sql);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $total_youth_records = $stmt->fetchColumn();
    $total_pages = ceil($total_youth_records / $per_page);
    
    // Ensure page is within valid range
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    // Query to fetch youth records with pagination
    $sql = "
        SELECT 
            id, 
            name, 
            date_of_birth, 
            national_id, 
            residential_community, 
            phone_number,
            employment_status,
            status,
            created_at
        FROM 
            youth_records
        " . (!empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "") . "
        ORDER BY 
            created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters for main query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $youth_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = "Error fetching youth records: " . $e->getMessage();
    $message_type = 'error';
    $youth_records = [];
    $total_pages = 0;
    $total_youth_records = 0;
}

// Get counts by status for quick stats
try {
    $status_counts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'archived' => 0
    ];
    
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM youth_records GROUP BY status");
    $status_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($status_results as $row) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    // Get employment status counts
    $employment_counts = [
        'unemployed' => 0,
        'employed' => 0,
        'self_employed' => 0,
        'student' => 0
    ];
    
    $stmt = $conn->query("SELECT employment_status, COUNT(*) as count FROM youth_records GROUP BY employment_status");
    $employment_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($employment_results as $row) {
        $employment_counts[$row['employment_status']] = $row['count'];
    }
} catch (Exception $e) {
    // Silently fail for counts
    error_log("Error getting youth record counts: " . $e->getMessage());
}

// Define header action buttons
$headerActionButtons = [
    [
        'label' => 'Add New Youth Record',
        'icon' => 'fas fa-plus',
        'url' => 'add_youth.php',
        'class' => 'bg-purple-600 hover:bg-purple-700'
    ],
    [
        'label' => 'Export Records',
        'icon' => 'fas fa-file-export',
        'url' => '../reports/export.php?type=youth',
        'class' => 'bg-gray-600 hover:bg-gray-700'
    ]
];

// Get counts for sidebar
$pendingIssuesCount = getSystemPendingIssuesCount($conn);
$activeUsersCount = getActiveUsersCount($conn);

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
                        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:leading-9 sm:truncate">
                            Youth Records Management
                        </h1>
                        <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <i class="fas fa-users-cog flex-shrink-0 mr-1.5 text-gray-400"></i>
                                Total Records: <?php echo $total_youth_records; ?>
                            </div>
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <i class="fas fa-clock flex-shrink-0 mr-1.5 text-yellow-500"></i>
                                Pending: <?php echo $status_counts['pending']; ?>
                            </div>
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <i class="fas fa-check-circle flex-shrink-0 mr-1.5 text-green-500"></i>
                                Approved: <?php echo $status_counts['approved']; ?>
                            </div>
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <i class="fas fa-briefcase flex-shrink-0 mr-1.5 text-blue-500"></i>
                                Unemployed: <?php echo $employment_counts['unemployed']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-4 flex-shrink-0 flex md:mt-0 md:ml-4">
                        <?php foreach ($headerActionButtons as $button): ?>
                            <a href="<?php echo $button['url']; ?>" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white <?php echo $button['class']; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <i class="<?php echo $button['icon']; ?> mr-2"></i>
                                <?php echo $button['label']; ?>
                            </a>
                        <?php endforeach; ?>
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
                        <?php if (isset($message_confirmation)): ?>
                            <div class="mt-4">
                                <div class="flex space-x-3">
                                    <a href="<?php echo $message_confirm_url; ?>" 
                                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Confirm Delete
                                    </a>
                                    <a href="<?php echo $message_cancel_url; ?>" 
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Filter Form -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form action="index.php" method="GET" class="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search_query); ?>" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" placeholder="Name, ID, Phone, Community">
                        </div>
                    </div>
                    
                    <div class="w-full sm:w-1/5">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    
                    <div class="w-full sm:w-1/5">
                        <label for="employment" class="block text-sm font-medium text-gray-700">Employment</label>
                        <select id="employment" name="employment" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">All Employment</option>
                            <option value="unemployed" <?php echo $employment_filter === 'unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                            <option value="employed" <?php echo $employment_filter === 'employed' ? 'selected' : ''; ?>>Employed</option>
                            <option value="self_employed" <?php echo $employment_filter === 'self_employed' ? 'selected' : ''; ?>>Self-employed</option>
                            <option value="student" <?php echo $employment_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                    
                    <div class="w-full sm:w-1/5">
                        <label for="qualification" class="block text-sm font-medium text-gray-700">Qualification</label>
                        <select id="qualification" name="qualification" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">All Qualifications</option>
                            <option value="jhs" <?php echo $qualification_filter === 'jhs' ? 'selected' : ''; ?>>JHS</option>
                            <option value="shs" <?php echo $qualification_filter === 'shs' ? 'selected' : ''; ?>>SHS</option>
                            <option value="certificate" <?php echo $qualification_filter === 'certificate' ? 'selected' : ''; ?>>Certificate</option>
                            <option value="diploma" <?php echo $qualification_filter === 'diploma' ? 'selected' : ''; ?>>Diploma</option>
                            <option value="degree" <?php echo $qualification_filter === 'degree' ? 'selected' : ''; ?>>First Degree</option>
                            <option value="postgrad" <?php echo $qualification_filter === 'postgrad' ? 'selected' : ''; ?>>Postgraduate</option>
                            <option value="professional" <?php echo $qualification_filter === 'professional' ? 'selected' : ''; ?>>Professional</option>
                        </select>
                    </div>
                    
                    <div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-filter mr-2"></i>
                            Filter
                        </button>
                        <?php if (!empty($search_query) || !empty($status_filter) || !empty($employment_filter) || !empty($qualification_filter)): ?>
                            <a href="index.php" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-times mr-2"></i>
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Youth Records Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <?php if (empty($youth_records)): ?>
                <div class="px-4 py-12 text-center">
                    <div class="inline-block p-4 rounded-full bg-gray-100 mb-4">
                        <i class="fas fa-user-graduate text-gray-500 text-4xl"></i>
                    </div>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">No youth records found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <?php if (!empty($search_query) || !empty($status_filter) || !empty($employment_filter) || !empty($qualification_filter)): ?>
                            No records match your current filters. Try changing your search criteria.
                        <?php else: ?>
                            There are no youth records in the system yet.
                        <?php endif; ?>
                    </p>
                    <div class="mt-6">
                        <a href="add_youth.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            <i class="fas fa-plus mr-2"></i>
                            Add New Youth Record
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name & Contact
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID & Location
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Employment
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date Added
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($youth_records as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                            <span class="text-purple-700 font-medium text-sm"><?php echo strtoupper(substr($record['name'], 0, 2)); ?></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($record['name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($record['phone_number']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php 
                                                    // Calculate age
                                                    $dob = new DateTime($record['date_of_birth']);
                                                    $now = new DateTime();
                                                    $age = $now->diff($dob)->y;
                                                    echo "Age: {$age} years";
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['national_id']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['residential_community']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $status_badges = [
                                            'unemployed' => 'bg-red-100 text-red-800',
                                            'employed' => 'bg-green-100 text-green-800',
                                            'self_employed' => 'bg-blue-100 text-blue-800',
                                            'student' => 'bg-purple-100 text-purple-800'
                                        ];
                                        $status_display = [
                                            'unemployed' => 'Unemployed',
                                            'employed' => 'Employed',
                                            'self_employed' => 'Self-employed',
                                            'student' => 'Student'
                                        ];
                                        $badge_class = $status_badges[$record['employment_status']] ?? 'bg-gray-100 text-gray-800';
                                        $status_text = $status_display[$record['employment_status']] ?? ucfirst($record['employment_status']);
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badge_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $status_badges = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'archived' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $badge_class = $status_badges[$record['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($record['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="view_youth.php?id=<?php echo $record['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_youth.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Edit Record">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($record['status'] === 'pending'): ?>
                                            <a href="index.php?action=approve&id=<?php echo $record['id']; ?>" class="text-green-600 hover:text-green-900" title="Approve Record" onclick="return confirm('Approve this youth record?');">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="index.php?action=reject&id=<?php echo $record['id']; ?>" class="text-red-600 hover:text-red-900" title="Reject Record" onclick="return confirm('Reject this youth record?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($record['status'] !== 'archived'): ?>
                                            <a href="index.php?action=archive&id=<?php echo $record['id']; ?>" class="text-gray-600 hover:text-gray-900" title="Archive Record" onclick="return confirm('Archive this youth record?');">
                                                <i class="fas fa-archive"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?delete=<?php echo $record['id']; ?>" class="text-red-600 hover:text-red-900" title="Delete Record">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6" aria-label="Pagination">
                        <div class="hidden sm:block">
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium"><?php echo ($page - 1) * $per_page + 1; ?></span>
                                to
                                <span class="font-medium"><?php echo min($page * $per_page, $total_youth_records); ?></span>
                                of
                                <span class="font-medium"><?php echo $total_youth_records; ?></span>
                                results
                            </p>
                        </div>
                        <div class="flex-1 flex justify-between sm:justify-end">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo "index.php?page=" . ($page - 1) . (!empty($search_query) ? "&search=" . urlencode($search_query) : "") . (!empty($status_filter) ? "&status=" . urlencode($status_filter) : "") . (!empty($employment_filter) ? "&employment=" . urlencode($employment_filter) : "") . (!empty($qualification_filter) ? "&qualification=" . urlencode($qualification_filter) : ""); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php else: ?>
                                <button disabled class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-gray-50 cursor-not-allowed">
                                    Previous
                                </button>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo "index.php?page=" . ($page + 1) . (!empty($search_query) ? "&search=" . urlencode($search_query) : "") . (!empty($status_filter) ? "&status=" . urlencode($status_filter) : "") . (!empty($employment_filter) ? "&employment=" . urlencode($employment_filter) : "") . (!empty($qualification_filter) ? "&qualification=" . urlencode($qualification_filter) : ""); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php else: ?>
                                <button disabled class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-gray-50 cursor-not-allowed">
                                    Next
                                </button>
                            <?php endif; ?>
                        </div>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// JavaScript for table row hover effects
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.classList.add('bg-gray-50');
        });
        row.addEventListener('mouseleave', () => {
            row.classList.remove('bg-gray-50');
        });
    });
});
</script>
