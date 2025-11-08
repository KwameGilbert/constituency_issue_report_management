<?php
/**
 * Add Events Only - Script to populate events table
 */

require_once 'config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📅 Adding Events to Database</h1>";

// Clear existing events first
$conn->query("DELETE FROM events");
echo "<p>✅ Cleared existing events</p>";

$events = [
    [
        'name' => 'Community Development Forum',
        'slug' => 'community-development-forum',
        'description' => 'Annual forum to discuss community development projects and priorities for the upcoming year.',
        'start_date' => date('Y-m-d', strtotime('+30 days')),
        'end_date' => date('Y-m-d', strtotime('+30 days')),
        'event_time' => '09:00:00',
        'location' => 'Sefwi Wiawso Community Center',
        'image_url' => 'assets/images/carousel/banner.jpg'
    ],
    [
        'name' => 'Youth Empowerment Workshop',
        'slug' => 'youth-empowerment-workshop',
        'description' => 'Workshop focused on skills development and entrepreneurship opportunities for young people.',
        'start_date' => date('Y-m-d', strtotime('+15 days')),
        'end_date' => date('Y-m-d', strtotime('+16 days')),
        'event_time' => '10:00:00',
        'location' => 'Municipal Assembly Hall',
        'image_url' => 'assets/images/carousel/slide2.jpg'
    ],
    [
        'name' => 'Health Screening Campaign',
        'slug' => 'health-screening-campaign',
        'description' => 'Free health screening and vaccination campaign for all residents.',
        'start_date' => date('Y-m-d', strtotime('+7 days')),
        'end_date' => date('Y-m-d', strtotime('+7 days')),
        'event_time' => '08:00:00',
        'location' => 'Sefwi Wiawso Hospital',
        'image_url' => 'assets/images/carousel/slide3.jpg'
    ],
    [
        'name' => 'Farmers Market Festival',
        'slug' => 'farmers-market-festival',
        'description' => 'Annual celebration of local farmers and agricultural products with exhibitions, sales, and cultural performances.',
        'start_date' => date('Y-m-d', strtotime('+45 days')),
        'end_date' => date('Y-m-d', strtotime('+47 days')),
        'event_time' => '06:00:00',
        'location' => 'Sefwi Wiawso Market Square',
        'image_url' => 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Women Entrepreneurs Summit',
        'slug' => 'women-entrepreneurs-summit',
        'description' => 'Empowering women through business training, networking, and access to microfinance opportunities.',
        'start_date' => date('Y-m-d', strtotime('+20 days')),
        'end_date' => date('Y-m-d', strtotime('+20 days')),
        'event_time' => '09:30:00',
        'location' => 'Municipal Conference Hall',
        'image_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Digital Literacy Training',
        'slug' => 'digital-literacy-training',
        'description' => 'Computer and internet skills training for community members of all ages.',
        'start_date' => date('Y-m-d', strtotime('+25 days')),
        'end_date' => date('Y-m-d', strtotime('+27 days')),
        'event_time' => '14:00:00',
        'location' => 'Sefwi Wiawso Technical Institute',
        'image_url' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Environmental Clean-Up Day',
        'slug' => 'environmental-clean-up-day',
        'description' => 'Community-wide environmental awareness and clean-up campaign focusing on waste management.',
        'start_date' => date('Y-m-d', strtotime('+12 days')),
        'end_date' => date('Y-m-d', strtotime('+12 days')),
        'event_time' => '07:00:00',
        'location' => 'Various locations across municipality',
        'image_url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Cultural Heritage Festival',
        'slug' => 'cultural-heritage-festival',
        'description' => 'Celebration of local culture, traditions, music, dance, and traditional crafts.',
        'start_date' => date('Y-m-d', strtotime('+60 days')),
        'end_date' => date('Y-m-d', strtotime('+62 days')),
        'event_time' => '16:00:00',
        'location' => 'Sefwi Wiawso Cultural Center',
        'image_url' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'School Infrastructure Development Meeting',
        'slug' => 'school-infrastructure-meeting',
        'description' => 'Planning meeting for new classroom blocks and educational facility improvements.',
        'start_date' => date('Y-m-d', strtotime('+35 days')),
        'end_date' => date('Y-m-d', strtotime('+35 days')),
        'event_time' => '10:30:00',
        'location' => 'District Education Office',
        'image_url' => 'https://images.unsplash.com/photo-1497486751825-1233686d5d80?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Senior Citizens Health Fair',
        'slug' => 'senior-citizens-health-fair',
        'description' => 'Specialized health services, screenings, and wellness activities for elderly residents.',
        'start_date' => date('Y-m-d', strtotime('+18 days')),
        'end_date' => date('Y-m-d', strtotime('+18 days')),
        'event_time' => '08:30:00',
        'location' => 'Municipal Health Center',
        'image_url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Road Safety Awareness Campaign',
        'slug' => 'road-safety-awareness-campaign',
        'description' => 'Educational program on road safety, traffic rules, and accident prevention.',
        'start_date' => date('Y-m-d', strtotime('+40 days')),
        'end_date' => date('Y-m-d', strtotime('+40 days')),
        'event_time' => '11:00:00',
        'location' => 'Main Lorry Station',
        'image_url' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Micro-Finance Loan Distribution',
        'slug' => 'micro-finance-loan-distribution',
        'description' => 'Distribution of microfinance loans to support small-scale businesses and entrepreneurs.',
        'start_date' => date('Y-m-d', strtotime('+28 days')),
        'end_date' => date('Y-m-d', strtotime('+28 days')),
        'event_time' => '13:00:00',
        'location' => 'Rural Bank Conference Room',
        'image_url' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Water Project Inauguration',
        'slug' => 'water-project-inauguration',
        'description' => 'Official opening of new borehole and water distribution system serving 5 communities.',
        'start_date' => date('Y-m-d', strtotime('+50 days')),
        'end_date' => date('Y-m-d', strtotime('+50 days')),
        'event_time' => '15:00:00',
        'location' => 'Akontombra Water Treatment Plant',
        'image_url' => 'https://images.unsplash.com/photo-1544966503-7cc5ac882d5f?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Agricultural Training Workshop',
        'slug' => 'agricultural-training-workshop',
        'description' => 'Modern farming techniques, crop rotation, and sustainable agriculture practices for local farmers.',
        'start_date' => date('Y-m-d', strtotime('+22 days')),
        'end_date' => date('Y-m-d', strtotime('+24 days')),
        'event_time' => '08:00:00',
        'location' => 'Boako Agricultural Center',
        'image_url' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=600&h=400&fit=crop'
    ],
    [
        'name' => 'Tourism Development Summit',
        'slug' => 'tourism-development-summit',
        'description' => 'Exploring tourism potential, eco-tourism, and cultural tourism opportunities in the region.',
        'start_date' => date('Y-m-d', strtotime('+55 days')),
        'end_date' => date('Y-m-d', strtotime('+56 days')),
        'event_time' => '09:00:00',
        'location' => 'Manhyia Tourism Center',
        'image_url' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=600&h=400&fit=crop'
    ]
];

$stmt = $conn->prepare("
    INSERT INTO events (name, slug, description, start_date, end_date, event_time, location, image_url, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$count = 0;
foreach ($events as $event) {
    $stmt->bind_param("ssssssss", 
        $event['name'], $event['slug'], $event['description'], 
        $event['start_date'], $event['end_date'], $event['event_time'],
        $event['location'], $event['image_url']
    );
    
    if ($stmt->execute()) {
        $count++;
        echo "<p>✓ Added event: {$event['name']}</p>";
    } else {
        echo "<p>❌ Error adding: {$event['name']} - " . $stmt->error . "</p>";
    }
}

echo "<h2>🎉 Successfully added {$count} events!</h2>";
echo "<p><a href='index.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Homepage Gallery</a></p>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Events - Sefwi Wiawso</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #f59e0b; }
        h2 { color: #059669; }
        p { margin: 5px 0; }
    </style>
</head>
<body>
</body>
</html>