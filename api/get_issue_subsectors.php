<?php
// api/get_issue_subsectors.php
header('Content-Type: application/json');

$sector_id = isset($_GET['sector_id']) ? (int)$_GET['sector_id'] : 0;

$subsectors = [];
if ($sector_id == 1) { // Infrastructure
    $subsectors = [
        ['id' => 10, 'name' => 'Road Construction'],
        ['id' => 11, 'name' => 'Water Supply'],
        ['id' => 12, 'name' => 'Drainage Systems']
    ];
} elseif ($sector_id == 2) { // Social Services
    $subsectors = [
        ['id' => 20, 'name' => 'Education Facilities'],
        ['id' => 21, 'name' => 'Healthcare Access']
    ];
}
// Add more conditions for other sectors

echo json_encode($subsectors);
