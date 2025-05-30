<?php
require_once '../config/db.php';

// Get project ID
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch project details
$query = "SELECT * FROM projects WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$project = $result->fetch_assoc();

// Decode images JSON
$images = [];
if (!empty($project['images'])) {
    $images = json_decode($project['images'], true) ?: [];
}

// Fetch related projects (same sector, excluding current)
$related_query = "SELECT id, title, location, sector, status, start_date, end_date, images 
                  FROM projects 
                  WHERE sector = ? AND id != ? 
                  ORDER BY start_date DESC 
                  LIMIT 3";
$related_stmt = $conn->prepare($related_query);
$related_stmt->bind_param("si", $project['sector'], $project_id);
$related_stmt->execute();
$related_projects = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> | SWMA Projects</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
     <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/main.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
</head>

<body class="bg-gray-50">
    <?php include_once '../includes/header.php'; ?>

    <main>
        <!-- Project Content -->
        <div class="max-w-6xl mx-auto px-4 py-12">
            <!-- Back Button -->
            <a href="./index.php" class="inline-flex items-center text-amber-600 hover:underline mb-6">
                <i class="fas fa-arrow-left mr-2"></i> Back to Projects
            </a>

            <!-- Project Header -->
            <header class="bg-white p-6 rounded-lg shadow-md mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($project['title']) ?></h1>
                        <p class="text-sm text-gray-600">
                            <?= htmlspecialchars($project['sector']) ?> &bull;
                            <?= htmlspecialchars($project['location']) ?> &bull;
                            <span class="font-medium"><?= ucfirst($project['status']) ?></span>
                        </p>
                    </div>
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
                <p class="mt-4 text-sm text-gray-600">
                    <span class="font-medium">Start:</span> <?= date('F d, Y', strtotime($project['start_date'])) ?>
                    <?php if (!empty($project['end_date'])): ?>
                    &bull; <span class="font-medium">End:</span> <?= date('F d, Y', strtotime($project['end_date'])) ?>
                    <?php endif; ?>
                </p>
            </header>

            <!-- Project Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Project Details -->
                <div class="lg:col-span-2">
                    <!-- Project Images -->
                    <?php if (!empty($images)): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-800">Project Gallery</h2>
                        </div>

                        <!-- Main Image -->
                        <div class="p-6">
                            <div class="mb-6">
                                <img src="<?= htmlspecialchars($images[0]) ?>" id="main-image" alt="Project Image"
                                    class="w-full h-80 object-contain rounded-lg cursor-pointer"
                                    onclick="openLightbox('<?= htmlspecialchars($images[0]) ?>')">
                            </div>

                            <!-- Thumbnails -->
                            <?php if (count($images) > 1): ?>
                            <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                                <?php foreach ($images as $index => $image): ?>
                                <img src="<?= htmlspecialchars($image) ?>" alt="Project Image <?= $index + 1 ?>"
                                    class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-75 transition"
                                    onclick="setMainImage('<?= htmlspecialchars($image) ?>')">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Project Description -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-800">Project Details</h2>
                        </div>
                        <div class="p-6">
                            <div class="prose max-w-none text-gray-700">
                                <?= nl2br(htmlspecialchars($project['description'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Project Info -->
                <div>
                    <!-- Project Summary -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-800">Project Summary</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-5">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Progress</p>
                                    <div class="flex items-center">
                                        <div class="flex-1">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <?php 
                                                $progress_color = 'bg-blue-600';
                                                if ($project['progress'] >= 100) {
                                                    $progress_color = 'bg-green-600';
                                                } elseif ($project['progress'] >= 50) {
                                                    $progress_color = 'bg-amber-600';
                                                }
                                                ?>
                                                <div class="<?= $progress_color ?> h-2.5 rounded-full"
                                                    style="width: <?= $project['progress'] ?>%"></div>
                                            </div>
                                        </div>
                                        <span class="ml-2 text-sm font-medium"><?= $project['progress'] ?>%</span>
                                    </div>
                                </div>

                                <?php if (!empty($project['budget_allocation'])): ?>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Budget Allocation</p>
                                    <p class="text-lg font-medium text-gray-900">
                                        GHS <?= number_format($project['budget_allocation'], 2) ?>
                                    </p>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($project['people_benefitted'])): ?>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">People Benefitted</p>
                                    <p class="text-lg font-medium text-gray-900">
                                        <?= number_format($project['people_benefitted']) ?> people
                                    </p>
                                </div>
                                <?php endif; ?>

                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Project Timeline</p>
                                    <?php
                                    // Calculate timeline progress
                                    $start = strtotime($project['start_date']);
                                    $now = time();
                                    $end = !empty($project['end_date']) ? strtotime($project['end_date']) : null;

                                    if ($end) {
                                        $total_duration = $end - $start;
                                        $elapsed = $now - $start;
                                        $timeline_progress = min(100, max(0, ($elapsed / $total_duration) * 100));
                                    } else {
                                        $timeline_progress = 0;
                                    }
                                    ?>

                                    <?php if ($end): ?>
                                    <div class="relative mb-2">
                                        <div class="bg-gray-200 rounded-full h-2.5 mb-2">
                                            <div class="h-2.5 rounded-full bg-blue-600"
                                                style="width: <?= $timeline_progress ?>%"></div>
                                        </div>
                                        <div class="absolute -top-1 left-<?= $timeline_progress ?>% transform -translate-x-1/2 text-blue-500"
                                            style="width: 20px;">
                                            <i class="fas fa-flag"></i>
                                        </div>
                                    </div>

                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php
                                        if ($now > $end) {
                                            echo "Project completed";
                                        } else {
                                            $days_left = ceil(($end - $now) / (60 * 60 * 24));
                                            echo "$days_left day" . ($days_left > 1 ? "s" : "") . " remaining";
                                        }
                                        ?>
                                    </p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($project['electoral_area_id'])): ?>
                                <?php 
                                    // Fetch electoral area name
                                    $area_query = "SELECT name FROM electoral_areas WHERE id = ?";
                                    $area_stmt = $conn->prepare($area_query);
                                    $area_stmt->bind_param("i", $project['electoral_area_id']);
                                    $area_stmt->execute();
                                    $area_result = $area_stmt->get_result();
                                    $electoral_area = $area_result->fetch_assoc();
                                ?>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Electoral Area</p>
                                    <p class="text-lg font-medium text-gray-900">
                                        <?= htmlspecialchars($electoral_area['name'] ?? 'Unknown') ?>
                                    </p>
                                </div>
                                <?php endif; ?>

                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Created On</p>
                                    <div class="text-lg font-medium text-gray-900">
                                        <?= date('F d, Y', strtotime($project['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($project['featured'] == 1): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-star text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    This is a featured project highlighted on the website homepage.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Related Projects -->
            <?php if (!empty($related_projects)): ?>
            <section class="mt-8">
                <h2 class="text-2xl font-semibold mb-6">Related Projects</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($related_projects as $related): ?>
                    <?php
                        $rel_images = [];
                        if (!empty($related['images'])) {
                            $rel_images = json_decode($related['images'], true) ?: [];
                        }
                        $rel_thumbnail = !empty($rel_images) ? $rel_images[0] : '../assets/images/projects/default-project.jpg';
                        
                        $rel_status_class = match($related['status']) {
                            'planned' => 'bg-blue-100 text-blue-800',
                            'ongoing' => 'bg-yellow-100 text-yellow-800',
                            'completed' => 'bg-green-100 text-green-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="relative">
                            <span
                                class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-medium <?= $rel_status_class ?>">
                                <?= ucfirst($related['status']) ?>
                            </span>
                            <img src="<?= htmlspecialchars($rel_thumbnail) ?>"
                                alt="<?= htmlspecialchars($related['title']) ?>" class="w-full h-40 object-cover">
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-lg"><?= htmlspecialchars($related['title']) ?></h3>
                            <p class="text-sm text-gray-600">
                                <?= date('M Y', strtotime($related['start_date'])) ?>
                                <?php if (!empty($related['end_date'])): ?>
                                - <?= date('M Y', strtotime($related['end_date'])) ?>
                                <?php endif; ?>
                            </p>
                            <a href="project-detail.php?id=<?= $related['id'] ?>"
                                class="mt-2 inline-block text-amber-600 hover:underline">
                                View Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <?php include_once '../includes/footer.php'; ?>

    <!-- Lightbox for Images -->
    <script>
    function openLightbox(src) {
        const lb = document.createElement('div');
        lb.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50';
        lb.innerHTML = `
                <img src="${src}" class="max-w-full max-h-full rounded" alt="">
                <button class="absolute top-4 right-4 text-white text-2xl" onclick="document.body.removeChild(this.parentNode)">
                  &times;
                </button>`;
        document.body.appendChild(lb);
    }

    function setMainImage(src) {
        document.getElementById('main-image').src = src;
        document.getElementById('main-image').onclick = function() {
            openLightbox(src);
        };
    }
    </script>
</body>

</html>