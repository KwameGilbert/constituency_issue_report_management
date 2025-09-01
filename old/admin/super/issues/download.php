<?php
session_start();

// Authentication check
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';
// Include TCPDF library for PDF generation
require_once '../../../vendor/autoload.php';

// Check if issue ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
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

// Get issue updates for the PDF
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
                  WHERE iu.issue_id = ? 
                  ORDER BY iu.created_at DESC";
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

// 1. Create a temporary directory to store files
$temp_dir = sys_get_temp_dir() . '/issue_' . $issue_id . '_' . time();
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Create images folder
$images_dir = $temp_dir . '/images';
mkdir($images_dir, 0777, true);

// 2. Generate PDF report and save to temp directory
generatePDF($issue, $issue_id, $photos, $updates, $temp_dir);

// 3. Copy images to the images folder
$imageFiles = [];
foreach ($photos as $photo) {
    $source_path = $_SERVER['DOCUMENT_ROOT'] . $photo['photo_url'];
    if (file_exists($source_path)) {
        $file_info = pathinfo($source_path);
        $file_name = 'image_' . $photo['id'] . '.' . $file_info['extension'];
        $dest_path = $images_dir . '/' . $file_name;
        copy($source_path, $dest_path);
        $imageFiles[] = $dest_path;
        
        // Create a text file with image caption if it exists
        if (!empty($photo['caption'])) {
            $caption_file = $images_dir . '/image_' . $photo['id'] . '_caption.txt';
            file_put_contents($caption_file, $photo['caption']);
        }
    }
}

// 4. Create README.txt with issue summary
$readme_content = "ISSUE #" . $issue_id . " - " . strtoupper($issue['title']) . "\n";
$readme_content .= str_repeat("=", 80) . "\n\n";
$readme_content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
$readme_content .= "Status: " . formatStatus($issue['status']) . "\n";
$readme_content .= "Severity: " . ucfirst($issue['severity']) . "\n";
$readme_content .= "Location: " . $issue['location'] . " (" . $issue['electoral_area_name'] . ")\n";
$readme_content .= "Reported on: " . date('F d, Y', strtotime($issue['created_at'])) . "\n";
$readme_content .= "Field Officer: " . ($issue['officer_name'] ?? 'Not assigned') . "\n";
$readme_content .= "Supervisor: " . ($issue['supervisor_name'] ?? 'Not assigned') . "\n\n";
$readme_content .= "This bundle contains:\n";
$readme_content .= "- PDF report with full issue details\n";
$readme_content .= "- Folder with all issue images\n";
$readme_content .= "\nFor more information, log in to the Constituency Management System.";

file_put_contents($temp_dir . '/README.txt', $readme_content);

// 5. Create ZIP archive
$zip_file = $temp_dir . '.zip';
createZip($temp_dir, $zip_file);

// 6. Send the ZIP file to the user
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="Issue_' . $issue_id . '_Bundle.zip"');
header('Content-Length: ' . filesize($zip_file));
header('Pragma: no-cache');
header('Expires: 0');
readfile($zip_file);

// 7. Clean up temporary files
cleanupTempFiles($temp_dir, $zip_file);
exit();

// Function to generate PDF for the bundle
function generatePDF($issue, $issue_id, $photos, $updates, $temp_dir) {
    // Create new PDF document - similar to generate-pdf.php
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
    
    // Create PDF document
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
    
    // Set margins
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Issue Title and ID
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, htmlspecialchars($issue['title']), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, 'Issue ID: ' . $issue_id, 0, 1, 'L');
    $pdf->Cell(0, 6, 'Reported on: ' . date('F d, Y, h:i A', strtotime($issue['created_at'])), 0, 1, 'L');
    
    // Status Information
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Status Information', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    // Create a table for status info
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(60, 8, 'Current Status:', 1, 0, 'L', 1);
    $pdf->Cell(120, 8, formatStatus($issue['status']), 1, 1, 'L', 0);
    
    $pdf->Cell(60, 8, 'Severity:', 1, 0, 'L', 1);
    $pdf->Cell(120, 8, ucfirst($issue['severity']), 1, 1, 'L', 0);
    
    $pdf->Cell(60, 8, 'People Affected:', 1, 0, 'L', 1);
    $pdf->Cell(120, 8, number_format($issue['people_affected'] ?? 0), 1, 1, 'L', 0);
    
    if ($issue['budget_estimate']) {
        $pdf->Cell(60, 8, 'Budget Estimate:', 1, 0, 'L', 1);
        $pdf->Cell(120, 8, 'GH₵ ' . number_format($issue['budget_estimate'], 2), 1, 1, 'L', 0);
    }
    
    // Full implementation similar to generate-pdf.php
    // Location, Description, Resolution Notes, etc.
    
    // Save PDF to temporary directory
    $pdf->Output($temp_dir . '/Issue_' . $issue_id . '_Report.pdf', 'F');
}

// Function to create a ZIP archive
function createZip($source, $destination) {
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));
    if (is_dir($source)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $file = str_replace('\\', '/', realpath($file));

            if (is_dir($file)) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } else if (is_file($file)) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($source)) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}

// Function to clean up temporary files
function cleanupTempFiles($temp_dir, $zip_file) {
    // Delete all files in the temporary directory
    if (is_dir($temp_dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $action($fileinfo->getRealPath());
        }
        
        rmdir($temp_dir);
    }
    
    // Keep the zip file for a short time to allow download, 
    // then delete it with a separate process
    register_shutdown_function(function() use ($zip_file) {
        if (file_exists($zip_file)) {
            @unlink($zip_file);
        }
    });
}