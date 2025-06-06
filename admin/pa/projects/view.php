<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in as PA
if (!isset($_SESSION['pa_id'])) {
    header("Location: ../login/");
    exit;
}

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: index.php");
    exit;
}

$project_id = intval($_GET['id']);

// Fetch project details
$query = "SELECT * FROM projects WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Project not found.";
    header("Location: index.php");
    exit;
}

$project = $result->fetch_assoc();

// Decode images JSON
$images = [];
if (!empty($project['images'])) {
    $images = json_decode($project['images'], true) ?: [];
}

// Set page title
$page_title = $project['title'] . " - Project Details";
include '../includes/header.php';
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <!-- Breadcrumb -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="../dashboard/"
                        class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-green-600">
                        <i class="fas fa-home mr-2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2 text-sm"></i>
                        <a href="index.php" class="text-sm font-medium text-gray-700 hover:text-green-600">
                            Projects
                        </a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2 text-sm"></i>
                        <span
                            class="text-sm font-medium text-gray-500"><?= htmlspecialchars($project['title']) ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-md p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <span><?= $_SESSION['success'] ?></span>
                <button type="button" class="ml-auto text-green-500 hover:text-green-700"
                    onclick="this.parentElement.parentElement.style.display='none';">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-md p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span><?= $_SESSION['error'] ?></span>
                <button type="button" class="ml-auto text-red-500 hover:text-red-700"
                    onclick="this.parentElement.parentElement.style.display='none';">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Page Header Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800"><?= htmlspecialchars($project['title']) ?></h1>
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <a href="edit.php?id=<?= $project_id ?>"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-edit mr-2"></i> Edit Project
                </a>
                <a href="upload-photos.php?id=<?= $project_id ?>"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-images mr-2"></i> Manage Photos
                </a>
                <a href="add-update.php?id=<?= $project_id ?>"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-plus-circle mr-2"></i> Add Update
                </a>
                <button onclick="confirmDelete(<?= $project_id ?>)"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-trash-alt mr-2"></i> Delete
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Project Details -->
            <div class="lg:col-span-2">
                <!-- Project Details Card -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-800">Project Details</h2>
                        <?php 
                        $status_class = match($project['status']) {
                            'planned' => 'bg-blue-100 text-blue-800',
                            'ongoing' => 'bg-yellow-100 text-yellow-800',
                            'completed' => 'bg-green-100 text-green-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                        ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?= $status_class ?>">
                            <?= ucfirst($project['status']) ?>
                        </span>
                    </div>
                    <div class="p-6">
                        <!-- Project Images Carousel -->
                        <?php if (!empty($images)): ?>
                        <div class="relative mb-6">
                            <div id="projectCarousel" class="carousel w-full">
                                <div class="overflow-hidden rounded-lg h-64 md:h-80 relative">
                                    <?php foreach ($images as $index => $image): ?>
                                    <div class="carousel-item absolute inset-0 opacity-0 transition-opacity duration-700 ease-in-out"
                                        id="carousel-item-<?= $index ?>"
                                        <?= $index === 0 ? 'style="opacity: 1"' : '' ?>>
                                        <img src="<?= htmlspecialchars($image) ?>"
                                            class="absolute inset-0 w-full h-full object-contain" alt="Project Image">
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Carousel Navigation -->
                                <div class="absolute z-30 flex space-x-3 -translate-x-1/2 bottom-5 left-1/2">
                                    <?php foreach ($images as $index => $image): ?>
                                    <button type="button"
                                        class="w-3 h-3 rounded-full bg-white dark:bg-gray-800 hover:bg-white carousel-indicator"
                                        aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
                                        aria-label="Slide <?= $index + 1 ?>"
                                        data-carousel-slide-to="<?= $index ?>"></button>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Carousel Controls -->
                                <button type="button"
                                    class="absolute top-0 left-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none"
                                    data-carousel-prev>
                                    <span
                                        class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                                        <i class="fas fa-chevron-left text-white dark:text-gray-800"></i>
                                    </span>
                                </button>
                                <button type="button"
                                    class="absolute top-0 right-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none"
                                    data-carousel-next>
                                    <span
                                        class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                                        <i class="fas fa-chevron-right text-white dark:text-gray-800"></i>
                                    </span>
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="mb-6 flex justify-center">
                            <img src="../../../assets/images/projects/default-project.jpg"
                                class="rounded-lg max-h-64 object-cover" alt="Default Project Image">
                        </div>
                        <?php endif; ?>

                        <!-- Project Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Location & Sector</h3>
                                <p class="text-gray-800 mb-1"><span class="font-medium">Location:</span>
                                    <?= htmlspecialchars($project['location']) ?></p>
                                <p class="text-gray-800 mb-1"><span class="font-medium">Sector:</span>
                                    <?= htmlspecialchars($project['sector']) ?></p>
                                <?php if (!empty($project['people_benefitted'])): ?>
                                <p class="text-gray-800 mb-1"><span class="font-medium">Beneficiaries:</span>
                                    <?= number_format($project['people_benefitted']) ?> people</p>
                                <?php endif; ?>
                                <?php if (!empty($project['budget_allocation'])): ?>
                                <p class="text-gray-800 mb-1"><span class="font-medium">Budget:</span> GHS
                                    <?= number_format($project['budget_allocation'], 2) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Timeline</h3>
                                <p class="text-gray-800 mb-1">
                                    <span class="font-medium">Start Date:</span>
                                    <?= date('F d, Y', strtotime($project['start_date'])) ?>
                                </p>
                                <?php if (!empty($project['end_date'])): ?>
                                <p class="text-gray-800 mb-1">
                                    <span class="font-medium">End Date:</span>
                                    <?= date('F d, Y', strtotime($project['end_date'])) ?>
                                </p>
                                <?php endif; ?>
                                <p class="text-gray-800 mb-1">
                                    <span class="font-medium">Progress:</span> <?= $project['progress'] ?>%
                                </p>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                    <?php 
                                    $progress_color = 'bg-blue-600';
                                    if ($project['progress'] >= 100) {
                                        $progress_color = 'bg-green-600';
                                    } elseif ($project['progress'] >= 50) {
                                        $progress_color = 'bg-yellow-600';
                                    }
                                    ?>
                                    <div class="<?= $progress_color ?> h-2.5 rounded-full"
                                        style="width: <?= $project['progress'] ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Description</h3>
                            <div class="prose max-w-none text-gray-700">
                                <?= nl2br(htmlspecialchars($project['description'])) ?>
                            </div>
                        </div>

                        <?php if ($project['featured']): ?>
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-star text-blue-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        This project is featured on the website homepage.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Updates Card -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-800">Project Updates</h2>
                        <a href="add-update.php?id=<?= $project_id ?>"
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-green-600 rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <i class="fas fa-plus-circle mr-2"></i> Add Update
                        </a>
                    </div>
                    <div class="p-6">


                        <!-- Fetch project updates if there is a project_updates table -->
                        <?php
                        $updates_query = "SHOW TABLES LIKE 'project_updates'";
                        $updates_result = $conn->query($updates_query);
                        
                        if ($updates_result->num_rows > 0) {
                            $updates_query = "SELECT * FROM project_updates WHERE project_id = ? ORDER BY created_at DESC";
                            $updates_stmt = $conn->prepare($updates_query);
                            $updates_stmt->bind_param("i", $project_id);
                            $updates_stmt->execute();
                            $updates_result = $updates_stmt->get_result();
                            
                            if ($updates_result->num_rows > 0) {
                                ?>
                        <div class="relative">
                            <!-- Timeline Line -->
                            <div class="absolute left-5 top-0 h-full w-0.5 bg-gray-200"></div>

                            <div class="space-y-8">
                                <?php while ($update = $updates_result->fetch_assoc()): ?>
                                <div class="relative pl-12">
                                    <!-- Timeline Dot -->
                                    <div
                                        class="absolute left-0 top-1 bg-white border-4 border-green-500 rounded-full h-10 w-10 flex items-center justify-center">
                                        <i class="fas fa-clipboard-check text-green-500"></i>
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-4 shadow-sm">
                                        <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-2">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?= htmlspecialchars($update['title']) ?></h3>
                                            <time
                                                class="text-sm text-gray-500"><?= date('M d, Y', strtotime($update['created_at'])) ?></time>
                                        </div>
                                        <div class="prose prose-sm max-w-none text-gray-700 mb-3">
                                            <?= nl2br(htmlspecialchars($update['description'])) ?>
                                        </div>

                                        <?php if (!empty($update['status'])): ?>
                                        <?php 
                                                $update_status_class = match($update['status']) {
                                                    'planned' => 'bg-blue-100 text-blue-800',
                                                    'ongoing' => 'bg-yellow-100 text-yellow-800',
                                                    'completed' => 'bg-green-100 text-green-800',
                                                    default => 'bg-gray-100 text-gray-800'
                                                };
                                                ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $update_status_class ?>">
                                            <?= ucfirst($update['status']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <?php
                            } else {
                                ?>
                        <div class="text-center py-10">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                                <i class="fas fa-clipboard-list text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No updates yet</h3>
                            <p class="text-gray-500 mb-6">Keep stakeholders informed by adding regular updates about
                                this project's progress.</p>
                            <a href="add-update.php?id=<?= $project_id ?>"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-plus-circle mr-2"></i> Add First Update
                            </a>
                        </div>
                        <?php
                            }
                        } else {
                            ?>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Project updates feature is not available. The required database table does not
                                        exist.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>

                <!-- Project Comments Card -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-800">Project Comments</h2>
                    </div>
                    <div class="p-6">
                        <!-- Fetch project comments if there is a project_comments table -->
                        <?php
                        $comments_query = "SHOW TABLES LIKE 'project_comments'";
                        $comments_result = $conn->query($comments_query);
                        
                        if ($comments_result->num_rows > 0) {
                            // Add new comment form
                            ?>
                        <form action="add-comment.php" method="POST" class="mb-6">
                            <input type="hidden" name="project_id" value="<?= $project_id ?>">
                            <div class="mb-4">
                                <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">Add a
                                    comment</label>
                                <textarea id="comment" name="comment" rows="3"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                    required></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Comment
                                </button>
                            </div>
                        </form>

                        <div class="border-t border-gray-200 pt-6">
                            <?php
                            // Fetch comments
                            $comments_query = "SELECT c.*, pa.name as author_name 
                                              FROM project_comments c
                                              LEFT JOIN personal_assistants pa ON c.pa_id = pa.id
                                              WHERE c.project_id = ? 
                                              ORDER BY c.created_at DESC";
                            $comments_stmt = $conn->prepare($comments_query);
                            $comments_stmt->bind_param("i", $project_id);
                            $comments_stmt->execute();
                            $comments_result = $comments_stmt->get_result();
                            
                            if ($comments_result->num_rows > 0) {
                                ?>
                            <div class="space-y-6">
                                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                                <div class="flex space-x-4">
                                    <div class="flex-shrink-0">
                                        <span
                                            class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-gray-200">
                                            <span class="text-gray-600 font-medium text-sm">
                                                <?= strtoupper(substr($comment['author_name'] ?? 'U', 0, 1)) ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center mb-1">
                                            <h3 class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($comment['author_name']) ?></h3>
                                            <time
                                                class="text-xs text-gray-500"><?= date('M d, Y g:i A', strtotime($comment['created_at'])) ?></time>
                                        </div>
                                        <div class="text-sm text-gray-700 space-y-2">
                                            <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php
                            } else {
                                ?>
                            <div class="text-center py-8">
                                <span
                                    class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-3">
                                    <i class="far fa-comments text-gray-400"></i>
                                </span>
                                <h3 class="text-sm font-medium text-gray-900 mb-1">No comments yet</h3>
                                <p class="text-xs text-gray-500">Be the first to leave a comment!</p>
                            </div>
                            <?php
                            }
                            ?>
                        </div>
                        <?php
                        } else {
                            ?>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Project comments feature is not available. The required database table does not
                                        exist.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Sidebar -->
            <div>
                <!-- Project Statistics Card -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-800">Project Summary</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-5">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase mb-1">Status</p>
                                <div class="flex items-center">
                                    <?php
                                    $status_icon_class = match($project['status']) {
                                        'planned' => 'text-blue-500',
                                        'ongoing' => 'text-yellow-500',
                                        'completed' => 'text-green-500',
                                        default => 'text-gray-500'
                                    };
                                    $status_icon = match($project['status']) {
                                        'planned' => 'fa-clipboard-list',
                                        'ongoing' => 'fa-hammer',
                                        'completed' => 'fa-check-circle',
                                        default => 'fa-info-circle'
                                    };
                                    ?>
                                    <i class="fas <?= $status_icon ?> <?= $status_icon_class ?> mr-2"></i>
                                    <span
                                        class="text-lg font-medium text-gray-900"><?= ucfirst($project['status']) ?></span>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase mb-1">Project Duration</p>
                                <div class="text-lg font-medium text-gray-900">
                                    <?php
                                    $start_date = new DateTime($project['start_date']);
                                    $duration = "";
                                
                                    if (!empty($project['end_date'])) {
                                        $end_date = new DateTime($project['end_date']);
                                        $interval = $start_date->diff($end_date);
                                        
                                        if ($interval->y > 0) {
                                            $duration .= $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ";
                                        }
                                        
                                        if ($interval->m > 0) {
                                            $duration .= $interval->m . " month" . ($interval->m > 1 ? "s" : "");
                                        }
                                        
                                        if (empty($duration) && $interval->d > 0) {
                                            $duration = $interval->d . " day" . ($interval->d > 1 ? "s" : "");
                                        }
                                        
                                        echo $duration ?: 'Same day';
                                    } else {
                                        echo "Ongoing";
                                    }
                                    ?>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase mb-1">Project Timeline</p>

                                <?php
                                // Calculate timeline progress
                                $start = strtotime($project['start_date']);
                                $now = time();
                                $end = !empty($project['end_date']) ? strtotime($project['end_date']) : null;
                                
                                if ($end): 
                                    $total_duration = $end - $start;
                                    $elapsed_duration = $now - $start;
                                    $timeline_progress = min(100, max(0, ($elapsed_duration / $total_duration) * 100));
                                    
                                    // Determine color based on timeline progress
                                    $timeline_color = 'bg-green-600';
                                    if ($timeline_progress > 75) {
                                        $timeline_color = 'bg-red-600';
                                    } elseif ($timeline_progress > 50) {
                                        $timeline_color = 'bg-yellow-600';
                                    }
                                ?>
                                <div class="relative bg-gray-200 rounded-full h-2.5 mb-2 overflow-hidden">
                                    <div class="absolute top-0 left-0 h-full <?= $timeline_color ?> transition-all duration-1000 ease-out"
                                        style="width: <?= $timeline_progress ?>%">
                                        <div class="absolute right-0 top-0 h-full w-1 bg-white animate-pulse"></div>
                                    </div>

                                    <!-- Timeline Indicator Icon -->
                                    <div class="absolute -top-1 left-<?= $timeline_progress ?>% transform -translate-x-1/2 text-blue-500"
                                        style="width: 20px;">
                                        <i class="fas fa-flag animate-bounce"></i>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="bg-gray-200 rounded-full h-2.5 mb-2">
                                    <div class="h-2.5 rounded-full bg-blue-600 animate-pulse" style="width: 100%"></div>
                                </div>
                                <?php endif; ?>

                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php
                                    if ($project['status'] == 'completed') {
                                        echo "Project completed";
                                    } else if ($project['status'] == 'ongoing') {
                                        if (!empty($project['end_date'])) {
                                            $end = strtotime($project['end_date']);
                                            $now = time();
                                            $days_left = ceil(($end - $now) / (60 * 60 * 24));
                                            
                                            if ($days_left > 0) {
                                                echo "$days_left day" . ($days_left > 1 ? "s" : "") . " remaining";
                                            } else {
                                                echo "Due date passed";
                                            }
                                        } else {
                                            echo "In progress";
                                        }
                                    } else {
                                        echo "Not started yet";
                                    }
                                    ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase mb-1">Created On</p>
                                <div class="text-lg font-medium text-gray-900">
                                    <?= date('F d, Y', strtotime($project['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Entities Card -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-800">Assigned Entities</h2>
                        <a href="assign-entity.php?id=<?= $project_id ?>"
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-plus-circle mr-1"></i> Assign
                        </a>
                    </div>
                    <div class="p-6">
                        <?php
                        $entities_query = "SHOW TABLES LIKE 'project_entities'";
                        $entities_result = $conn->query($entities_query);
                        
                        if ($entities_result->num_rows > 0) {
                            // Fetch assigned entities
                            $entities_query = "SELECT pe.*, e.name, e.contact, e.type 
                                              FROM project_entities pe
                                              JOIN entities e ON pe.entity_id = e.id
                                              WHERE pe.project_id = ?";
                            $entities_stmt = $conn->prepare($entities_query);
                            $entities_stmt->bind_param("i", $project_id);
                            $entities_stmt->execute();
                            $entities_result = $entities_stmt->get_result();
                            
                            if ($entities_result->num_rows > 0) {
                                ?>
                        <div class="space-y-3">
                            <?php while ($entity = $entities_result->fetch_assoc()): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 mb-1">
                                            <?= htmlspecialchars($entity['name']) ?></h3>
                                        <p class="text-xs text-gray-500"><?= ucfirst($entity['type']) ?></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="../entities/view.php?id=<?= $entity['entity_id'] ?>"
                                            class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#"
                                            onclick="confirmRemoveEntity(<?= $project_id ?>, <?= $entity['entity_id'] ?>, '<?= htmlspecialchars(addslashes($entity['name'])) ?>')"
                                            class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </div>

                                <?php if (!empty($entity['contact'])): ?>
                                <p class="text-xs text-gray-600 mt-2">
                                    <i class="fas fa-phone-alt mr-1"></i> <?= htmlspecialchars($entity['contact']) ?>
                                </p>
                                <?php endif; ?>

                                <?php if (!empty($entity['role'])): ?>
                                <p class="text-xs text-gray-600 mt-1">
                                    <i class="fas fa-user-tag mr-1"></i> <?= htmlspecialchars($entity['role']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php
                            } else {
                                ?>
                        <div class="text-center py-8">
                            <span
                                class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-3">
                                <i class="fas fa-building text-gray-400"></i>
                            </span>
                            <h3 class="text-sm font-medium text-gray-900 mb-1">No entities assigned</h3>
                            <p class="text-xs text-gray-500 mb-4">Assign entities that are involved in this project</p>
                            <a href="assign-entity.php?id=<?= $project_id ?>"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                <i class="fas fa-plus-circle mr-2"></i> Assign Entity
                            </a>
                        </div>
                        <?php
                            }
                        } else {
                            ?>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Entity assignment feature is not available. The required database table does not
                                        exist.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Project Confirmation Modal -->
<div id="deleteProjectModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title"
    role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <!-- Modal panel -->
        <div
            class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Delete Project
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to delete this project? This action cannot be undone and all
                                associated data will be permanently removed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="confirmDeleteBtn"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Delete
                </a>
                <button type="button" onclick="closeDeleteModal()"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Remove Entity Confirmation Modal -->
<div id="removeEntityModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title"
    role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-circle text-yellow-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Remove Entity
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="removeEntityMessage">
                                Are you sure you want to remove this entity from the project?
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="confirmRemoveEntityBtn"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Remove
                </a>
                <button type="button" onclick="closeRemoveEntityModal()"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Carousel functionality
document.addEventListener('DOMContentLoaded', function() {
    const carouselItems = document.querySelectorAll('.carousel-item');
    const indicators = document.querySelectorAll('.carousel-indicator');
    const prevButton = document.querySelector('[data-carousel-prev]');
    const nextButton = document.querySelector('[data-carousel-next]');

    if (!carouselItems.length) return;

    let currentSlide = 0;
    const totalSlides = carouselItems.length;

    // Initialize indicators
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            goToSlide(index);
        });
    });

    // Previous button
    if (prevButton) {
        prevButton.addEventListener('click', () => {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateCarousel();
        });
    }

    // Next button
    if (nextButton) {
        nextButton.addEventListener('click', () => {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateCarousel();
        });
    }

    // Auto-rotate carousel
    let interval = setInterval(() => {
        currentSlide = (currentSlide + 1) % totalSlides;
        updateCarousel();
    }, 5000);

    // Pause on hover
    const carousel = document.getElementById('projectCarousel');
    if (carousel) {
        carousel.addEventListener('mouseenter', () => {
            clearInterval(interval);
        });

        carousel.addEventListener('mouseleave', () => {
            interval = setInterval(() => {
                currentSlide = (currentSlide + 1) % totalSlides;
                updateCarousel();
            }, 5000);
        });
    }

    function goToSlide(index) {
        currentSlide = index;
        updateCarousel();
    }

    function updateCarousel() {
        // Update slides
        carouselItems.forEach((item, index) => {
            item.style.opacity = index === currentSlide ? '1' : '0';
        });

        // Update indicators
        indicators.forEach((indicator, index) => {
            if (index === currentSlide) {
                indicator.setAttribute('aria-current', 'true');
                indicator.classList.add('bg-white');
                indicator.classList.remove('bg-white/50');
            } else {
                indicator.setAttribute('aria-current', 'false');
                indicator.classList.add('bg-white/50');
                indicator.classList.remove('bg-white');
            }
        });
    }
});

// Delete Project Confirmation
function confirmDelete(projectId) {
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + projectId;
    document.getElementById('deleteProjectModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteProjectModal').classList.add('hidden');
}

// Remove Entity Confirmation
function confirmRemoveEntity(projectId, entityId, entityName) {
    document.getElementById('removeEntityMessage').textContent =
        `Are you sure you want to remove ${entityName} from this project?`;
    document.getElementById('confirmRemoveEntityBtn').href = 'remove-entity.php?project_id=' + projectId +
        '&entity_id=' + entityId;
    document.getElementById('removeEntityModal').classList.remove('hidden');
}

function closeRemoveEntityModal() {
    document.getElementById('removeEntityModal').classList.add('hidden');
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const deleteModal = document.getElementById('deleteProjectModal');
    const removeEntityModal = document.getElementById('removeEntityModal');

    if (event.target === deleteModal) {
        closeDeleteModal();
    }

    if (event.target === removeEntityModal) {
        closeRemoveEntityModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>