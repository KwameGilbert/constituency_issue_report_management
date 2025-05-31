<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if (!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit;
}

// Include database connection
require_once '../../../config/db.php';

// Get officer details
$officer_id = $_SESSION['officer_id'];
$query = "SELECT * FROM field_officers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$result = $stmt->get_result();
$officer = $result->fetch_assoc();
$stmt->close();

// Set active page and title
$active_page = 'reports';
$pageTitle = 'Generate Report';
$basePath = '../';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ./");
    exit;
}

// Get form data
$report_period = isset($_POST['report_period']) ? $_POST['report_period'] : 'all';
$report_format = isset($_POST['report_format']) ? $_POST['report_format'] : 'pdf';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// Build date clause for SQL
$date_clause = '';

switch ($report_period) {
    case 'week':
        $date_clause = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $period_text = "Past Week";
        break;
    case 'month':
        $date_clause = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $period_text = "Past Month";
        break;
    case 'quarter':
        $date_clause = "AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        $period_text = "Past Quarter";
        break;
    case 'year':
        $date_clause = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $period_text = "Past Year";
        break;
    case 'custom':
        if (!empty($start_date) && !empty($end_date)) {
            $date_clause = "AND created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            $period_text = "From " . date('M j, Y', strtotime($start_date)) . " to " . date('M j, Y', strtotime($end_date));
        }
        break;
    default:
        $date_clause = "";
        $period_text = "All Time";
}

// Get summary statistics
$stats_query = "SELECT 
                COUNT(*) as total_issues,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_issues,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_issues,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_issues,
                SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_issues,
                SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium_issues,
                SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_issues
                FROM issues WHERE officer_id = ? $date_clause";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $officer_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Resolution rate calculation
$resolution_rate = 0;
if ($stats['total_issues'] > 0) {
    $resolution_rate = round(($stats['resolved_issues'] / $stats['total_issues']) * 100);
}

// Average resolution time
$avg_time_query = "SELECT 
                  AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_days
                  FROM issues 
                  WHERE officer_id = ? AND status = 'resolved' $date_clause";

$avg_time_stmt = $conn->prepare($avg_time_query);
$avg_time_stmt->bind_param("i", $officer_id);
$avg_time_stmt->execute();
$avg_time_result = $avg_time_stmt->get_result();
$avg_time_row = $avg_time_result->fetch_assoc();
$avg_resolution_days = round($avg_time_row['avg_days'] ?? 0);

// Electoral area breakdown
$area_query = "SELECT 
               ea.name as area_name,
               COUNT(issues.id) as issue_count
               FROM issues
               LEFT JOIN electoral_areas ea ON issues.electoral_area_id = ea.id
               WHERE issues.officer_id = ? $date_clause
               GROUP BY issues.electoral_area_id
               ORDER BY issue_count DESC";

$area_stmt = $conn->prepare($area_query);
$area_stmt->bind_param("i", $officer_id);
$area_stmt->execute();
$area_result = $area_stmt->get_result();
$area_breakdown = [];

while ($row = $area_result->fetch_assoc()) {
    $area_name = $row['area_name'] ? $row['area_name'] : 'Unassigned';
    $area_breakdown[] = [
        'area' => $area_name,
        'count' => $row['issue_count']
    ];
}

// Get all issues for the detailed report table
// Fix the ambiguous 'id' column by specifying the table name
$issues_query = "SELECT 
                issues.id, issues.title, issues.description, issues.location, issues.severity, issues.status, 
                issues.people_affected, issues.created_at, issues.updated_at, ea.name as electoral_area
                FROM issues
                LEFT JOIN electoral_areas ea ON issues.electoral_area_id = ea.id
                WHERE issues.officer_id = ? $date_clause
                ORDER BY issues.created_at DESC";

$issues_stmt = $conn->prepare($issues_query);
$issues_stmt->bind_param("i", $officer_id);
$issues_stmt->execute();
$issues_result = $issues_stmt->get_result();
$issues = [];

while ($row = $issues_result->fetch_assoc()) {
    $issues[] = $row;
}

