<?php
// api/get_communities.php
header('Content-Type: application/json');

$electoral_area_id = isset($_GET['electoral_area_id']) ? (int)$_GET['electoral_area_id'] : 0;

$communities = [];
if ($electoral_area_id == 1) { // Adabraka
    $communities = [
        ['id' => 101, 'name' => 'Asamankese'],
        ['id' => 102, 'name' => 'Nsawam']
    ];
} elseif ($electoral_area_id == 2) { // Osu
    $communities = [
        ['id' => 201, 'name' => 'Osu Kuku Hill'],
        ['id' => 202, 'name' => 'Osu Ringway']
    ];
} elseif ($electoral_area_id == 3) { // Labadi
    $communities = [
        ['id' => 301, 'name' => 'Labadi Beach Road'],
        ['id' => 302, 'name' => 'Labadi Main']
    ];
}

echo json_encode($communities);
