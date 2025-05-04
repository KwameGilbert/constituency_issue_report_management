<?php
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kofi Benteh Afful - The Office of the MP</title>
    //
    <!-- Coat of arms favicon  -->
    <link rel="icon" type="image/x-icon" href="assets/images/coat-of-arms.png">
</head>

<body>
    <?php require_once 'includes/header.php';?>
    <main class="bg-white text-gray-800">
        <?php 
        require_once 'includes/hero_carousel.php';
        // require_once 'includes/featured_projects.php';
        require_once 'includes/blog_section.php';// Latest blog grid 
        require_once 'includes/events.php'; // Upcoming events 
        require_once 'includes/map.php'; // Leaflet map 
        require_once 'includes/faq.php'; // FAQ accordion 
        require_once 'includes/vision_mission.php';
        require_once 'includes/newsletter.php'; // Newsletter form 
        require_once 'includes/contact.php';
?>
    </main>
    <?php require_once 'includes/footer.php'; ?>
</body>

</html>