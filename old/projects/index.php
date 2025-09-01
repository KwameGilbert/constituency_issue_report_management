<?php
require_once '../config/db.php';

// Handle filtering
$sector = $_GET['sector'] ?? '';
$location = $_GET['location'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause - ALWAYS include featured=1
$where = ["featured = 1"];  // This ensures only featured projects are shown
$params = [];
$param_types = '';

if (!empty($search)) {
    $where[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if (!empty($sector)) {
    $where[] = "sector = ?";
    $params[] = $sector;
    $param_types .= 's';
}

if (!empty($location)) {
    $where[] = "location = ?";
    $params[] = $location;
    $param_types .= 's';
}

if (!empty($status)) {
    $where[] = "status = ?";
    $params[] = $status;
    $param_types .= 's';
}

// Pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$projects_per_page = 12;
$offset = ($current_page - 1) * $projects_per_page;

// Prepare the WHERE clause - note we always have at least one condition (featured=1)
$where_clause = "WHERE " . implode(" AND ", $where);

// Count total projects for pagination
$count_sql = "SELECT COUNT(*) as total FROM projects $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$total_projects = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_projects / $projects_per_page);

// Fetch projects with pagination
$sql = "SELECT * FROM projects $where_clause ORDER BY start_date DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

// Add pagination parameters
$params[] = $offset;
$params[] = $projects_per_page;
$param_types .= 'ii';

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch filters options - but only from FEATURED projects
$sectors = $conn->query("SELECT DISTINCT sector FROM projects WHERE featured = 1 ORDER BY sector")->fetch_all(MYSQLI_ASSOC);
$locations = $conn->query("SELECT DISTINCT location FROM projects WHERE featured = 1 ORDER BY location")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constituency Projects | SWMA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
</head>

<body class="bg-gray-50">
    <?php include_once '../includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="bg-amber-600 text-white py-12 md:py-20">
            <div class="max-w-6xl mx-auto px-4">
                <h1 class="text-3xl md:text-4xl font-bold mb-4">Constituency Projects</h1>
                <p class="text-lg md:text-xl max-w-3xl">Discover the development initiatives and community projects
                    being implemented across our constituency.</p>
            </div>
        </section>

        <!-- Projects Section -->
        <section class="py-12">
            <div class="max-w-6xl mx-auto px-4">
                <!-- Filters and Search -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4">Find Projects</h2>
                    <form action="" method="GET" class="space-y-4 md:space-y-0 md:flex md:flex-wrap md:gap-4">
                        <!-- Search -->
                        <div class="md:w-full lg:w-1/3">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search by title, description or location"
                                class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                        </div>

                        <!-- Sector filter -->
                        <div class="md:w-1/3 lg:w-1/5">
                            <label for="sector" class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                            <select name="sector" id="sector"
                                class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                                <option value="">All Sectors</option>
                                <?php foreach ($sectors as $s): ?>
                                <option value="<?= htmlspecialchars($s['sector']) ?>"
                                    <?= $s['sector'] === $sector ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['sector']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Location filter -->
                        <div class="md:w-1/3 lg:w-1/5">
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <select name="location" id="location"
                                class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $l): ?>
                                <option value="<?= htmlspecialchars($l['location']) ?>"
                                    <?= $l['location'] === $location ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['location']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status filter -->
                        <div class="md:w-1/3 lg:w-1/5">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status"
                                class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                                <option value="">All Statuses</option>
                                <option value="planned" <?= $status === 'planned' ? 'selected' : '' ?>>Planned</option>
                                <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed
                                </option>
                            </select>
                        </div>

                        <!-- Filter buttons -->
                        <div class="md:flex md:items-end md:gap-2">
                            <button type="submit"
                                class="w-full md:w-auto px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 transition-colors">
                                <i class="fas fa-search mr-2"></i> Apply Filters
                            </button>
                            <?php if (!empty($search) || !empty($sector) || !empty($location) || !empty($status)): ?>
                            <a href="index.php"
                                class="mt-2 md:mt-0 inline-block w-full md:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors text-center">
                                <i class="fas fa-times mr-2"></i> Clear Filters
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Project Results -->
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold mb-6">
                        <?= empty($where) ? 'All Projects' : 'Filtered Projects' ?>
                        <span class="text-gray-500 text-lg">(<?= $total_projects ?>)</span>
                    </h2>

                    <?php if (count($projects) > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($projects as $project): ?>
                        <?php
                            $images = [];
                            if (!empty($project['images'])) {
                                $images = json_decode($project['images'], true) ?: [];
                            }
                            $thumbnail = !empty($images) ? $images[0] : '../assets/images/projects/default-project.jpg';
                            
                            $status_class = match($project['status']) {
                                'planned' => 'bg-blue-100 text-blue-800',
                                'ongoing' => 'bg-yellow-100 text-yellow-800',
                                'completed' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?>
                        <div
                            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <!-- Project Status Badge -->
                            <div class="relative">
                                <span
                                    class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                                    <?= ucfirst($project['status']) ?>
                                </span>

                                <!-- Project Image -->
                                <img src="<?= htmlspecialchars($thumbnail) ?>"
                                    alt="<?= htmlspecialchars($project['title']) ?>" class="w-full h-48 object-cover">
                            </div>

                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 truncate"
                                    title="<?= htmlspecialchars($project['title']) ?>">
                                    <?= htmlspecialchars($project['title']) ?>
                                </h3>

                                <div class="flex items-center text-sm text-gray-500 mb-2">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <span class="truncate"><?= htmlspecialchars($project['location']) ?></span>
                                </div>

                                <div class="flex items-center text-sm text-gray-500 mb-3">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    <span>
                                        <?= date('M d, Y', strtotime($project['start_date'])) ?>
                                        <?php if (!empty($project['end_date'])): ?>
                                        - <?= date('M d, Y', strtotime($project['end_date'])) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                                    <?= htmlspecialchars(substr($project['description'], 0, 150)) ?>...
                                </p>

                                <div class="mt-4 border-t pt-4 flex justify-between items-center">
                                    <div class="text-xs text-gray-500">
                                        Sector: <span
                                            class="font-medium"><?= htmlspecialchars($project['sector']) ?></span>
                                    </div>

                                    <a href="project-detail.php?id=<?= $project['id'] ?>"
                                        class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-amber-600 rounded hover:bg-amber-700">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="mt-8">
                        <nav class="flex justify-center">
                            <ul class="flex">
                                <!-- Previous page -->
                                <?php if ($current_page > 1): ?>
                                <a href="?page=<?= $current_page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($sector) ? '&sector=' . urlencode($sector) : '' ?><?= !empty($location) ? '&location=' . urlencode($location) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left h-5 w-5"></i>
                                </a>
                                <?php else: ?>
                                <span
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-left h-5 w-5"></i>
                                </span>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                if ($start_page > 1) {
                                    echo '<a href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sector) ? '&sector=' . urlencode($sector) : '') . (!empty($location) ? '&location=' . urlencode($location) : '') . (!empty($status) ? '&status=' . urlencode($status) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                
                                    if ($start_page > 2) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                }

                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    if ($i == $current_page) {
                                        echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-amber-500 bg-amber-50 text-sm font-medium text-amber-600">' . $i . '</span>';
                                    } else {
                                        echo '<a href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sector) ? '&sector=' . urlencode($sector) : '') . (!empty($location) ? '&location=' . urlencode($location) : '') . (!empty($status) ? '&status=' . urlencode($status) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $i . '</a>';
                                    }
                                }

                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                    echo '<a href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sector) ? '&sector=' . urlencode($sector) : '') . (!empty($location) ? '&location=' . urlencode($location) : '') . (!empty($status) ? '&status=' . urlencode($status) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                }
                                ?>

                                <!-- Next page -->
                                <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($sector) ? '&sector=' . urlencode($sector) : '' ?><?= !empty($location) ? '&location=' . urlencode($location) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right h-5 w-5"></i>
                                </a>
                                <?php else: ?>
                                <span
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-right h-5 w-5"></i>
                                </span>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-8 text-center">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600 mb-4">
                            <i class="fas fa-project-diagram text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No projects found</h3>
                        <p class="text-gray-500 mb-6">
                            <?php if (!empty($where)): ?>
                            No projects match your filter criteria. Try adjusting your filters.
                            <?php else: ?>
                            There are no projects available at the moment.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($where)): ?>
                        <a href="index.php"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i> Clear Filters
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include_once '../includes/footer.php'; ?>

    <!-- Add project detail page script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when filters change
        const filterForm = document.querySelector('form');
        const filterSelects = filterForm.querySelectorAll('select');

        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    });
    </script>
</body>

</html>