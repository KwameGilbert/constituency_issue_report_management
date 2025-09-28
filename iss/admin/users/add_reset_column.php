<?php
require_once __DIR__ . '/../../config/db_connection.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if column exists first to avoid errors
    $checkColumnSql = "SHOW COLUMNS FROM users LIKE 'password_reset_required'";
    $stmt = $conn->prepare($checkColumnSql);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, so add it
        $sql = "ALTER TABLE users ADD COLUMN password_reset_required TINYINT(1) DEFAULT 0";
        $conn->exec($sql);
        echo "Successfully added password_reset_required column to users table.";
    } else {
        echo "Column password_reset_required already exists.";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
