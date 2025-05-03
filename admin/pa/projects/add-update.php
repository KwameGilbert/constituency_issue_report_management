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

// Verify project exists and belongs to the PA
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

// Check if table exists, if not create it
$table_check = "SHOW TABLES LIKE 'project_updates'";
$table_result = $conn->query($table_check);

if ($table_result->num_rows == 0) {
    // Create project_updates table
    $create_table = "CREATE TABLE project_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('planned', 'ongoing', 'completed') DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($create_table)) {
        $_SESSION['error'] = "Failed to create project updates table: " . $conn->error;
        header("Location: view.php?id=" . $project_id);
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = isset($_POST['status']) ? trim($_POST['status']) : null;
    
    // Validate title
    if (empty($title)) {
        $_SESSION['error'] = "Update title is required.";
        header("Location: add-update.php?id=" . $project_id);
        exit;
    }
    
    // Validate description
    if (empty($description)) {
        $_SESSION['error'] = "Update description is required.";
        header("Location: add-update.php?id=" . $project_id);
        exit;
    }
    
    // Insert update
    $query = "INSERT INTO project_updates (project_id, title, description, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $project_id, $title, $description, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Project update added successfully.";
        
        // If status is set, update the project status as well
        if (!empty($status)) {
            $update_project = "UPDATE projects SET status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_project);
            $update_stmt->bind_param("si", $status, $project_id);
            $update_stmt->execute();
        }
        
        header("Location: view.php?id=" . $project_id);
        exit;
    } else {
        $_SESSION['error'] = "Failed to add update: " . $conn->error;
        header("Location: add-update.php?id=" . $project_id);
        exit;
    }
}

// Set page title
$page_title = "Add Update - " . $project['title'];
include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Projects</a></li>
            <li class="breadcrumb-item"><a
                    href="view.php?id=<?= $project_id ?>"><?= htmlspecialchars($project['title']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Add Update</li>
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

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add Project Update</h1>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Add Update for: <?= htmlspecialchars($project['title']) ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form action="add-update.php?id=<?= $project_id ?>" method="POST">
                        <div class="form-group">
                            <label for="title">Update Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                placeholder="e.g., Phase 1 Completed, Weekly Progress, etc.">
                        </div>

                        <div class="form-group">
                            <label for="description">Update Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required
                                placeholder="Describe the progress or changes in the project..."></textarea>
                            <small class="form-text text-muted">
                                Provide detailed information about what has been accomplished, current challenges,
                                next steps, etc.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="status">Update Project Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">No change</option>
                                <option value="planned">Planned</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                            </select>
                            <small class="form-text text-muted">
                                If selected, this will also update the overall project status.
                            </small>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-6">
                                <a href="view.php?id=<?= $project_id ?>" class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus-circle"></i> Add Update
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>