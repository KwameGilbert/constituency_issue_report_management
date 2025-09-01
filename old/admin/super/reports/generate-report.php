<?php
// filepath: c:\xampp\htdocs\swma\admin\pa\reports\generate-report.php
session_start();



// Include database connection
require_once '../../../config/db.php';

// Get format and date range parameters
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Prepare date condition for SQL queries
$date_condition = '';
$date_description = 'All Time';

if ($date_range === 'custom' && !empty($start_date) && !empty($end_date)) {
    $date_condition = "AND issues.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
    $date_description = "From $start_date to $end_date";
} elseif ($date_range === 'month') {
    $date_condition = "AND issues.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $date_description = "Last 30 Days";
} elseif ($date_range === 'quarter') {
    $date_condition = "AND issues.created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
    $date_description = "Last 90 Days";
} elseif ($date_range === 'year') {
    $date_condition = "AND issues.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    $date_description = "Last Year";
}

// Get overall issue statistics
$overall_query = "SELECT 
    COUNT(*) as total_issues,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review_issues,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_issues,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_issues,
    SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_severity,
    SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium_severity,
    SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_severity,
    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_severity,
    SUM(people_affected) as total_people_affected
FROM issues
WHERE 1=1 $date_condition";

$overall_result = $conn->query($overall_query);
$overall_stats = $overall_result->fetch_assoc();

// Calculate resolution rate
$resolution_rate = 0;
if ($overall_stats['total_issues'] > 0) {
    $resolution_rate = round(($overall_stats['resolved_issues'] / $overall_stats['total_issues']) * 100);
}

// Calculate average resolution time (in days)
$avg_resolution_query = "SELECT 
    AVG(DATEDIFF(updated_at, created_at)) as avg_resolution_days
FROM issues
WHERE status = 'resolved' $date_condition";
$avg_resolution_result = $conn->query($avg_resolution_query);
$avg_resolution_row = $avg_resolution_result->fetch_assoc();
$avg_resolution_days = round($avg_resolution_row['avg_resolution_days'] ?? 0, 1);

