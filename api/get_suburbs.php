<?php
// api/get_suburbs.php
header('Content-Type: application/json');

$community_id = isset($_GET['community_id']) ? (int)$_GET['community_id'] : 0;

$suburbs = [];
if ($community_id == 101) { // Asamankese
    $suburbs = [
        ['id' => 1001, 'name' => 'Asamankese North'],
        ['id' => 1002, 'name' => 'Asamankese South']
    ];
} elseif ($community_id == 201) { // Osu Kuku Hill
    $suburbs = [
        ['id' => 2001, 'name' => 'Kuku Hill West'],
        ['id' => 2002, 'name' => 'Kuku Hill East']
    ];
}
// Add more conditions for other communities

echo json_encode($suburbs);
