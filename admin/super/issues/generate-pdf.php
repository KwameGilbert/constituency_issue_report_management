<?php
session_start();

// Authentication check
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Include TCPDF library (you may need to install this via Composer)
require_once '../../../vendor/autoload.php';

// Check if issue ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to issues list if no ID provided
    header("Location: ./");
    exit();
}

$issue_id = (int)$_GET['id'];

// Get issue details
$query = "SELECT 
            i.*, 
            ea.name as electoral_area_name,
            fo.name as officer_name,
            fo.email as officer_email,
            fo.phone as officer_phone,
            s.name as supervisor_name,
            pa.name as pa_name,
            pa.email as pa_email
          FROM issues i
          LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
          LEFT JOIN field_officers fo ON i.officer_id = fo.id
          LEFT JOIN field_officers s ON i.supervisor_id = s.id
          LEFT JOIN personal_assistants pa ON pa.id = ?
          WHERE i.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $_SESSION['pa_id'], $issue_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Issue not found, redirect to issues list
    header("Location: ./");
    exit();
}

$issue = $result->fetch_assoc();

// Get issue photos
$photos_query = "SELECT id, photo_url, caption, uploaded_at FROM issue_photos WHERE issue_id = ? ORDER BY uploaded_at";
$photos_stmt = $conn->prepare($photos_query);
$photos_stmt->bind_param("i", $issue_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$photos = [];
while ($photo = $photos_result->fetch_assoc()) {
    $photos[] = $photo;
}

// Get issue updates ONLY where status_change is NOT NULL
$updates_query = "SELECT 
                    iu.*, 
                    COALESCE(pa.name, fo.name) as officer_name,
                    CASE 
                        WHEN pa.id IS NOT NULL THEN 'Personal Assistant' 
                        WHEN fo.id IS NOT NULL THEN 'Field Officer'
                        ELSE 'System'
                    END as user_role
                  FROM issue_updates iu
                  LEFT JOIN personal_assistants pa ON iu.officer_id = pa.id AND pa.id = ?
                  LEFT JOIN field_officers fo ON iu.officer_id = fo.id AND iu.officer_id != ?
                  WHERE iu.issue_id = ? AND iu.status_change IS NOT NULL
                  ORDER BY iu.created_at ASC";
$updates_stmt = $conn->prepare($updates_query);
$pa_id = $_SESSION['pa_id'];
$updates_stmt->bind_param("iii", $pa_id, $pa_id, $issue_id);
$updates_stmt->execute();
$updates_result = $updates_stmt->get_result();
$updates = [];
while ($update = $updates_result->fetch_assoc()) {
    $updates[] = $update;
}

// Helper function to format status for display
function formatStatus($status) {
    $text = str_replace('_', ' ', $status);
    return ucwords($text);
}

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        // Get the current page width
        $pageWidth = $this->getPageWidth();
        
        // Logo
        $logoFile = '../../../assets/images/coat-of-arms.png';
        if (file_exists($logoFile)) {
            $this->Image($logoFile, 15, 10, 20, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Set font
        $this->SetFont('helvetica', 'B', 16);
        
        // Title
        $this->SetY(10);
        $this->Cell(0, 15, 'REPUBLIC OF GHANA', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(7);
        
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 15, 'Constituency Management System', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(7);
        
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 15, 'ISSUE REPORT', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        // Line
        $this->Line(15, 33, $pageWidth - 15, 33);
    }

    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        
        // Date generated
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Constituency Management System');
$pdf->SetAuthor('PA Portal');
$pdf->SetTitle('Issue #' . $issue_id . ' - ' . $issue['title']);
$pdf->SetSubject('Issue Report');
$pdf->SetKeywords('Issue, Report, Constituency, Ghana');

// Set default header and footer data
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set content styles
$pdf->SetFont('helvetica', '', 11);

// Issue Title and ID
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, htmlspecialchars($issue['title']), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, 'Issue ID: ' . $issue_id, 0, 1, 'L');
$pdf->Cell(0, 6, 'Reported on: ' . date('F d, Y', strtotime($issue['created_at'])), 0, 1, 'L');
$pdf->Cell(0, 6, 'Electoral Area: ' . htmlspecialchars($issue['electoral_area_name'] ?? 'Unknown Area'), 0, 1, 'L');
$pdf->Cell(0, 6, 'Current Status: ' . formatStatus($issue['status']), 0, 1, 'L');
$pdf->Cell(0, 6, 'Severity: ' . ucfirst($issue['severity']), 0, 1, 'L');
$pdf->Cell(0, 6, 'People Affected: ' . number_format($issue['people_affected'] ?? 0), 0, 1, 'L');

if ($issue['budget_estimate']) {
    $pdf->Cell(0, 6, 'Budget Estimate: GH₵ ' . number_format($issue['budget_estimate'], 2), 0, 1, 'L');
}

// Location Section
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'LOCATION', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, htmlspecialchars($issue['location']), 0, 1, 'L');

// Description Section
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'ISSUE DESCRIPTION', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 6, htmlspecialchars($issue['description']), 0, 'L', 0, 1);

