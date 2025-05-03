<?php
session_start();

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit;
}

require_once '../../../config/db.php';

// Get the current page for pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Default filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sector_filter = isset($_GET['sector']) ? $_GET['sector'] : '';
$location_filter = isset($_GET['location']) ? $_GET['location'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query based on filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($sector_filter)) {
    $where_conditions[] = "sector = ?";
    $params[] = $sector_filter;
    $param_types .= 's';
}

if (!empty($location_filter)) {
    $where_conditions[] = "location LIKE ?";
    $params[] = "%$location_filter%";
    $param_types .= 's';
}

if (!empty($search_term)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $param_types .= 'sss';
}

if (!empty($date_from)) {
    $where_conditions[] = "start_date >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "end_date <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

// Construct the WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM projects $where_clause";
$stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$total_result = $stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get projects with filters and pagination
$query = "SELECT * FROM projects $where_clause ORDER BY created_at DESC LIMIT ?, ?";

// Add pagination parameters
$params[] = $offset;
$params[] = $records_per_page;
$param_types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get distinct sectors and locations for filter dropdowns
$sectors_query = "SELECT DISTINCT sector FROM projects ORDER BY sector";
$sectors_result = $conn->query($sectors_query);

$locations_query = "SELECT DISTINCT location FROM projects ORDER BY location";
$locations_result = $conn->query($locations_query);

$page_title = "Projects Management - PA Portal";
include '../includes/header.php';
?>

<div class="p-4 sm:ml-64">
    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Constituency Projects</h1>
        <a href="create.php" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Add New Project
        </a>
    </div>

    <!-- Filters card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Filter Projects</h6>
            <?php if (isset($_GET['filter'])): ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row">
                <input type="hidden" name="filter" value="true">

                <!-- Search box -->
                <div class="col-md-4 mb-3">
                    <label for="search">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                        placeholder="Search by title, description or location"
                        value="<?= htmlspecialchars($search_term) ?>">
                </div>

                <!-- Status filter -->
                <div class="col-md-2 mb-3">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="planned" <?= $status_filter === 'planned' ? 'selected' : '' ?>>Planned</option>
                        <option value="ongoing" <?= $status_filter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed
                        </option>
                    </select>
                </div>

                <!-- Sector filter -->
                <div class="col-md-2 mb-3">
                    <label for="sector">Sector</label>
                    <select class="form-control" id="sector" name="sector">
                        <option value="">All Sectors</option>
                        <?php while ($sector = $sectors_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($sector['sector']) ?>"
                            <?= $sector_filter === $sector['sector'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sector['sector']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Location filter -->
                <div class="col-md-4 mb-3">
                    <label for="location">Location</label>
                    <select class="form-control" id="location" name="location">
                        <option value="">All Locations</option>
                        <?php while ($location = $locations_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($location['location']) ?>"
                            <?= $location_filter === $location['location'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($location['location']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Date range -->
                <div class="col-md-3 mb-3">
                    <label for="date_from">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from"
                        value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="date_to">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                        value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <!-- Submit button -->
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                    <button type="button" class="btn btn-success ml-2" id="exportBtn">
                        <i class="fas fa-file-export"></i> Export Results
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Projects grid -->
    <div class="row">
        <?php if ($result->num_rows > 0): ?>
        <?php while ($project = $result->fetch_assoc()): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <!-- Project Status Badge -->
                <div class="position-absolute" style="top: 10px; right: 10px;">
                    <?php 
                            $status_class = match($project['status']) {
                                'planned' => 'bg-info',
                                'ongoing' => 'bg-warning',
                                'completed' => 'bg-success',
                                default => 'bg-secondary'
                            };
                            ?>
                    <span class="badge <?= $status_class ?> text-white px-3 py-2">
                        <?= ucfirst($project['status']) ?>
                    </span>
                </div>

                <!-- Project Image (thumbnail from images JSON) -->
                <?php 
                        $image_url = '../../../assets/images/projects/default-project.jpg';
                        if (!empty($project['images'])) {
                            $images = json_decode($project['images'], true);
                            if (!empty($images) && isset($images[0])) {
                                $image_url = $images[0];
                            }
                        }
                        ?>
                <img src="<?= htmlspecialchars($image_url) ?>" class="card-img-top"
                    alt="<?= htmlspecialchars($project['title']) ?>" style="height: 180px; object-fit: cover;">

                <div class="card-body">
                    <h5 class="card-title text-truncate" title="<?= htmlspecialchars($project['title']) ?>">
                        <?= htmlspecialchars($project['title']) ?>
                    </h5>

                    <div class="mb-2 text-muted small">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($project['location']) ?>
                    </div>

                    <div class="mb-3 text-muted small">
                        <i class="fas fa-calendar-alt"></i>
                        <?= date('M d, Y', strtotime($project['start_date'])) ?>
                        <?php if ($project['end_date']): ?>
                        - <?= date('M d, Y', strtotime($project['end_date'])) ?>
                        <?php endif; ?>
                    </div>

                    <p class="card-text" style="height: 4.5em; overflow: hidden;">
                        <?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...
                    </p>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="view.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <div>
                            <a href="edit.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="upload-photos.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-images"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        Sector: <strong><?= htmlspecialchars($project['sector']) ?></strong>
                    </small>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No projects found matching your criteria.
                <?php if (isset($_GET['filter'])): ?>
                <a href="index.php" class="alert-link">Clear filters</a> to see all projects.
                <?php else: ?>
                <a href="create.php" class="alert-link">Create your first project</a>.
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <!-- Previous page link -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="?page=<?= $page - 1 ?><?= isset($_GET['filter']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>"
                    aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <!-- Page number links -->
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link"
                    href="?page=<?= $i ?><?= isset($_GET['filter']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>

            <!-- Next page link -->
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="?page=<?= $page + 1 ?><?= isset($_GET['filter']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>"
                    aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        // Get current filter parameters
        const urlParams = new URLSearchParams(window.location.search);
        let exportUrl = 'export-projects.php';

        // Add filters to export URL
        if (urlParams.has('filter')) {
            exportUrl += '?' + window.location.search.substr(1);
        }

        window.location.href = exportUrl;
    });
});
</script>

<?php include '../includes/footer.php'; ?>