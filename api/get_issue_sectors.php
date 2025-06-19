<?php
// api/get_issue_sectors.php
header('Content-Type: application/json');

echo json_encode([
    ['id' => 1, 'name' => 'Infrastructure'],
    ['id' => 2, 'name' => 'Social Services'],
    ['id' => 3, 'name' => 'Public Safety'],
    ['id' => 4, 'name' => 'Economic Development']
]);
