<?php
// api/get_issue_categories.php
header('Content-Type: application/json');

echo json_encode([
    ['id' => 1, 'name' => 'Roads'],
    ['id' => 2, 'name' => 'Water'],
    ['id' => 3, 'name' => 'Electricity'],
    ['id' => 4, 'name' => 'Environment'],
    ['id' => 5, 'name' => 'Sanitation'],
    ['id' => 6, 'name' => 'Education'],
    ['id' => 7, 'name' => 'Security'],
    ['id' => 8, 'name' => 'Health']
]);
