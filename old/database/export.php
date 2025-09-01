<?php

function exportDatabase($outputFile) {
    // Create database connection
    $conn = new mysqli("localhost", "root", "", "constituency_db");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get all table names
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    // Start output buffering
    ob_start();

    $constraints = ""; // To store constraints separately

    // Loop through each table and generate SQL dump
    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM `$table`");
        $numFields = $result->field_count;

        $return = "DROP TABLE IF EXISTS `$table`;";
        $row2 = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
        $createTableSQL = $row2[1];

        // Separate constraints from CREATE TABLE
        if (preg_match_all('/CONSTRAINT.*?,/', $createTableSQL, $matches)) {
            foreach ($matches[0] as $constraint) {
                $constraints .= rtrim($constraint, ',') . ";\n";
            }
            $createTableSQL = preg_replace('/,\s*CONSTRAINT.*?(?=\))/', '', $createTableSQL);
        }

        $return .= "\n\n" . $createTableSQL . ";\n\n";

        for ($i = 0; $i < $numFields; $i++) {
            while ($row = $result->fetch_row()) {
                $return .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $numFields; $j++) {
                    $row[$j] = $conn->real_escape_string($row[$j]);
                    $return .= isset($row[$j]) ? '"' . $row[$j] . '"' : 'NULL';
                    $return .= $j < ($numFields - 1) ? ',' : '';
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
        echo $return;
    }

    // Append constraints at the end
    echo "\n\n-- Constraints\n" . $constraints;

    // Write output to file
    file_put_contents($outputFile, ob_get_contents());
    ob_end_clean();

    // Close connection
    $conn->close();
}

// Specify the output file path
$outputFile = 'database_export_' . date('Y-m-d_H-i-s') . '.sql';
exportDatabase($outputFile);
echo "Database exported successfully to $outputFile";