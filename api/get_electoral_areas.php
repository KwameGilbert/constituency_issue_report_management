<?php
// api/get_electoral_areas.php
header('Content-Type: application/json');

// In a real application, fetch from database using Lookup model
echo json_encode([
    ['id' => 1, 'name' => 'Adabraka'],
    ['id' => 2, 'name' => 'Osu'],
    ['id' => 3, 'name' => 'Labadi'],
    ['id' => 4, 'name' => 'Cantonments']
]);
