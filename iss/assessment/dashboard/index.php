<?php
require_once __DIR__ . '/../../config/db_connection.php';
$database = new Database();
$conn = $database->getConnection();

require_once __DIR__ . '/../login/check_assessment_session.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'dashboard';

// Get assessment statistics
try {
    // Issues pending assessment
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM issues WHERE accesments_status = 'pending_assessment'");
    $stmt->execute();
    $pendingAssessment = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Issues under assessment
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM issues WHERE accesments_status = 'under_assessment'");
    $stmt->execute();
    $underAssessment = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Issues assessed this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM issues WHERE accesments_status IN ('approved', 'rejected') AND MONTH(updated_at) = MONTH(CURRENT_DATE()) AND YEAR(updated_at) = YEAR(CURRENT_DATE())");
    $stmt->execute();
    $assessedThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total issues assessed by this assessor
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM issues WHERE assessment_status IN ('approved', 'rejected')");
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $totalAssessed = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

} catch (Exception $e) {
    error_log("Assessment dashboard error: " . $e->getMessage());
    $pendingAssessment = $underAssessment = $assessedThisMonth = $totalAssessed = 0;
}

// Header actions
$headerActionButtons = [
    ['icon' => 'fas fa-plus', 'label' => 'New Assessment', 'href' => '../issues/add_assessment.php'],
    ['icon' => 'fas fa-file-export', 'label' => 'Export Reports', 'href' => '../reports/export.php?type=assessment'],
];

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Dashboard - SWMA System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/styles/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen font-inter">
    <?php renderOfficerSidebar($current_page, $pendingAssessment); ?>

    <main class="lg:ml-64 flex flex-col flex-1">
        <?php renderAdminHeader('Assessment Dashboard', 'Welcome to your assessment workspace. Review and assess community issues.', $headerActionButtons); ?>

        <div class="flex-1 pb-8 px-4 sm:px-6 lg:px-8 bg-gray-50">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Pending Assessment -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-yellow-100">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pending Assessment</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $pendingAssessment; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Under Assessment -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-100">
                            <i class="fas fa-tasks text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Under Assessment</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $underAssessment; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Assessed This Month -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-100">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Assessed This Month</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $assessedThisMonth; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Assessed -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-purple-100">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Assessed</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $totalAssessed; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Assessments -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Assessments</h3>
                    <p class="text-sm text-gray-600">Issues you've recently assessed</p>
                </div>
                <div class="p-6">
                    <?php
                    try {
                        $stmt = $conn->prepare("
                            SELECT i.id, i.title, i.status, i.updated_at, c.name as community_name
                            FROM issues i
                            LEFT JOIN communities c ON i.community_id = c.id
                            WHERE i.assessed_by = ?
                            ORDER BY i.updated_at DESC
                            LIMIT 5
                        ");
                        $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
                        $stmt->execute();
                        $recentAssessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($recentAssessments)) {
                            echo '<div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p>No assessments yet. Start reviewing issues!</p>
                            </div>';
                        } else {
                            echo '<div class="space-y-4">';
                            foreach ($recentAssessments as $assessment) {
                                $statusColor = match($assessment['status']) {
                                    'approved' => 'text-green-600 bg-green-100',
                                    'rejected' => 'text-red-600 bg-red-100',
                                    'under_assessment' => 'text-blue-600 bg-blue-100',
                                    default => 'text-gray-600 bg-gray-100'
                                };
                                echo '<div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900">' . htmlspecialchars($assessment['title']) . '</h4>
                                        <p class="text-sm text-gray-600">' . htmlspecialchars($assessment['community_name'] ?? 'Unknown Community') . '</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusColor . '">
                                            ' . ucfirst(str_replace('_', ' ', $assessment['status'])) . '
                                        </span>
                                        <p class="text-xs text-gray-500 mt-1">' . date('M d, Y', strtotime($assessment['updated_at'])) . '</p>
                                    </div>
                                </div>';
                            }
                            echo '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="text-center py-8 text-red-500">
                            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                            <p>Error loading recent assessments</p>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>