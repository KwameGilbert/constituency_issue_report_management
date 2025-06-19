<?php
// api/get_constituents.php
header('Content-Type: application/json');

echo json_encode([
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john.doe@example.com'],
    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane.smith@example.com'],
    ['id' => 3, 'name' => 'Peter Jones', 'email' => 'peter.jones@example.com']
]);
