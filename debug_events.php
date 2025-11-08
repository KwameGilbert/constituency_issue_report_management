<?php
require_once 'config/db.php';

echo "<h2>🔍 Debug Events Database</h2>";

// Check if events table exists and has data
$result = $conn->query('SELECT id, name, image_url FROM events LIMIT 10');

if ($result) {
    $events = $result->fetch_all(MYSQLI_ASSOC);
    echo "<p><strong>Found " . count($events) . " events:</strong></p>";
    
    if (count($events) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Image URL</th></tr>";
        foreach ($events as $event) {
            echo "<tr>";
            echo "<td>" . $event['id'] . "</td>";
            echo "<td>" . htmlspecialchars($event['name']) . "</td>";
            echo "<td>" . ($event['image_url'] ? htmlspecialchars($event['image_url']) : '<em>NULL</em>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No events found in database!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Query failed: " . $conn->error . "</p>";
}

// Test the same query that the gallery uses
echo "<hr><h3>🔍 Testing Gallery Query</h3>";
$gallery_query = "
    SELECT id, name, location, start_date, images, image_url
    FROM events 
    WHERE ( (image_url IS NOT NULL AND image_url != '') OR (images IS NOT NULL AND images != '') )
    ORDER BY start_date ASC
";

$result2 = $conn->query($gallery_query);
if ($result2) {
    $gallery_events = $result2->fetch_all(MYSQLI_ASSOC);
    echo "<p><strong>Gallery query found " . count($gallery_events) . " events with images:</strong></p>";
    
    if (count($gallery_events) > 0) {
        foreach ($gallery_events as $event) {
            echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc;'>";
            echo "<strong>" . htmlspecialchars($event['name']) . "</strong><br>";
            echo "Location: " . htmlspecialchars($event['location']) . "<br>";
            echo "Date: " . $event['start_date'] . "<br>";
            echo "Image URL: " . ($event['image_url'] ? htmlspecialchars($event['image_url']) : '<em>NULL</em>') . "<br>";
            echo "Images JSON: " . ($event['images'] ? htmlspecialchars($event['images']) : '<em>NULL</em>');
            echo "</div>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No events match the gallery criteria!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Gallery query failed: " . $conn->error . "</p>";
}
?>