// Generate report based on selected format
if ($report_format === 'excel') {
    // Generate Excel file
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="issue_report_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Excel file content
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Issue Report</title>
    </head>
    <body>
        <h1>Issue Report - ' . htmlspecialchars($officer['name']) . '</h1>
        <h2>Period: ' . $period_text . '</h2>
        <h3>Generated on: ' . date('F j, Y, g:i a') . '</h3>
        
        <h2>Summary Statistics</h2>
        <table border="1">
            <tr>
                <th>Total Issues</th>
                <th>Pending</th>
                <th>In Progress</th>
                <th>Resolved</th>
                <th>Rejected</th>
                <th>Resolution Rate</th>
                <th>Avg. Resolution Time</th>
            </tr>
            <tr>
                <td>' . $stats['total_issues'] . '</td>
                <td>' . $stats['pending_issues'] . '</td>
                <td>' . $stats['in_progress_issues'] . '</td>
                <td>' . $stats['resolved_issues'] . '</td>
                <td>' . $stats['rejected_issues'] . '</td>
                <td>' . $resolution_rate . '%</td>
                <td>' . $avg_resolution_days . ' days</td>
            </tr>
        </table>
        
        <h2>Severity Breakdown</h2>
        <table border="1">
            <tr>
                <th>Critical</th>
                <th>High</th>
                <th>Medium</th>
                <th>Low</th>
            </tr>
            <tr>
                <td>' . $stats['critical_issues'] . '</td>
                <td>' . $stats['high_issues'] . '</td>
                <td>' . $stats['medium_issues'] . '</td>
                <td>' . $stats['low_issues'] . '</td>
            </tr>
        </table>
        
        <h2>Electoral Area Breakdown</h2>
        <table border="1">
            <tr>
                <th>Electoral Area</th>
                <th>Issue Count</th>
            </tr>';
            
    foreach ($area_breakdown as $area) {
        echo '<tr>
                <td>' . htmlspecialchars($area['area']) . '</td>
                <td>' . $area['count'] . '</td>
            </tr>';
    }
    
    echo '</table>
        
        <h2>Detailed Issue List</h2>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Electoral Area</th>
                <th>Severity</th>
                <th>Status</th>
                <th>People Affected</th>
                <th>Created</th>
                <th>Last Updated</th>
            </tr>';
            
    foreach ($issues as $issue) {
        echo '<tr>
                <td>' . $issue['id'] . '</td>
                <td>' . htmlspecialchars($issue['title']) . '</td>
                <td>' . htmlspecialchars($issue['electoral_area'] ?? 'Unassigned') . '</td>
                <td>' . ucfirst($issue['severity']) . '</td>
                <td>' . ucfirst(str_replace('_', ' ', $issue['status'])) . '</td>
                <td>' . $issue['people_affected'] . '</td>
                <td>' . date('Y-m-d', strtotime($issue['created_at'])) . '</td>
                <td>' . date('Y-m-d', strtotime($issue['updated_at'])) . '</td>
            </tr>';
    }
    
    echo '</table>
    </body>
    </html>';
    
    exit;
} elseif ($report_format === 'pdf') {
    // Requires the TCPDF library - not available in this workspace
    // We'll create a printable page with a message to install the library
    require_once '../../../vendor/autoload.php';
    
    try {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            throw new Exception("TCPDF library not found");
        }
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Constituency Issue Management System');
        $pdf->SetAuthor(htmlspecialchars($officer['name']));
        $pdf->SetTitle('Issue Report');
        $pdf->SetSubject('Issue Report - ' . $period_text);
        
        // Set default header and footer data
        $pdf->setHeaderData('', 0, 'Issue Report - ' . htmlspecialchars($officer['name']), 'Generated: ' . date('F j, Y, g:i a'));
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array('helvetica', '', 10));
        $pdf->setFooterFont(Array('helvetica', '', 8));
        
        // Add a page
        $pdf->AddPage();
        
        // Add content
        $html = '<h1>Issue Report - ' . htmlspecialchars($officer['name']) . '</h1>
                <h2>Period: ' . $period_text . '</h2>
                
                <h3>Summary Statistics</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <th>Total Issues</th>
                        <th>Pending</th>
                        <th>In Progress</th>
                        <th>Resolved</th>
                        <th>Rejected</th>
                    </tr>
                    <tr>
                        <td>' . $stats['total_issues'] . '</td>
                        <td>' . $stats['pending_issues'] . '</td>
                        <td>' . $stats['in_progress_issues'] . '</td>
                        <td>' . $stats['resolved_issues'] . '</td>
                        <td>' . $stats['rejected_issues'] . '</td>
                    </tr>
                </table>
                
                <h3>Performance Metrics</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <th>Resolution Rate</th>
                        <th>Avg. Resolution Time</th>
                    </tr>
                    <tr>
                        <td>' . $resolution_rate . '%</td>
                        <td>' . $avg_resolution_days . ' days</td>
                    </tr>
                </table>
                
                <h3>Severity Breakdown</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <th>Critical</th>
                        <th>High</th>
                        <th>Medium</th>
                        <th>Low</th>
                    </tr>
                    <tr>
                        <td>' . $stats['critical_issues'] . '</td>
                        <td>' . $stats['high_issues'] . '</td>
                        <td>' . $stats['medium_issues'] . '</td>
                        <td>' . $stats['low_issues'] . '</td>
                    </tr>
                </table>';
                
        // Add issue table
        $html .= '<h3>Detailed Issue List</h3>
                <table border="1" cellpadding="3">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>';
                    
        foreach ($issues as $issue) {
            $html .= '<tr>
                        <td>' . $issue['id'] . '</td>
                        <td>' . htmlspecialchars($issue['title']) . '</td>
                        <td>' . ucfirst($issue['severity']) . '</td>
                        <td>' . ucfirst(str_replace('_', ' ', $issue['status'])) . '</td>
                        <td>' . date('Y-m-d', strtotime($issue['created_at'])) . '</td>
                    </tr>';
        }
        
        $html .= '</table>';
        
        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('issue_report_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    } catch (Exception $e) {
        // TCPDF library not available, display error message
        $error_message = "PDF generation requires the TCPDF library to be installed. Please contact your system administrator.";
    }
}