// Get electoral area breakdown
$area_query = "SELECT 
    ea.name as area_name,
    COUNT(*) as issue_count,
    SUM(CASE WHEN issues.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
FROM issues
LEFT JOIN electoral_areas ea ON issues.electoral_area_id = ea.id
WHERE 1=1 $date_condition
GROUP BY issues.electoral_area_id
ORDER BY issue_count DESC
LIMIT 10";
$area_result = $conn->query($area_query);
$area_data = [];
while ($area = $area_result->fetch_assoc()) {
    $area_name = $area['area_name'] ? $area['area_name'] : 'Unassigned';
    $area_data[] = [
        'name' => $area_name,
        'count' => $area['issue_count'],
        'resolved' => $area['resolved_count']
    ];
}

// Get officer performance
$officer_query = "SELECT 
    fo.name as officer_name,
    COUNT(*) as assigned_issues,
    SUM(CASE WHEN issues.status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
    ROUND(AVG(DATEDIFF(issues.updated_at, issues.created_at)), 1) as avg_resolution_days
FROM issues
JOIN field_officers fo ON issues.officer_id = fo.id
WHERE issues.officer_id IS NOT NULL $date_condition
GROUP BY issues.officer_id
ORDER BY resolved_issues DESC
LIMIT 10";
$officer_result = $conn->query($officer_query);
$officer_data = [];
while ($officer = $officer_result->fetch_assoc()) {
    $officer_data[] = $officer;
}

// Get top issues by people affected
$top_issues_query = "SELECT 
    issues.id,
    issues.title,
    issues.severity,
    issues.status,
    issues.people_affected,
    ea.name as area_name
FROM issues
LEFT JOIN electoral_areas ea ON issues.electoral_area_id = ea.id
WHERE issues.people_affected > 0 $date_condition
ORDER BY issues.people_affected DESC
LIMIT 10";
$top_issues_result = $conn->query($top_issues_query);
$top_issues = [];
while ($issue = $top_issues_result->fetch_assoc()) {
    $top_issues[] = $issue;
}

// Fetch PA information for the report header
$pa_id = $_SESSION['pa_id'];
$pa_query = "SELECT name FROM personal_assistants WHERE id = ?";
$pa_stmt = $conn->prepare($pa_query);
$pa_stmt->bind_param("i", $pa_id);
$pa_stmt->execute();
$pa_result = $pa_stmt->get_result();
$pa_info = $pa_result->fetch_assoc();
$pa_stmt->close();

// Generate the report based on the requested format
switch ($format) {
    case 'pdf':
        generatePDFReport($overall_stats, $resolution_rate, $avg_resolution_days, $area_data, $officer_data, $top_issues, $date_description, $pa_info);
        break;
    case 'excel':
        generateExcelReport($overall_stats, $resolution_rate, $avg_resolution_days, $area_data, $officer_data, $top_issues, $date_description, $pa_info);
        break;
    case 'print':
        generatePrintableReport($overall_stats, $resolution_rate, $avg_resolution_days, $area_data, $officer_data, $top_issues, $date_description, $pa_info);
        break;
    default:
        header("Location: ./");
        exit();
}

/**
 * Generate a PDF report
 */
function generatePDFReport($overall_stats, $resolution_rate, $avg_resolution_days, $area_data, $officer_data, $top_issues, $date_description, $pa_info) {
    // We'll use mPDF library (you need to install it via composer)
    // If not already installed, you can add it with: composer require mpdf/mpdf
    
    // For now, we'll redirect to print view with a message
    echo "<script>alert('PDF generation requires mPDF library. Please install via composer or use the print option.'); window.location='?format=print&date_range=" . $_GET['date_range'] . "&start_date=" . $_GET['start_date'] . "&end_date=" . $_GET['end_date'] . "';</script>";
    exit;
    
    /* Commented out until mPDF is installed
    require_once '../../../vendor/autoload.php';
    
    // Create new mPDF instance
    $mpdf = new \Mpdf\Mpdf([
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15
    ]);
    
    // Build HTML content for PDF
    $html = generateReportHTML($overall_stats, $resolution_rate, $avg_resolution_days, $area_data, $officer_data, $top_issues, $date_description, $pa_info);
    
    // Set document info
    $mpdf->SetTitle('Constituency Issue Report - ' . date('Y-m-d'));
    $mpdf->SetAuthor($pa_info['name']);
    
    // Write HTML to PDF
    $mpdf->WriteHTML($html);
    
    // Output PDF for download
    $mpdf->Output('constituency-report-' . date('Y-m-d') . '.pdf', 'D');
    exit;
    */
}

/**
 * Generate an Excel report
 */
function generateExcelReport($overall_stats, $resolution_rate, $avg_resolution_days, $area_data, $officer_data, $top_issues, $date_description, $pa_info) {
    // We'll use PhpSpreadsheet library (you need to install it via composer)
    // If not already installed, you can add it with: composer require phpoffice/phpspreadsheet
    
    // For now, we'll use a simpler CSV approach
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="constituency-report-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add report header
    fputcsv($output, ['Constituency Issue Report']);
    fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
    fputcsv($output, ['Date Range:', $date_description]);
    fputcsv($output, ['Generated by:', $pa_info['name']]);
    fputcsv($output, []);
    
    // Add summary statistics
    fputcsv($output, ['Summary Statistics']);
    fputcsv($output, ['Total Issues', $overall_stats['total_issues']]);
    fputcsv($output, ['Resolution Rate', $resolution_rate . '%']);
    fputcsv($output, ['Average Resolution Time', $avg_resolution_days . ' days']);
    fputcsv($output, ['People Affected', $overall_stats['total_people_affected']]);
    fputcsv($output, ['Pending Issues', $overall_stats['pending_issues']]);
    fputcsv($output, ['Under Review', $overall_stats['under_review_issues']]);
    fputcsv($output, ['In Progress', $overall_stats['in_progress_issues']]);
    fputcsv($output, ['Resolved', $overall_stats['resolved_issues']]);
    fputcsv($output, ['Rejected', $overall_stats['rejected_issues']]);
    fputcsv($output, []);
    
    // Add officer performance
    fputcsv($output, ['Officer Performance']);
    fputcsv($output, ['Officer Name', 'Assigned Issues', 'Resolved Issues', 'Resolution Rate', 'Avg. Resolution Time (days)']);
    
    foreach ($officer_data as $officer) {
        $resolution_rate = ($officer['assigned_issues'] > 0) ? round(($officer['resolved_issues'] / $officer['assigned_issues']) * 100) . '%' : '0%';
        fputcsv($output, [
            $officer['officer_name'],
            $officer['assigned_issues'],
            $officer['resolved_issues'],
            $resolution_rate,
            $officer['avg_resolution_days']
        ]);
    }
    fputcsv($output, []);
    
    // Add electoral area breakdown
    fputcsv($output, ['Electoral Area Breakdown']);
    fputcsv($output, ['Area Name', 'Total Issues', 'Resolved Issues', 'Resolution Rate']);
    
    foreach ($area_data as $area) {
        $resolution_rate = ($area['count'] > 0) ? round(($area['resolved'] / $area['count']) * 100) . '%' : '0%';
        fputcsv($output, [
            $area['name'],
            $area['count'],
            $area['resolved'],
            $resolution_rate
        ]);
    }
    fputcsv($output, []);
    
    // Add top issues
    fputcsv($output, ['Top Issues by People Affected']);
    fputcsv($output, ['Issue Title', 'Electoral Area', 'Severity', 'Status', 'People Affected']);
    
    foreach ($top_issues as $issue) {
        fputcsv($output, [
            $issue['title'],
            $issue['area_name'] ?? 'Unassigned',
            ucfirst($issue['severity']),
            ucfirst(str_replace('_', ' ', $issue['status'])),
            $issue['people_affected']
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Generate a printable HTML report
 */
function generatePrintableReport($overall_stats, $resolution_rate, $avg_resolution_days, $area_data, $officer_data, $top_issues, $date_description, $pa_info) {
    // Set headers for HTML output
    header('Content-Type: text/html; charset=utf-8');
    
    // Generate the HTML for the printable report
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Constituency Issue Report - ' . date('Y-m-d') . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .report-header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #333;
            }
            .report-header h1 {
                margin-bottom: 5px;
                color: #1e40af;
            }
            .report-header p {
                margin: 0;
                color: #666;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .stat-card {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 15px;
                background-color: #f9fafb;
            }
            .stat-card h3 {
                margin-top: 0;
                color: #666;
                font-size: 14px;
                font-weight: normal;
            }
            .stat-card p {
                font-size: 24px;
                font-weight: bold;
                margin: 10px 0 0;
                color: #1e40af;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            table, th, td {
                border: 1px solid #ddd;
            }
            th {
                background-color: #f1f5f9;
                padding: 12px 15px;
                text-align: left;
            }
            td {
                padding: 10px 15px;
            }
            h2 {
                color: #1e40af;
                margin-top: 40px;
                padding-bottom: 10px;
                border-bottom: 1px solid #ddd;
            }
            .report-footer {
                margin-top: 50px;
                text-align: center;
                font-size: 12px;
                color: #666;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            .watermark {
                position: fixed;
                bottom: 10px;
                right: 10px;
                opacity: 0.1;
                font-size: 40px;
                transform: rotate(-45deg);
                pointer-events: none;
                z-index: -1;
            }
            .severity-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: bold;
                text-align: center;
            }
            .severity-low { background-color: #dcfce7; color: #166534; }
            .severity-medium { background-color: #dbeafe; color: #1e40af; }
            .severity-high { background-color: #fef3c7; color: #92400e; }
            .severity-critical { background-color: #fee2e2; color: #b91c1c; }
            
            .status-pending { background-color: #fef3c7; color: #92400e; }
            .status-under_review { background-color: #dbeafe; color: #1e40af; }
            .status-in_progress { background-color: #f3e8ff; color: #6b21a8; }
            .status-resolved { background-color: #dcfce7; color: #166534; }
            .status-rejected { background-color: #fee2e2; color: #b91c1c; }
            
            @media print {
                body { 
                    padding: 0;
                    font-size: 12px;
                }
                .no-print { display: none; }
                .page-break { page-break-before: always; }
            }
        </style>
    </head>
    <body>
        <div class="watermark">SWMA</div>
        
        <div class="report-header">
            <img src="/assets/images/coat-of-arms.png" alt="Ghana Coat of Arms" style="height: 60px; margin-bottom: 10px;">
            <h1>Constituency Issue Report</h1>
            <p><strong>Sefwi Wiawso Municipal Assembly</strong></p>
            <p>Date Range: ' . htmlspecialchars($date_description) . '</p>
            <p>Generated on: ' . date('F j, Y, g:i a') . '</p>
            <p>Generated by: ' . htmlspecialchars($pa_info['name']) . '</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 8px 16px; background-color: #1e40af; color: white; border: none; border-radius: 4px; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" style="vertical-align: text-top; margin-right: 4px;" viewBox="0 0 16 16">
                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                </svg>
                Print Report
            </button>
            <a href="./" style="margin-left: 10px; padding: 8px 16px; background-color: #64748b; color: white; border: none; border-radius: 4px; text-decoration: none;">
                Back to Reports
            </a>
        </div>
        
        <h2>Summary Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Issues</h3>
                <p>' . number_format($overall_stats['total_issues']) . '</p>
            </div>
            <div class="stat-card">
                <h3>Resolution Rate</h3>
                <p>' . $resolution_rate . '%</p>
            </div>
            <div class="stat-card">
                <h3>Avg. Resolution Time</h3>
                <p>' . $avg_resolution_days . ' days</p>
            </div>
            <div class="stat-card">
                <h3>People Affected</h3>
                <p>' . number_format($overall_stats['total_people_affected']) . '</p>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Pending Issues</h3>
                <p>' . number_format($overall_stats['pending_issues']) . '</p>
            </div>
            <div class="stat-card">
                <h3>Under Review</h3>
                <p>' . number_format($overall_stats['under_review_issues']) . '</p>
            </div>
            <div class="stat-card">
                <h3>In Progress</h3>
                <p>' . number_format($overall_stats['in_progress_issues']) . '</p>
            </div>
            <div class="stat-card">
                <h3>Resolved</h3>
                <p>' . number_format($overall_stats['resolved_issues']) . '</p>
            </div>
            <div class="stat-card">
                <h3>Rejected</h3>
                <p>' . number_format($overall_stats['rejected_issues']) . '</p>
            </div>
        </div>
        
        <h2>Officer Performance</h2>';
        
    if (count($officer_data) > 0) {
        echo '<table>
            <thead>
                <tr>
                    <th>Officer</th>
                    <th>Assigned Issues</th>
                    <th>Resolved Issues</th>
                    <th>Resolution Rate</th>
                    <th>Avg. Resolution Time</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($officer_data as $officer) {
            $officer_resolution_rate = ($officer['assigned_issues'] > 0) ? round(($officer['resolved_issues'] / $officer['assigned_issues']) * 100) : 0;
            echo '<tr>
                <td>' . htmlspecialchars($officer['officer_name']) . '</td>
                <td>' . number_format($officer['assigned_issues']) . '</td>
                <td>' . number_format($officer['resolved_issues']) . '</td>
                <td>' . $officer_resolution_rate . '%</td>
                <td>' . $officer['avg_resolution_days'] . ' days</td>
            </tr>';
        }
        
        echo '</tbody>
        </table>';
    } else {
        echo '<p>No officer performance data available for the selected time period.</p>';
    }
    
    echo '<h2>Electoral Area Breakdown</h2>';
    
    if (count($area_data) > 0) {
        echo '<table>
            <thead>
                <tr>
                    <th>Electoral Area</th>
                    <th>Total Issues</th>
                    <th>Resolved Issues</th>
                    <th>Resolution Rate</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($area_data as $area) {
            $area_resolution_rate = ($area['count'] > 0) ? round(($area['resolved'] / $area['count']) * 100) : 0;
            echo '<tr>
                <td>' . htmlspecialchars($area['name']) . '</td>
                <td>' . number_format($area['count']) . '</td>
                <td>' . number_format($area['resolved']) . '</td>
                <td>' . $area_resolution_rate . '%</td>
            </tr>';
        }
        
        echo '</tbody>
        </table>';
    } else {
        echo '<p>No electoral area data available for the selected time period.</p>';
    }
    
    echo '<div class="page-break"></div>
    
    <h2>Top Issues by People Affected</h2>';
    
    if (count($top_issues) > 0) {
        echo '<table>
            <thead>
                <tr>
                    <th>Issue</th>
                    <th>Electoral Area</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>People Affected</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($top_issues as $issue) {
            echo '<tr>
                <td>' . htmlspecialchars($issue['title']) . '</td>
                <td>' . htmlspecialchars($issue['area_name'] ?? 'Unassigned') . '</td>
                <td>
                    <span class="severity-badge severity-' . $issue['severity'] . '">
                        ' . ucfirst($issue['severity']) . '
                    </span>
                </td>
                <td>
                    <span class="severity-badge status-' . $issue['status'] . '">
                        ' . ucfirst(str_replace('_', ' ', $issue['status'])) . '
                    </span>
                </td>
                <td>' . number_format($issue['people_affected']) . '</td>
            </tr>';
        }
        
        echo '</tbody>
        </table>';
    } else {
        echo '<p>No issues with people affected data for the selected time period.</p>';
    }
    
    echo '<div class="report-footer">
        <p>© ' . date('Y') . ' Sefwi Wiawso Municipal Assembly. All rights reserved.</p>
        <p>Generated from the Constituency Issue Report Management System</p>
    </div>
    
    </body>
    </html>';
}