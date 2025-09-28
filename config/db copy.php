<?php
$host = "localhost";
$database = "kofiwgmr_constituency_db";
$user = "kofiwgmr_KwameGilbert";
$password = ";4L2(Ubpwa)R";

try {
    $conn = new mysqli($host, $user, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>