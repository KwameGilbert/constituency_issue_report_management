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

<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Projects</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($project['title']) ?></li>
        </ol>
    </nav>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Project Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($project['title']) ?></h1>
        <div>
            <a href="edit.php?id=<?= $project_id ?>" class="btn btn-primary btn-sm mr-2">
                <i class="fas fa-edit fa-sm"></i> Edit Project
            </a>
            <a href="upload-photos.php?id=<?= $project_id ?>" class="btn btn-info btn-sm mr-2">
                <i class="fas fa-images fa-sm"></i> Manage Photos
            </a>
            <a href="add-update.php?id=<?= $project_id ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus-circle fa-sm"></i> Add Update
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Project Details Card -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Project Details</h6>
                    <?php 
                    $status_class = match($project['status']) {
                        'planned' => 'badge-info',
                        'ongoing' => 'badge-warning',
                        'completed' => 'badge-success',
                        default => 'badge-secondary'
                    };
                    ?>
                    <span class="badge <?= $status_class ?> px-3 py-2">
                        <?= ucfirst($project['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <!-- Project Images Carousel -->
                    <?php if (!empty($images)): ?>
                    <div id="projectCarousel" class="carousel slide mb-4" data-ride="carousel">
                        <ol class="carousel-indicators">
                            <?php foreach ($images as $index => $image): ?>
                            <li data-target="#projectCarousel" data-slide-to="<?= $index ?>"
                                <?= $index === 0 ? 'class="active"' : '' ?>></li>
                            <?php endforeach; ?>
                        </ol>
                        <div class="carousel-inner">
                            <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="<?= htmlspecialchars($image) ?>" class="d-block w-100" alt="Project Image"
                                    style="height: 400px; object-fit: cover;">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <a class="carousel-control-prev" href="#projectCarousel" role="button" data-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#projectCarousel" role="button" data-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="text-center mb-4">
                        <img src="../../../assets/images/projects/default-project.jpg" class="img-fluid rounded"
                            alt="Default Project Image" style="max-height: 400px;">
                    </div>
                    <?php endif; ?>

                    <!-- Project Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Location:</strong> <?= htmlspecialchars($project['location']) ?></p>
                            <p><strong>Sector:</strong> <?= htmlspecialchars($project['sector']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>Start Date:</strong> <?= date('F d, Y', strtotime($project['start_date'])) ?>
                            </p>
                            <?php if (!empty($project['end_date'])): ?>
                            <p>
                                <strong>End Date:</strong> <?= date('F d, Y', strtotime($project['end_date'])) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="font-weight-bold">Description</h5>
                        <p class="text-justify"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
                    </div>

                    <?php if ($project['featured']): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-star mr-2"></i> This project is featured on the website homepage.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Project Updates Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Project Updates</h6>
                    <a href="add-update.php?id=<?= $project_id ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus-circle"></i> Add Update
                    </a>
                </div>
                <div class="card-body">
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
                            echo '<div class="timeline">';
                            while ($update = $updates_result->fetch_assoc()) {
                                ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h3 class="timeline-title">
                                <?= htmlspecialchars($update['title']) ?>
                                <small class="text-muted ml-2">
                                    <?= date('M d, Y', strtotime($update['created_at'])) ?>
                                </small>
                            </h3>
                            <p><?= nl2br(htmlspecialchars($update['description'])) ?></p>

                            <?php if (!empty($update['status'])): ?>
                            <span class="badge badge-pill 
                                                <?php 
                                                echo match($update['status']) {
                                                    'planned' => 'badge-info',
                                                    'ongoing' => 'badge-warning',
                                                    'completed' => 'badge-success',
                                                    default => 'badge-secondary'
                                                };
                                                ?>">
                                <?= ucfirst($update['status']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
                                    <p>No updates have been added to this project yet.</p>
                                    <a href="add-update.php?id=' . $project_id . '" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus-circle"></i> Add First Update
                                    </a>
                                </div>';
                        }
                    } else {
                        echo '<div class="alert alert-warning">
                                Project updates feature is not available. The required database table does not exist.
                            </div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Project Comments Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Project Comments</h6>
                </div>
                <div class="card-body">
                    <!-- Fetch project comments if there is a project_comments table -->
                    <?php
                    $comments_query = "SHOW TABLES LIKE 'project_comments'";
                    $comments_result = $conn->query($comments_query);
                    
                    if ($comments_result->num_rows > 0) {
                        // Add new comment form
                        ?>
                    <form action="add-comment.php" method="POST" class="mb-4">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">
                        <div class="form-group">
                            <label for="comment">Add a comment</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Comment</button>
                    </form>

                    <hr>

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
                            while ($comment = $comments_result->fetch_assoc()) {
                                ?>
                    <div class="media mb-4">
                        <div class="mr-3">
                            <i class="fas fa-user-circle fa-3x text-gray-300"></i>
                        </div>
                        <div class="media-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mt-0"><?= htmlspecialchars($comment['author_name']) ?></h5>
                                <small class="text-muted">
                                    <?= date('M d, Y g:i A', strtotime($comment['created_at'])) ?>
                                </small>
                            </div>
                            <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                        </div>
                    </div>
                    <?php
                            }
                        } else {
                            echo '<div class="text-center py-5">
                                    <i class="far fa-comments fa-3x text-gray-300 mb-3"></i>
                                    <p>No comments have been added to this project yet.</p>
                                    <p>Be the first to leave a comment!</p>
                                </div>';
                        }
                    } else {
                        echo '<div class="alert alert-warning">
                                Project comments feature is not available. The required database table does not exist.
                            </div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Project Sidebar -->
        <div class="col-lg-4">
            <!-- Project Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Project Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Project Status
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= ucfirst($project['status']) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Project Duration
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
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
                                
                                echo $duration;
                            } else {
                                echo "Not specified";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Project Timeline
                        </div>
                        <div class="progress mb-2">
                            <?php
                            $progress = 0;
                            $status_text = "Not started";
                            
                            if ($project['status'] == 'completed') {
                                $progress = 100;
                                $status_text = "Completed";
                            } else if ($project['status'] == 'ongoing') {
                                // Calculate progress based on dates
                                if (!empty($project['end_date'])) {
                                    $start = strtotime($project['start_date']);
                                    $end = strtotime($project['end_date']);
                                    $now = time();
                                    
                                    if ($now >= $end) {
                                        $progress = 100;
                                    } else if ($now <= $start) {
                                        $progress = 0;
                                    } else {
                                        $total_duration = $end - $start;
                                        $time_passed = $now - $start;
                                        $progress = ($time_passed / $total_duration) * 100;
                                    }
                                } else {
                                    $progress = 50; // Default for ongoing with no end date
                                }
                                
                                $status_text = "In progress";
                            } else {
                                $status_text = "Planned";
                            }
                            
                            $progress_class = "bg-info";
                            if ($progress >= 100) {
                                $progress_class = "bg-success";
                            } else if ($progress >= 50) {
                                $progress_class = "bg-warning";
                            }
                            ?>
                            <div class="progress-bar <?= $progress_class ?>" role="progressbar"
                                style="width: <?= round($progress) ?>%" aria-valuenow="<?= round($progress) ?>"
                                aria-valuemin="0" aria-valuemax="100">
                                <?= round($progress) ?>%
                            </div>
                        </div>
                        <small class="text-muted"><?= $status_text ?></small>
                    </div>

                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Created On
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= date('F d, Y', strtotime($project['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Entities Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Assigned Entities</h6>
                    <a href="assign-entity.php?id=<?= $project_id ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle"></i> Assign Entity
                    </a>
                </div>
                <div class="card-body">
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
                            echo '<div class="list-group">';
                            while ($entity = $entities_result->fetch_assoc()) {
                                ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($entity['name']) ?></h6>
                            <small class="text-muted"><?= ucfirst($entity['type']) ?></small>
                        </div>
                        <?php if (!empty($entity['contact'])): ?>
                        <p class="mb-1 small">
                            <i class="fas fa-phone-alt mr-1"></i> <?= htmlspecialchars($entity['contact']) ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($entity['role'])): ?>
                        <p class="mb-1 small">
                            <strong>Role:</strong> <?= htmlspecialchars($entity['role']) ?>
                        </p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-end mt-2">
                            <a href="../entities/view.php?id=<?= $entity['entity_id'] ?>"
                                class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="remove-entity.php?project_id=<?= $project_id ?>&entity_id=<?= $entity['entity_id'] ?>"
                                class="btn btn-sm btn-outline-danger ml-2"
                                onclick="return confirm('Are you sure you want to remove this entity from the project?')">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <?php
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="text-center py-5">
                                    <i class="fas fa-building fa-3x text-gray-300 mb-3"></i>
                                    <p>No entities have been assigned to this project yet.</p>
                                    <a href="assign-entity.php?id=' . $project_id . '" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus-circle"></i> Assign Entity
                                    </a>
                                </div>';
                        }
                    } else {
                        echo '<div class="alert alert-warning">
                                Entity assignment feature is not available. The required database table does not exist.
                            </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Timeline Styles -->
<style>
.timeline {
    position: relative;
    padding: 1rem;
    margin: 0 auto;
}

.timeline::before {
    content: '';
    position: absolute;
    height: 100%;
    border: 1px solid #e3e6f0;
    left: 40px;
    top: 0;
}

.timeline-item {
    position: relative;
    margin-left: 60px;
    margin-bottom: 2rem;
}

.timeline-marker {
    position: absolute;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #4e73df;
    background: #fff;
    margin-top: 10px;
    left: -53px;
}

.timeline-title {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}
</style>

<?php include '../includes/footer.php'; ?>