<?php
// File: ping-sitemap.php - Can be run via cron job
$sitemap_url = urlencode("https://swma.rf.gd/sitemap.xml");

// Ping Google
file_get_contents("https://www.google.com/ping?sitemap=" . $sitemap_url);

// Ping Bing
file_get_contents("https://www.bing.com/ping?sitemap=" . $sitemap_url);

echo "Pinged search engines with the sitemap URL.";
?>