// Resolution Notes (if resolved)
if ($issue['resolution_notes'] && $issue['status'] === 'resolved') {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'RESOLUTION', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 6, htmlspecialchars($issue['resolution_notes']), 0, 'L', 0, 1);
}

// Officer Information
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'PERSONNEL', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, 'Field Officer: ' . htmlspecialchars($issue['officer_name'] ?? 'Not assigned'), 0, 1, 'L');

if ($issue['supervisor_name']) {
    $pdf->Cell(0, 6, 'Supervisor: ' . htmlspecialchars($issue['supervisor_name']), 0, 1, 'L');
}

$pdf->Cell(0, 6, 'Personal Assistant: ' . htmlspecialchars($issue['pa_name'] ?? 'Not assigned'), 0, 1, 'L');

// Add Status Updates Timeline - only include entries with status changes
if (count($updates) > 0) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'STATUS HISTORY', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->Ln(5);
    
    foreach ($updates as $index => $update) {
        $date = date('F d, Y', strtotime($update['created_at']));
        $time = date('h:i A', strtotime($update['created_at']));
        $status = formatStatus($update['status_change']);
        $by = $update['officer_name'] . ' (' . $update['user_role'] . ')';
        
        // Status index number
        $status_num = $index + 1;
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, $status_num . '. Status changed to: ' . $status, 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Date: ' . $date . ' at ' . $time, 0, 1, 'L');
        $pdf->Cell(0, 6, 'By: ' . $by, 0, 1, 'L');
        
        if (!empty($update['update_text'])) {
            $pdf->Cell(0, 6, 'Comments:', 0, 1, 'L');
            $pdf->MultiCell(0, 6, htmlspecialchars($update['update_text']), 0, 'L', 0, 1);
        }
        
        // Add a separator line except for the last item
        if ($index < count($updates) - 1) {
            $pdf->Ln(2);
            $pdf->Cell(0, 0, '', 'B', 1, 'L');
            $pdf->Ln(5);
        }
    }
}

// Add photos (if any) - keep this separate and simpler
if (count($photos) > 0) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'ISSUE PHOTOS', 0, 1, 'L');
    $pdf->Ln(5);
    
    foreach ($photos as $index => $photo) {
        $photo_path = $_SERVER['DOCUMENT_ROOT'] . $photo['photo_url'];
        
        if (file_exists($photo_path)) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'Photo ' . ($index + 1) . (empty($photo['caption']) ? '' : ': ' . $photo['caption']), 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Uploaded: ' . date('F d, Y', strtotime($photo['uploaded_at'])), 0, 1, 'L');
            $pdf->Ln(2);
            
            // Add photo with responsive height
            $pdf->Image($photo_path, null, null, 120, 0, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
            $pdf->Ln(8);
        }
    }
}

// Output the PDF
$pdf->Output('Issue_' . $issue_id . '_Report.pdf', 'I');
exit();