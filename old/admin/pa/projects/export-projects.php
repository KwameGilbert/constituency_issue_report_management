<?php
session_start();
require_once '../../../config/db.php';

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit;
}

// Get PA ID from session
$pa_id = $_SESSION['pa_id'];

// Default filters - same as in index.php
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sector_filter = isset($_GET['sector']) ? $_GET['sector'] : '';
$location_filter = isset($_GET['location']) ? $_GET['location'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query based on filters
$where_conditions = ["pa_id = ?"]; // Always filter by PA ID for security
$params = [$pa_id];
$param_types = 'i';

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
$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get electoral area information for the exported data
$query = "SELECT p.*, ea.name as electoral_area_name 
          FROM projects p 
          LEFT JOIN electoral_areas ea ON p.electoral_area_id = ea.id 
          $where_clause 
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get PA information for export filename
$pa_query = "SELECT REPLACE(name, ' ', '_') as pa_name FROM personal_assistants WHERE id = ?";
$pa_stmt = $conn->prepare($pa_query);
$pa_stmt->bind_param("i", $pa_id);
$pa_stmt->execute();
$pa_result = $pa_stmt->get_result();
$pa_info = $pa_result->fetch_assoc();
$pa_name = !empty($pa_info['pa_name']) ? $pa_info['pa_name'] : 'pa_projects';

// Set the filename for export with date for uniqueness
$timestamp = date('Y-m-d_H-i-s');
$filename = $pa_name . "_projects_export_" . $timestamp . ".csv";

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create a file handle for PHP output
$output = fopen('php://output', 'w');

// Add UTF-8 BOM to fix Excel's encoding issues with CSV
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Define CSV columns headers
fputcsv($output, [
    'ID',
    'Title',
    'Description',
    'Electoral Area',
    'Location',
    'Sector',
    'People Benefitted',
    'Budget Allocation (GHS)',
    'Status',
    'Progress (%)',
    'Start Date',
    'End Date',
    'Featured',
    'Created At',
    'Last Updated'
]);

// Add data to CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format featured field to be more readable
        $featured = $row['featured'] ? 'Yes' : 'No';
        
        // Format dates for better readability
        $start_date = !empty($row['start_date']) ? date('d/m/Y', strtotime($row['start_date'])) : 'N/A';
        $end_date = !empty($row['end_date']) ? date('d/m/Y', strtotime($row['end_date'])) : 'N/A';
        $created_at = !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : 'N/A';
        $updated_at = !empty($row['updated_at']) ? date('d/m/Y H:i', strtotime($row['updated_at'])) : 'N/A';
        
        // Format status to be capitalized
        $status = ucfirst($row['status']);
        
        // Format budget with commas for thousands
        $budget = !empty($row['budget_allocation']) ? number_format($row['budget_allocation'], 2) : 'N/A';
        
        // Clean up description text for CSV output - remove HTML tags and ensure line breaks work
        $description = strip_tags(html_entity_decode($row['description']));
        $description = str_replace(["\r\n", "\r", "\n"], " ", $description);
        
        // Write row to CSV
        fputcsv($output, [
            $row['id'],
            $row['title'],
            $description,
            $row['electoral_area_name'],
            $row['location'],
            $row['sector'],
            $row['people_benefitted'],
            $budget,
            $status,
            $row['progress'],
            $start_date,
            $end_date,
            $featured,
            $created_at,
            $updated_at
        ]);
    }
} else {
    // If no data, output a message in the CSV
    fputcsv($output, ['No projects found matching your filter criteria.']);
}

// Close the file handle
fclose($output);
exit;
?>