// If we reach here, we're showing the printable HTML report
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Report - <?php echo htmlspecialchars($officer['name']); ?></title>
    <style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        color: #333;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    h1,
    h2,
    h3 {
        color: #d97706;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .stats-card {
        display: inline-block;
        width: 23%;
        margin: 1%;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        background-color: #fff;
        text-align: center;
    }

    .stats-value {
        font-size: 24px;
        font-weight: bold;
        margin: 10px 0;
    }

    .stats-label {
        font-size: 14px;
        color: #666;
    }

    .print-controls {
        margin-bottom: 20px;
    }

    .status-label {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
    }

    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-in-progress {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-resolved {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-rejected {
        background-color: #fee2e2;
        color: #b91c1c;
    }

    .severity-critical {
        background-color: #fee2e2;
        color: #b91c1c;
    }

    .severity-high {
        background-color: #fed7aa;
        color: #9a3412;
    }

    .severity-medium {
        background-color: #fef3c7;
        color: #92400e;
    }

    .severity-low {
        background-color: #d1fae5;
        color: #065f46;
    }

    @media print {
        .print-controls {
            display: none;
        }

        body {
            padding: 0;
            font-size: 12px;
        }

        h1 {
            font-size: 18px;
        }

        h2 {
            font-size: 16px;
        }

        h3 {
            font-size: 14px;
        }
    }
    </style>
</head>

<body>
    <div class="print-controls">
        <button onclick="window.print();">Print Report</button>
        <button onclick="window.location.href='./';">Back to Reports</button>
        <?php if (isset($error_message)): ?>
        <div style="color: #b91c1c; margin-top: 15px; padding: 10px; background-color: #fee2e2; border-radius: 4px;">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
    </div>

    <h1>Issue Report - <?php echo htmlspecialchars($officer['name']); ?></h1>
    <p><strong>Period:</strong> <?php echo $period_text; ?> | <strong>Generated:</strong>
        <?php echo date('F j, Y, g:i a'); ?></p>

    <h2>Summary Statistics</h2>
    <div>
        <div class="stats-card">
            <div class="stats-value"><?php echo $stats['total_issues']; ?></div>
            <div class="stats-label">Total Issues</div>
        </div>
        <div class="stats-card">
            <div class="stats-value"><?php echo $resolution_rate; ?>%</div>
            <div class="stats-label">Resolution Rate</div>
        </div>
        <div class="stats-card">
            <div class="stats-value"><?php echo $stats['critical_issues']; ?></div>
            <div class="stats-label">Critical Issues</div>
        </div>
        <div class="stats-card">
            <div class="stats-value"><?php echo $avg_resolution_days; ?> days</div>
            <div class="stats-label">Avg. Resolution Time</div>
        </div>
    </div>

    <h2>Status Breakdown</h2>
    <table>
        <tr>
            <th>Pending</th>
            <th>In Progress</th>
            <th>Resolved</th>
            <th>Rejected</th>
        </tr>
        <tr>
            <td><?php echo $stats['pending_issues']; ?></td>
            <td><?php echo $stats['in_progress_issues']; ?></td>
            <td><?php echo $stats['resolved_issues']; ?></td>
            <td><?php echo $stats['rejected_issues']; ?></td>
        </tr>
    </table>

    <h2>Severity Breakdown</h2>
    <table>
        <tr>
            <th>Critical</th>
            <th>High</th>
            <th>Medium</th>
            <th>Low</th>
        </tr>
        <tr>
            <td><?php echo $stats['critical_issues']; ?></td>
            <td><?php echo $stats['high_issues']; ?></td>
            <td><?php echo $stats['medium_issues']; ?></td>
            <td><?php echo $stats['low_issues']; ?></td>
        </tr>
    </table>

    <?php if (!empty($area_breakdown)): ?>
    <h2>Electoral Area Breakdown</h2>
    <table>
        <tr>
            <th>Electoral Area</th>
            <th>Issue Count</th>
            <th>Percentage</th>
        </tr>
        <?php foreach ($area_breakdown as $area): ?>
        <tr>
            <td><?php echo htmlspecialchars($area['area']); ?></td>
            <td><?php echo $area['count']; ?></td>
            <td><?php echo round(($area['count'] / $stats['total_issues']) * 100); ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <h2>Detailed Issue List</h2>
    <?php if (empty($issues)): ?>
    <p>No issues found for the selected period.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Electoral Area</th>
            <th>Severity</th>
            <th>Status</th>
            <th>People Affected</th>
            <th>Created</th>
            <th>Last Updated</th>
        </tr>
        <?php foreach ($issues as $issue): ?>
        <tr>
            <td><?php echo $issue['id']; ?></td>
            <td><?php echo htmlspecialchars($issue['title']); ?></td>
            <td><?php echo htmlspecialchars($issue['electoral_area'] ?? 'Unassigned'); ?></td>
            <td>
                <span class="status-label severity-<?php echo $issue['severity']; ?>">
                    <?php echo ucfirst($issue['severity']); ?>
                </span>
            </td>
            <td>
            <span class="status-label status-<?php echo str_replace('_', '-', $issue['status']); ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                </span>
            </td>
            <td><?php echo $issue['people_affected']; ?></td>
            <td><?php echo date('M j, Y', strtotime($issue['created_at'])); ?></td>
            <td><?php echo date('M j, Y', strtotime($issue['updated_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <footer
        style=" margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; text-align: center; font-size: 12px;
                    color: #666;">
                    <p>This report was generated from the Constituency Issue Management System.</p>
                    </footer>

                    <script>
                    // Auto-print the report when loaded in "print" format
                    document.addEventListener('DOMContentLoaded', function() {
                        <?php if ($report_format === 'print' && !isset($error_message)): ?>
                        setTimeout(function() {
                            window.print();
                        }, 1000);
                        <?php endif; ?>
                    });
                    </script>
</body>

</html>